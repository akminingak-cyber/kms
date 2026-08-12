import { ApiError } from '@kms/ts-api-client';
import { useCallback, useEffect, useState } from 'react';

/**
 * Loading, error and data for one request.
 *
 * Deliberately small, and deliberately not a caching library. A caching layer
 * would be the right call for a viewer-facing application making the same
 * request from many places; this panel is a handful of operators looking at
 * lists, where a stale cache is a worse failure than a refetch — an operator
 * who has just created a channel and does not see it concludes the write
 * failed.
 *
 * **The caller owns the dependency list.** `load` must be wrapped in
 * `useCallback` at the call site, which is where the question "what does this
 * request actually depend on?" can be answered. A hook that took its own
 * dependency array would take one the linter cannot check, and an unchecked
 * dependency array is how a screen quietly stops refetching when a filter
 * changes.
 */
export interface Resource<T> {
  readonly data: T | null;
  readonly error: ApiError | null;
  readonly loading: boolean;
  readonly reload: () => void;
}

interface Attempt<T> {
  /** The `load` this result belongs to; identity changes when its inputs do. */
  readonly source: unknown;
  /** Bumped by `reload()` so a repeat of the same request is a new attempt. */
  readonly token: object;
  readonly data: T | null;
  readonly error: ApiError | null;
}

const INITIAL_TOKEN = {};

export function useResource<T>(load: () => Promise<T>): Resource<T> {
  const [attempt, setAttempt] = useState<Attempt<T>>({
    source: null,
    token: INITIAL_TOKEN,
    data: null,
    error: null,
  });
  const [token, setToken] = useState<object>(INITIAL_TOKEN);

  /*
   * Derived, not stored.
   *
   * Setting a `loading` flag inside the effect would leave one render in which
   * the screen shows the *previous* request's data as though it were current —
   * an operator changing a filter would see the old rows settle before the new
   * ones arrive, which reads as the filter having done nothing.
   */
  const loading = attempt.source !== load || attempt.token !== token;

  useEffect(() => {
    let live = true;

    load()
      .then((data) => {
        if (live) {
          setAttempt({ source: load, token, data, error: null });
        }
      })
      .catch((caught: unknown) => {
        if (!live) {
          return;
        }

        /*
         * Only an ApiError is surfaced as a failed request. Anything else is a
         * defect in this application rather than a response from the server,
         * and folding it into an error banner would hide a bug behind a message
         * that blames the API.
         */
        if (caught instanceof ApiError) {
          setAttempt({ source: load, token, data: null, error: caught });

          return;
        }

        throw caught;
      });

    return () => {
      // Stops a slow response from an abandoned request overwriting a newer one.
      live = false;
    };
  }, [load, token]);

  const reload = useCallback(() => {
    setToken({});
  }, []);

  return { data: attempt.data, error: attempt.error, loading, reload };
}
