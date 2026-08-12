import { describe, expect, it, vi } from 'vitest';

import { ApiError, createAdminClient } from '../index.js';

/**
 * The transport, and the one thing it must never get wrong: a failed request
 * has to arrive as a typed error carrying the platform's stable code.
 *
 * Everything that switches on a failure — a form showing field errors, a
 * sign-out on a revoked token, a support agent quoting a correlation id —
 * depends on that code surviving the trip. Prose does not survive it: the
 * title is not localised and not stable, and behaviour built on it changes
 * when someone edits a sentence.
 */
function respondWith(status: number, body: unknown, headers: Record<string, string> = {}) {
  return vi.fn(
    async () =>
      new Response(typeof body === 'string' ? body : JSON.stringify(body), {
        status,
        headers: { 'Content-Type': 'application/problem+json', ...headers },
      }),
  );
}

function clientWith(fetch: typeof globalThis.fetch, onUnauthenticated?: () => void) {
  return createAdminClient({
    baseUrl: 'https://admin.test/api/admin/v1',
    token: () => 'staff-token',
    fetch,
    ...(onUnauthenticated ? { onUnauthenticated } : {}),
  });
}

describe('admin client', () => {
  it('attaches the bearer token and a correlation id to every request', async () => {
    const fetch = vi.fn(
      async (_input: RequestInfo | URL) =>
        new Response(JSON.stringify({ data: [] }), { status: 200 }),
    );

    await clientWith(fetch as unknown as typeof globalThis.fetch).GET('/channels');

    const request = fetch.mock.calls[0]?.[0] as unknown as Request;
    expect(request.headers.get('Authorization')).toBe('Bearer staff-token');
    // Originated here rather than left to the server, so the value is known to
    // the caller and can be shown in an error message.
    expect(request.headers.get('X-Correlation-Id')).toMatch(/^[0-9a-f-]{36}$/);
  });

  it('rejects with the platform error code rather than the prose', async () => {
    const fetch = respondWith(403, {
      type: 'https://problems.kmstv.invalid/forbidden',
      title: 'Not permitted',
      status: 403,
      code: 'FORBIDDEN',
      correlation_id: 'abc-123',
    });

    await expect(
      clientWith(fetch as unknown as typeof globalThis.fetch).GET('/staff'),
    ).rejects.toMatchObject({ code: 'FORBIDDEN', status: 403, correlationId: 'abc-123' });
  });

  it('exposes field errors so a form can show them against the right input', async () => {
    const fetch = respondWith(422, {
      title: 'Validation failed',
      status: 422,
      code: 'VALIDATION_FAILED',
      correlation_id: 'abc-123',
      errors: { slug: ['The slug has already been taken.'], name: ['The name field is required.'] },
    });

    try {
      await clientWith(fetch as unknown as typeof globalThis.fetch).POST('/channels', {
        body: { slug: 'one', name: '' },
      });
      expect.unreachable('the call should have rejected');
    } catch (error) {
      expect(error).toBeInstanceOf(ApiError);
      expect((error as ApiError).fieldErrors()).toEqual({
        slug: ['The slug has already been taken.'],
        name: ['The name field is required.'],
      });
    }
  });

  it('reports a revoked token so the application can sign out', async () => {
    const onUnauthenticated = vi.fn();
    const fetch = respondWith(401, {
      title: 'Access token revoked',
      status: 401,
      code: 'AUTH_TOKEN_REVOKED',
      correlation_id: 'abc-123',
    });

    await expect(
      clientWith(fetch as unknown as typeof globalThis.fetch, onUnauthenticated).GET('/me'),
    ).rejects.toBeInstanceOf(ApiError);

    // Reported, not acted on: signing out is the application's decision, and a
    // transport that cleared state itself would fight whatever the application
    // was doing at the time.
    expect(onUnauthenticated).toHaveBeenCalledOnce();
  });

  it('does not sign the user out for an ordinary refusal', async () => {
    const onUnauthenticated = vi.fn();
    const fetch = respondWith(403, {
      title: 'Not permitted',
      status: 403,
      code: 'FORBIDDEN',
      correlation_id: 'abc-123',
    });

    await expect(
      clientWith(fetch as unknown as typeof globalThis.fetch, onUnauthenticated).GET('/staff'),
    ).rejects.toBeInstanceOf(ApiError);

    // A role refusal is not a credential problem. Signing out here would log
    // people out for clicking a link they were not allowed to follow.
    expect(onUnauthenticated).not.toHaveBeenCalled();
  });

  it('survives a response that is not a problem document at all', async () => {
    // A gateway timeout returning HTML is still a failure the user must be
    // told about, and losing the status code to "cannot parse error" throws
    // away the only thing that explained it.
    const fetch = vi.fn(
      async () =>
        new Response('<html><body>504 Gateway Time-out</body></html>', {
          status: 504,
          statusText: 'Gateway Timeout',
          headers: { 'Content-Type': 'text/html' },
        }),
    );

    try {
      await clientWith(fetch as unknown as typeof globalThis.fetch).GET('/channels');
      expect.unreachable('the call should have rejected');
    } catch (error) {
      const problem = (error as ApiError).problem;
      expect(problem.status).toBe(504);
      // Not misreported as one of the platform's own codes.
      expect(problem.code).toBe('CLIENT_UNPARSEABLE_ERROR');
    }
  });

  it('ignores a malformed errors object rather than crashing rendering', async () => {
    const fetch = respondWith(422, {
      title: 'Validation failed',
      status: 422,
      code: 'VALIDATION_FAILED',
      correlation_id: 'abc-123',
      errors: { slug: 'not an array' },
    });

    try {
      await clientWith(fetch as unknown as typeof globalThis.fetch).POST('/channels', {
        body: { slug: 'one', name: 'One' },
      });
      expect.unreachable('the call should have rejected');
    } catch (error) {
      // Validated at the boundary: a shape that does not match is dropped here
      // rather than producing `undefined.map` deep inside a form.
      expect((error as ApiError).fieldErrors()).toEqual({});
    }
  });

  it('sends no Authorization header when signed out', async () => {
    const fetch = vi.fn(
      async (_input: RequestInfo | URL) =>
        new Response(JSON.stringify({ data: [] }), { status: 200 }),
    );

    await createAdminClient({
      baseUrl: 'https://admin.test/api/admin/v1',
      token: () => null,
      fetch: fetch as unknown as typeof globalThis.fetch,
    }).GET('/channels');

    const request = fetch.mock.calls[0]?.[0] as unknown as Request;
    expect(request.headers.has('Authorization')).toBe(false);
  });
});
