import type { ApiError } from '@kms/ts-api-client';
import type { ReactNode } from 'react';

import { ProblemNotice } from './ProblemNotice.js';

/**
 * The shell every screen shares: a title, an explanation, and one place where
 * a failed request appears.
 *
 * The explanation is not decoration. Several of these screens answer a question
 * that is not obvious from a table of rows — why a decision log exists at all,
 * or why a channel list here differs from the one in the app — and an operator
 * who does not know what a screen is for uses it wrongly or not at all.
 */
export function Screen({
  title,
  intro,
  error,
  onRetry,
  actions,
  children,
}: {
  readonly title: string;
  readonly intro?: ReactNode;
  readonly error?: ApiError | null;
  readonly onRetry?: () => void;
  readonly actions?: ReactNode;
  readonly children: ReactNode;
}) {
  return (
    <section className="screen">
      <div className="screen__head">
        <h1>{title}</h1>
        {actions}
      </div>
      {intro !== undefined && <div className="screen__intro">{intro}</div>}
      {error !== null && error !== undefined && <ProblemNotice error={error} onRetry={onRetry} />}
      {children}
    </section>
  );
}

/**
 * Cursor paging.
 *
 * Forward only, because the API is cursor-paginated and a cursor has no
 * inverse. Offering a "previous" button that re-fetched from the start would
 * be a lie about what the API can do, and offering page numbers would be a
 * bigger one — there is no total, deliberately.
 */
export function MoreButton({
  cursor,
  loading,
  onMore,
}: {
  readonly cursor: string | null | undefined;
  readonly loading: boolean;
  readonly onMore: (cursor: string) => void;
}) {
  if (cursor === null || cursor === undefined || cursor === '') {
    return null;
  }

  return (
    <button type="button" disabled={loading} onClick={() => onMore(cursor)}>
      {loading ? 'Loading…' : 'Load more'}
    </button>
  );
}
