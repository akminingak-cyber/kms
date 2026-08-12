import { ApiError } from '@kms/ts-api-client';
import { type FormEvent, useCallback, useState } from 'react';

import { useSession } from '../api/SessionProvider.js';
import { useResource } from '../api/useResource.js';
import { DataTable } from '../components/DataTable.js';
import { Field } from '../components/Field.js';
import { ProblemNotice } from '../components/ProblemNotice.js';
import { Screen } from '../components/Screen.js';
import { formatInstant, type AdminChannel } from '../types.js';

export function Channels() {
  const { client, staff } = useSession();
  const [createError, setCreateError] = useState<ApiError | null>(null);
  const [created, setCreated] = useState<string | null>(null);

  const channels = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/channels', {
        params: { query: { limit: 100 } },
      });

      return data?.data ?? [];
    }, [client]),
  );

  // Mirrors the API's own rule. It hides a form the caller would be refused at;
  // it does not decide anything.
  const mayCreate = staff?.role === 'content_operator' || staff?.role === 'platform_engineer';

  async function onCreate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const form = event.currentTarget;
    const values = new FormData(form);
    const number = String(values.get('number') ?? '').trim();

    setCreateError(null);
    setCreated(null);

    try {
      const { data } = await client.POST('/channels', {
        body: {
          slug: String(values.get('slug') ?? ''),
          name: String(values.get('name') ?? ''),
          ...(number === '' ? {} : { number: Number(number) }),
          // Sent explicitly rather than omitted, so the form states the
          // platform's default rather than relying on it: a channel may not be
          // recorded until somebody says the recording right exists.
          catchup_enabled: values.get('catchup_enabled') === 'on',
        },
      });

      setCreated(data?.data?.slug ?? null);
      form.reset();
      channels.reload();
    } catch (caught) {
      if (caught instanceof ApiError) {
        setCreateError(caught);

        return;
      }

      throw caught;
    }
  }

  return (
    <Screen
      title="Channels"
      intro={
        <p>
          Every channel, including the ones a viewer cannot see. This is deliberately not the list
          the apps receive — that one is filtered to what is playable, so if a channel is missing
          from the app it is the rows that filter removes that explain why.
        </p>
      }
      error={channels.error}
      onRetry={channels.reload}
    >
      <DataTable<AdminChannel>
        loading={channels.loading}
        rows={channels.data ?? []}
        empty="No channels yet."
        columns={[
          { header: 'No.', render: (row) => row.number ?? '—' },
          { header: 'Name', render: (row) => row.name },
          { header: 'Slug', render: (row) => <code>{row.slug}</code> },
          {
            header: 'Status',
            render: (row) => <span className="badge">{row.status}</span>,
          },
          {
            header: 'Catch-up',
            render: (row) =>
              row.catchup_enabled === true ? 'Enabled' : <span className="muted">Off</span>,
          },
          { header: 'Created', render: (row) => formatInstant(row.created_at) },
        ]}
      />

      {mayCreate && (
        <form className="panel" onSubmit={onCreate} noValidate>
          <h2>Add a channel</h2>

          <Field name="slug" label="Slug" errors={createError?.fieldErrors().slug}>
            <input id="slug" name="slug" required maxLength={60} />
          </Field>

          <Field name="name" label="Name" errors={createError?.fieldErrors().name}>
            <input id="name" name="name" required maxLength={120} />
          </Field>

          <Field name="number" label="Channel number" errors={createError?.fieldErrors().number}>
            <input id="number" name="number" inputMode="numeric" />
          </Field>

          <Field
            name="catchup_enabled"
            label="Catch-up"
            hint="Leave off unless a recording right exists. Content without one must never reach the buffer."
          >
            <input id="catchup_enabled" name="catchup_enabled" type="checkbox" />
          </Field>

          {createError !== null && <ProblemNotice error={createError} />}
          {created !== null && (
            <p className="notice notice--ok" role="status">
              Created <code>{created}</code>. It will not be playable until rights exist for it.
            </p>
          )}

          <button type="submit">Create channel</button>
        </form>
      )}
    </Screen>
  );
}
