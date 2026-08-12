import type { ReactNode } from 'react';

/**
 * A labelled input that can show the server's own validation message.
 *
 * The server's message is shown rather than a re-implementation of the rule.
 * A client that decides for itself what "slug already taken" means will
 * eventually disagree with the server about it, and the operator is then told
 * something untrue by an application that looked more helpful.
 */
export function Field({
  name,
  label,
  errors,
  hint,
  children,
}: {
  readonly name: string;
  readonly label: string;
  readonly errors?: readonly string[];
  readonly hint?: string;
  readonly children: ReactNode;
}) {
  const errorId = `${name}-error`;
  const hasError = errors !== undefined && errors.length > 0;

  return (
    <div className={hasError ? 'field field--invalid' : 'field'}>
      <label htmlFor={name}>{label}</label>
      {hint !== undefined && <p className="field__hint">{hint}</p>}
      {children}
      {hasError && (
        <p className="field__error" id={errorId} role="alert">
          {errors.join(' ')}
        </p>
      )}
    </div>
  );
}
