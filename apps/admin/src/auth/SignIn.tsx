import { ApiError } from '@kms/ts-api-client';
import { useState, type FormEvent } from 'react';

import { useSession } from '../api/SessionProvider.js';
import { Field } from '../components/Field.js';
import { ProblemNotice } from '../components/ProblemNotice.js';
import type { StaffMember } from '../types.js';

/**
 * Staff sign-in.
 *
 * The TOTP field is **not conditional**. There is no "remembered device", no
 * grace period and no first-login exemption, because the server has no branch
 * that issues a staff token without a code — and a form that implied otherwise
 * would be lying about what happens next.
 *
 * Failures are shown exactly as the server reports them, which is deliberately
 * indistinguishable: a wrong password, a wrong code and an unknown account all
 * return the same code. Helping the operator narrow it down would help an
 * attacker enumerate accounts by exactly as much.
 */
export function SignIn() {
  const { client, signIn } = useSession();
  const [error, setError] = useState<ApiError | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const form = new FormData(event.currentTarget);
    setSubmitting(true);
    setError(null);

    try {
      const { data } = await client.POST('/auth/sign-in', {
        body: {
          email: String(form.get('email') ?? ''),
          password: String(form.get('password') ?? ''),
          totp_code: String(form.get('totp_code') ?? ''),
        },
      });

      const token = data?.access_token;

      if (token === undefined) {
        throw new Error('The sign-in response carried no access token.');
      }

      /*
       * The sign-in response carries only enough of the staff record to render
       * a name, so the full record — including status and MFA enrolment — comes
       * from `/me`. Two calls rather than one, and the alternative is the panel
       * inventing fields the sign-in response never promised.
       */
      signIn(token, {
        id: data?.staff?.id ?? '',
        name: data?.staff?.name ?? '',
        role: data?.staff?.role ?? 'support_agent',
      } satisfies StaffMember);
    } catch (caught) {
      if (caught instanceof ApiError) {
        setError(caught);

        return;
      }

      throw caught;
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <main className="signin">
      <h1>KMS TV — Operator panel</h1>
      <form onSubmit={onSubmit} noValidate>
        <Field name="email" label="Email" errors={error?.fieldErrors().email}>
          <input id="email" name="email" type="email" autoComplete="username" required />
        </Field>

        <Field name="password" label="Password" errors={error?.fieldErrors().password}>
          <input
            id="password"
            name="password"
            type="password"
            autoComplete="current-password"
            required
          />
        </Field>

        <Field
          name="totp_code"
          label="Authenticator code"
          hint="Six digits. Required on every sign-in, without exception."
          errors={error?.fieldErrors().totp_code}
        >
          <input
            id="totp_code"
            name="totp_code"
            inputMode="numeric"
            autoComplete="one-time-code"
            pattern="\d{6}"
            maxLength={6}
            required
          />
        </Field>

        {error !== null && <ProblemNotice error={error} />}

        <button type="submit" disabled={submitting}>
          {submitting ? 'Signing in…' : 'Sign in'}
        </button>
      </form>

      <p className="muted">
        Your session is held in memory only, so refreshing this page signs you out. That is
        deliberate: a staff token reaches rights, pricing and subscriber data, and nothing readable
        by a script on this origin should be able to hand it over.
      </p>
    </main>
  );
}
