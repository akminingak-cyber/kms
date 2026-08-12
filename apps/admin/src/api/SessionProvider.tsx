import { createAdminClient, type AdminClient } from '@kms/ts-api-client';
import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react';

import type { StaffMember } from '../types.js';

/**
 * The signed-in session, and the client that carries it.
 *
 * ## Where the token lives, and why it is not in storage
 *
 * **In memory only.** Not `localStorage`, not `sessionStorage`, not a
 * JavaScript-readable cookie.
 *
 * A staff token reaches rights data, pricing and the decision log for every
 * subscriber. Any of those storages is readable by any script that manages to
 * run on this origin, so a single cross-site scripting flaw — in this
 * application or in any dependency it ever picks up — would hand over a working
 * staff credential rather than merely defacing a page.
 *
 * The cost is real and is accepted: **a page refresh signs the operator out**,
 * and they sign in again with password and TOTP. For an internal tool used by a
 * small number of people, on short-lived tokens, that is a fair trade for
 * removing the most valuable thing an attacker could steal from this origin.
 *
 * If it later proves unworkable in practice, the answer is a short-lived
 * refresh mechanism on an `HttpOnly` cookie — not moving the access token into
 * storage where script can read it.
 */
export interface Session {
  readonly staff: StaffMember | null;
  readonly client: AdminClient;
  readonly signIn: (token: string, staff: StaffMember) => void;
  readonly signOut: () => void;
}

/** Where the admin API lives, relative to the origin serving this panel. */
const ADMIN_API_PATH = '/api/admin/v1';

/**
 * A mutable holder for the access token.
 *
 * An explicit object rather than a ref, for two reasons. It is never read
 * during render — only when a request is being built — and saying so with a
 * type is clearer than a comment promising it. And it keeps the token out of
 * React state entirely, where a component-tree serialisation or a
 * development-tools inspection would expose it.
 */
class TokenHolder {
  private value: string | null;

  constructor(initial: string | null) {
    this.value = initial;
  }

  get(): string | null {
    return this.value;
  }

  set(token: string | null): void {
    this.value = token;
  }
}

const SessionContext = createContext<Session | null>(null);

export interface SessionProviderProps {
  readonly children: ReactNode;
  /** Injected by tests so a component tree can be exercised without a network. */
  readonly fetch?: typeof globalThis.fetch;
  readonly initialStaff?: StaffMember | null;
  readonly initialToken?: string | null;
}

export function SessionProvider({
  children,
  fetch,
  initialStaff = null,
  initialToken = null,
}: SessionProviderProps) {
  const [staff, setStaff] = useState<StaffMember | null>(initialStaff);

  // Created once. A lazy initialiser rather than a plain value, so a re-render
  // cannot replace the holder and lose the token with it.
  const [token] = useState(() => new TokenHolder(initialToken));

  const signOut = useCallback(() => {
    token.set(null);
    setStaff(null);
  }, [token]);

  const signIn = useCallback(
    (issued: string, member: StaffMember) => {
      token.set(issued);
      setStaff(member);
    },
    [token],
  );

  const client = useMemo(
    () =>
      createAdminClient({
        /*
         * Same-origin in every environment: the panel is served by the same
         * edge as the API it calls, so there is no cross-origin credential
         * handling to get wrong.
         *
         * Resolved to an absolute URL against the current origin rather than
         * left relative. A browser's `fetch` would resolve `/api/...` against
         * the document, but `Request` outside a browser refuses it — so a
         * relative base is a client that works in the app and throws in a test,
         * which is exactly the environment-dependence a test is supposed to
         * catch rather than suffer from.
         */
        baseUrl: new URL(ADMIN_API_PATH, globalThis.location.origin).toString(),
        token: () => token.get(),
        // The transport reports a dead token; deciding to sign out is this
        // application's call.
        onUnauthenticated: signOut,
        ...(fetch ? { fetch } : {}),
      }),
    [fetch, signOut, token],
  );

  const value = useMemo<Session>(
    () => ({ staff, client, signIn, signOut }),
    [staff, client, signIn, signOut],
  );

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSession(): Session {
  const session = useContext(SessionContext);

  if (session === null) {
    throw new Error('useSession was called outside a SessionProvider.');
  }

  return session;
}
