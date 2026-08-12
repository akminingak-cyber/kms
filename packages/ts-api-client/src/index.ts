/**
 * Typed clients for the KMS TV APIs.
 *
 * Every operation, path and response shape comes from `src/types/*.ts`, which
 * are generated from the OpenAPI contracts. There are no hand-written fetch
 * calls to our own API anywhere in this repository: a URL typed by hand is a
 * URL that can drift from the contract silently, and the whole point of the
 * two-direction conformance test is that such drift is impossible.
 *
 * What *is* hand-written here is the small amount of glue that is not
 * expressible in a contract: how a token is attached, how a correlation id is
 * propagated, and how a problem document becomes a typed error.
 */
import createClient, { type Middleware } from 'openapi-fetch';

import { ApiError, toProblem } from './problem.js';
import type { paths as AdminPaths } from './types/admin.js';
import type { paths as ClientPaths } from './types/client.js';

export { ApiError, toProblem } from './problem.js';
export type { ApiProblem } from './problem.js';
export type { paths as AdminPaths, components as AdminComponents } from './types/admin.js';
export type { paths as ClientPaths, components as ClientComponents } from './types/client.js';

export interface ApiClientOptions {
  /** Base URL of the surface, e.g. `/api/admin/v1`. */
  readonly baseUrl: string;
  /**
   * Supplies the current access token, or null when signed out.
   *
   * A function rather than a value, so a token refreshed mid-session is picked
   * up by calls already in flight rather than by the next page load.
   */
  readonly token: () => string | null;
  /**
   * Called when the API rejects the token.
   *
   * Sign-out is the application's decision, not the transport's — the transport
   * only reports it. A client that cleared state itself would fight whatever
   * the application was doing at the time.
   */
  readonly onUnauthenticated?: () => void;
  /** Injectable for tests; defaults to the platform `fetch`. */
  readonly fetch?: typeof globalThis.fetch;
}

/** Codes that mean "this token will not work again". */
const UNAUTHENTICATED_CODES = new Set([
  'AUTH_REQUIRED',
  'AUTH_TOKEN_EXPIRED',
  'AUTH_TOKEN_INVALID',
  'AUTH_TOKEN_REVOKED',
]);

function middleware(options: ApiClientOptions): Middleware {
  return {
    onRequest({ request }) {
      const token = options.token();

      if (token !== null) {
        request.headers.set('Authorization', `Bearer ${token}`);
      }

      /*
       * A correlation id per request, so one complaint can be traced from the
       * panel through the API to the decision log. The server generates one if
       * absent, but originating it here means the value is known to the caller
       * and can be shown in an error.
       */
      if (!request.headers.has('X-Correlation-Id')) {
        request.headers.set('X-Correlation-Id', crypto.randomUUID());
      }

      return request;
    },

    async onResponse({ response }) {
      if (response.ok) {
        return response;
      }

      /*
       * Cloned before reading: `openapi-fetch` still hands the original
       * response back to the caller, and a body can only be consumed once.
       */
      const body = await response
        .clone()
        .json()
        .catch(() => null);

      const problem = toProblem(body, response);

      if (UNAUTHENTICATED_CODES.has(problem.code)) {
        options.onUnauthenticated?.();
      }

      throw new ApiError(problem, response);
    },
  };
}

function build<Paths extends object>(options: ApiClientOptions) {
  const client = createClient<Paths>({
    baseUrl: options.baseUrl,
    fetch: options.fetch,
    headers: { Accept: 'application/json' },
  });

  client.use(middleware(options));

  return client;
}

/** The operator surface. Requires a staff token obtained with password **and** TOTP. */
export function createAdminClient(options: ApiClientOptions) {
  return build<AdminPaths>(options);
}

/** The viewer-facing surface. */
export function createViewerClient(options: ApiClientOptions) {
  return build<ClientPaths>(options);
}

export type AdminClient = ReturnType<typeof createAdminClient>;
export type ViewerClient = ReturnType<typeof createViewerClient>;
