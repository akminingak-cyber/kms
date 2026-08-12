/**
 * RFC 9457 problem documents, validated where they enter the application.
 *
 * Every failure from either API leaves as a problem document with a **stable
 * machine-readable `code`**. Clients switch on that code and never on prose:
 * the title is not localised, it is not stable, and building behaviour on it
 * means a copy edit changes what the application does.
 *
 * The shape is checked rather than asserted. A response body is data that
 * crossed a network boundary — a proxy error page, a truncated response or a
 * misconfigured gateway can all arrive where a problem document was expected,
 * and casting one of those to a typed object produces `undefined` deep inside
 * rendering code rather than an error at the boundary.
 */

/** A validated problem document. */
export interface ApiProblem {
  readonly type: string;
  readonly title: string;
  readonly status: number;
  readonly code: string;
  readonly detail?: string;
  readonly instance?: string;
  readonly correlationId: string;
  /** Field-level validation failures, when the code is `VALIDATION_FAILED`. */
  readonly errors?: Readonly<Record<string, readonly string[]>>;
}

/**
 * The error every call rejects with.
 *
 * Carries the correlation id because that is what turns a screenshot into a
 * searchable request, which is the difference between a support ticket that can
 * be investigated and one that cannot.
 */
export class ApiError extends Error {
  constructor(
    readonly problem: ApiProblem,
    readonly response: Response,
  ) {
    super(`${problem.code}: ${problem.title}`);
    this.name = 'ApiError';
  }

  get code(): string {
    return this.problem.code;
  }

  get status(): number {
    return this.problem.status;
  }

  get correlationId(): string {
    return this.problem.correlationId;
  }

  /** Field errors for a form, empty when the failure was not a validation failure. */
  fieldErrors(): Readonly<Record<string, readonly string[]>> {
    return this.problem.errors ?? {};
  }
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function stringArray(value: unknown): readonly string[] | undefined {
  if (!Array.isArray(value)) {
    return undefined;
  }

  const strings = value.filter((entry): entry is string => typeof entry === 'string');

  return strings.length === value.length ? strings : undefined;
}

function fieldErrors(value: unknown): Record<string, readonly string[]> | undefined {
  if (!isRecord(value)) {
    return undefined;
  }

  const errors: Record<string, readonly string[]> = {};

  for (const [field, messages] of Object.entries(value)) {
    const parsed = stringArray(messages);

    if (parsed !== undefined) {
      errors[field] = parsed;
    }
  }

  return Object.keys(errors).length > 0 ? errors : undefined;
}

/**
 * Parses a response body into a problem document.
 *
 * Returns a synthetic document when the body is not one, rather than throwing.
 * A gateway timeout that returns HTML is still a failure the user has to be
 * told about, and turning it into "cannot parse error" loses the status code
 * that would have explained it.
 */
export function toProblem(body: unknown, response: Response): ApiProblem {
  if (
    isRecord(body) &&
    typeof body.code === 'string' &&
    typeof body.title === 'string' &&
    typeof body.status === 'number'
  ) {
    return {
      type: typeof body.type === 'string' ? body.type : 'about:blank',
      title: body.title,
      status: body.status,
      code: body.code,
      detail: typeof body.detail === 'string' ? body.detail : undefined,
      instance: typeof body.instance === 'string' ? body.instance : undefined,
      correlationId: typeof body.correlation_id === 'string' ? body.correlation_id : '',
      errors: fieldErrors(body.errors),
    };
  }

  return {
    type: 'about:blank',
    title: response.statusText || 'Request failed',
    status: response.status,
    /*
     * A code of our own, because the alternative is no code at all. Anything
     * switching on codes needs a value here, and inventing a plausible platform
     * code — `INTERNAL_ERROR`, say — would misreport an upstream proxy failure
     * as one of ours.
     */
    code: response.status === 0 ? 'CLIENT_NETWORK_ERROR' : 'CLIENT_UNPARSEABLE_ERROR',
    correlationId: response.headers.get('X-Correlation-Id') ?? '',
  };
}
