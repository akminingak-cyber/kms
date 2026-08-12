import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { Channels } from '../screens/Channels.js';
import { renderWithSession, staffMember, stubFetch } from '../test/harness.js';

const CHANNEL = {
  id: '019ff0f4-0000-7000-8000-0000000000c1',
  slug: 'one',
  name: 'Channel One',
  number: 1,
  status: 'active',
  category_id: null,
  catchup_enabled: false,
  created_at: '2026-08-11T12:00:00Z',
};

describe('Channels', () => {
  it('lists channels including ones a viewer cannot see', async () => {
    const { fetch } = stubFetch([
      [
        '/channels',
        {
          body: {
            data: [
              CHANNEL,
              {
                ...CHANNEL,
                id: 'x',
                slug: 'two',
                name: 'Channel Two',
                status: 'draft',
              },
            ],
            page: { next_cursor: null },
          },
        },
      ],
    ]);

    renderWithSession(<Channels />, {
      fetch,
      staff: staffMember('content_operator'),
    });

    expect(await screen.findByText('Channel One')).toBeDefined();
    // The status column is the point of this screen existing separately from
    // the client one.
    expect(screen.getByText('draft')).toBeDefined();
  });

  it('says so plainly when there is nothing rather than looking broken', async () => {
    const { fetch } = stubFetch([
      ['/channels', { body: { data: [], page: { next_cursor: null } } }],
    ]);

    renderWithSession(<Channels />, {
      fetch,
      staff: staffMember('content_operator'),
    });

    // Empty is not an error and is not loading. Conflating them is how an
    // operator concludes their data is gone.
    expect(await screen.findByText('No channels yet.')).toBeDefined();
  });

  it('shows the failure with its stable code and correlation id', async () => {
    const { fetch } = stubFetch([
      [
        '/channels',
        {
          status: 403,
          body: {
            title: 'Not permitted',
            status: 403,
            code: 'FORBIDDEN',
            correlation_id: 'corr-987',
          },
        },
      ],
    ]);

    renderWithSession(<Channels />, {
      fetch,
      staff: staffMember('support_agent'),
    });

    expect(await screen.findByRole('alert')).toBeDefined();
    // Both are what turn a screenshot into something investigable.
    expect(screen.getByText('FORBIDDEN')).toBeDefined();
    expect(screen.getByText('corr-987')).toBeDefined();
  });

  it('hides the create form from a role that could not use it', async () => {
    const { fetch } = stubFetch([['/channels', { body: { data: [CHANNEL], page: {} } }]]);

    renderWithSession(<Channels />, {
      fetch,
      staff: staffMember('support_agent'),
    });

    await screen.findByText('Channel One');
    // Usability, not enforcement: the server refuses this role regardless.
    expect(screen.queryByRole('button', { name: 'Create channel' })).toBeNull();
  });

  it('shows the server’s own validation message against the offending field', async () => {
    const { fetch } = stubFetch([['/channels', { body: { data: [], page: {} } }]]);

    let posted = false;
    const stubbed = (async (input: RequestInfo | URL) => {
      const request = input as Request;

      if (request.method === 'POST') {
        posted = true;

        return new Response(
          JSON.stringify({
            title: 'Validation failed',
            status: 422,
            code: 'VALIDATION_FAILED',
            correlation_id: 'corr-1',
            errors: { slug: ['The slug has already been taken.'] },
          }),
          {
            status: 422,
            headers: { 'Content-Type': 'application/problem+json' },
          },
        );
      }

      return fetch(input as never);
    }) as unknown as typeof globalThis.fetch;

    renderWithSession(<Channels />, {
      fetch: stubbed,
      staff: staffMember('platform_engineer'),
    });

    await screen.findByRole('button', { name: 'Create channel' });
    await userEvent.type(screen.getByLabelText('Slug'), 'one');
    await userEvent.type(screen.getByLabelText('Name'), 'Channel One');
    await userEvent.click(screen.getByRole('button', { name: 'Create channel' }));

    await waitFor(() => expect(posted).toBe(true));
    // The server's message, not a re-implementation of the rule. A client that
    // decides for itself what "already taken" means eventually disagrees with
    // the server and tells the operator something untrue.
    expect(await screen.findByText('The slug has already been taken.')).toBeDefined();
  });
});
