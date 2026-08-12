import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { App } from '../App.js';
import { SignIn } from '../auth/SignIn.js';
import { renderWithSession, staffMember, stubFetch } from '../test/harness.js';

describe('SignIn', () => {
  it('always asks for an authenticator code', () => {
    const { fetch } = stubFetch([]);

    renderWithSession(<SignIn />, { fetch, staff: null });

    // Not conditional, and never will be: the server has no branch that issues
    // a staff token without one, so a form implying otherwise would be lying
    // about what happens next.
    const code = screen.getByLabelText('Authenticator code');
    expect(code.hasAttribute('required')).toBe(true);
  });

  it('shows the server’s refusal without helping narrow down which field was wrong', async () => {
    const { fetch } = stubFetch([
      [
        '/auth/sign-in',
        {
          status: 401,
          body: {
            title: 'Invalid credentials',
            status: 401,
            code: 'AUTH_INVALID_CREDENTIALS',
            correlation_id: 'corr-5',
          },
        },
      ],
    ]);

    renderWithSession(<SignIn />, { fetch, staff: null });

    await userEvent.type(screen.getByLabelText('Email'), 'operator@staff.test');
    await userEvent.type(screen.getByLabelText('Password'), 'wrong-password');
    await userEvent.type(screen.getByLabelText('Authenticator code'), '000000');
    await userEvent.click(screen.getByRole('button', { name: 'Sign in' }));

    expect(await screen.findByText('AUTH_INVALID_CREDENTIALS')).toBeDefined();
    /*
     * One message for a wrong password, a wrong code and an unknown account.
     * Telling the operator which it was would tell an attacker the same thing,
     * and account enumeration is the whole reason the server conflates them.
     */
    expect(screen.queryByText(/password is incorrect/i)).toBeNull();
    expect(screen.queryByText(/no such account/i)).toBeNull();
  });

  it('signs in and lands on the first section the role can use', async () => {
    const { fetch } = stubFetch([
      [
        '/auth/sign-in',
        {
          body: {
            access_token: 'staff-token',
            token_type: 'Bearer',
            expires_in: 900,
            staff: { id: 'id-1', name: 'Ops', role: 'support_agent' },
          },
        },
      ],
      ['/channels', { body: { data: [], page: {} } }],
    ]);

    renderWithSession(<App />, { fetch, staff: null });

    await userEvent.type(screen.getByLabelText('Email'), 'operator@staff.test');
    await userEvent.type(screen.getByLabelText('Password'), 'correct-password');
    await userEvent.type(screen.getByLabelText('Authenticator code'), '123456');
    await userEvent.click(screen.getByRole('button', { name: 'Sign in' }));

    await waitFor(() => expect(screen.getByText('Ops')).toBeDefined());
    // A support agent lands on Channels and is never shown Rights or Staff.
    expect(screen.getByRole('link', { name: 'Channels' })).toBeDefined();
    expect(screen.queryByRole('link', { name: 'Rights' })).toBeNull();
    expect(screen.queryByRole('link', { name: 'Staff' })).toBeNull();
  });
});

describe('session security', () => {
  it('keeps the token out of every browser storage', async () => {
    const { fetch, calls } = stubFetch([['/channels', { body: { data: [], page: {} } }]]);

    renderWithSession(<App />, {
      fetch,
      staff: staffMember('platform_engineer'),
    });

    await waitFor(() => expect(calls.length).toBeGreaterThan(0));

    // The token reaches the request…
    expect(calls[0]?.headers.get('Authorization')).toBe('Bearer staff-token');

    /*
     * …and nowhere a script can read it later. A staff token reaches rights,
     * pricing and subscriber data, so a single XSS anywhere on this origin
     * must not be able to walk away with a working credential.
     */
    expect(window.localStorage.length).toBe(0);
    expect(window.sessionStorage.length).toBe(0);
    expect(document.cookie).toBe('');
  });

  it('signs the operator out when the API reports a dead token', async () => {
    const { fetch } = stubFetch([
      [
        '/channels',
        {
          status: 401,
          body: {
            title: 'Access token revoked',
            status: 401,
            code: 'AUTH_TOKEN_REVOKED',
            correlation_id: 'corr-9',
          },
        },
      ],
    ]);

    renderWithSession(<App />, {
      fetch,
      staff: staffMember('platform_engineer'),
    });

    // Back to sign-in rather than sitting on a screen whose every request fails.
    expect(await screen.findByRole('button', { name: 'Sign in' })).toBeDefined();
  });

  it('stays signed in when a request is merely refused by role', async () => {
    const { fetch } = stubFetch([
      [
        '/channels',
        {
          status: 403,
          body: {
            title: 'Not permitted',
            status: 403,
            code: 'FORBIDDEN',
            correlation_id: 'corr-9',
          },
        },
      ],
    ]);

    renderWithSession(<App />, {
      fetch,
      staff: staffMember('platform_engineer'),
    });

    expect(await screen.findByRole('alert')).toBeDefined();
    // Clicking something you are not allowed to do is not a credential problem,
    // and signing people out for it would be maddening.
    expect(screen.queryByRole('button', { name: 'Sign in' })).toBeNull();
  });
});
