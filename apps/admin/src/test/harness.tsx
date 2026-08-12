import { render, type RenderResult } from '@testing-library/react';
import type { ReactElement } from 'react';
import { MemoryRouter } from 'react-router-dom';

import { SessionProvider } from '../api/SessionProvider.js';
import type { StaffMember, StaffRole } from '../types.js';

/**
 * Renders a component tree against a stubbed `fetch`.
 *
 * The **real** API client is used, with the real problem-document handling and
 * the real generated types — only the network is replaced. Substituting the
 * client itself would mean these tests pass while the thing that actually runs
 * in a browser is never exercised, which is the failure mode a component test
 * is most prone to.
 */
export interface StubbedResponse {
  readonly status?: number;
  readonly body: unknown;
}

export type Route = string | RegExp;

export function stubFetch(routes: ReadonlyArray<readonly [Route, StubbedResponse]>) {
  const calls: Request[] = [];

  const fetch = (async (input: RequestInfo | URL) => {
    const request = input as Request;
    calls.push(request);

    const match = routes.find(([route]) =>
      typeof route === 'string' ? request.url.includes(route) : route.test(request.url),
    );

    if (match === undefined) {
      /*
       * An unstubbed call fails loudly rather than returning an empty object.
       * A silent default would let a test pass while the component called an
       * endpoint nobody meant it to.
       */
      throw new Error(`No stub for ${request.method} ${request.url}`);
    }

    const [, response] = match;

    return new Response(JSON.stringify(response.body), {
      status: response.status ?? 200,
      headers: { 'Content-Type': 'application/json' },
    });
  }) as unknown as typeof globalThis.fetch;

  return { fetch, calls };
}

export function staffMember(role: StaffRole, overrides: Partial<StaffMember> = {}): StaffMember {
  return {
    id: '019ff0f4-0000-7000-8000-000000000001',
    name: 'Test Operator',
    email: 'operator@staff.test',
    role,
    status: 'active',
    mfa_enrolled: true,
    ...overrides,
  };
}

export function renderWithSession(
  ui: ReactElement,
  options: {
    readonly fetch: typeof globalThis.fetch;
    readonly staff?: StaffMember | null;
    readonly route?: string;
  },
): RenderResult {
  return render(
    <MemoryRouter initialEntries={[options.route ?? '/']}>
      <SessionProvider
        fetch={options.fetch}
        initialStaff={options.staff ?? null}
        initialToken={options.staff === null ? null : 'staff-token'}
      >
        {ui}
      </SessionProvider>
    </MemoryRouter>,
  );
}
