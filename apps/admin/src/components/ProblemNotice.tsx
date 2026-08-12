import type { ApiError } from '@kms/ts-api-client';

/**
 * A failed request, shown to an operator.
 *
 * Two things are non-negotiable here.
 *
 * The **stable code** is displayed, not just the title. Support and engineering
 * both search on it, and it is the one part of a problem document guaranteed
 * not to change wording between releases.
 *
 * The **correlation id** is displayed. It turns a screenshot into a searchable
 * request, which is the difference between an incident someone can investigate
 * and one where the only evidence is a person's recollection.
 */
export function ProblemNotice({ error, onRetry }: { error: ApiError; onRetry?: () => void }) {
  return (
    <div className="notice notice--error" role="alert">
      <p className="notice__title">{error.problem.title}</p>
      {error.problem.detail !== undefined && <p>{error.problem.detail}</p>}
      <dl className="notice__meta">
        <dt>Code</dt>
        <dd>
          <code>{error.code}</code>
        </dd>
        <dt>Correlation id</dt>
        <dd>
          <code>{error.correlationId === '' ? 'not reported' : error.correlationId}</code>
        </dd>
      </dl>
      {onRetry !== undefined && (
        <button type="button" onClick={onRetry}>
          Try again
        </button>
      )}
    </div>
  );
}
