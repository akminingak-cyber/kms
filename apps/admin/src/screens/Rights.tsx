import { ApiError } from '@kms/ts-api-client';
import { type FormEvent, useCallback, useState } from 'react';

import { useSession } from '../api/SessionProvider.js';
import { useResource } from '../api/useResource.js';
import { DataTable } from '../components/DataTable.js';
import { Field } from '../components/Field.js';
import { ProblemNotice } from '../components/ProblemNotice.js';
import { Screen } from '../components/Screen.js';
import { formatInstant, type Blackout, type Right } from '../types.js';

type Verdict = {
  permitted?: boolean;
  reason?: string | null;
  availability_version?: number | null;
};

export function Rights() {
  const { client } = useSession();
  const [verdict, setVerdict] = useState<Verdict | null>(null);
  const [previewError, setPreviewError] = useState<ApiError | null>(null);
  const [blackoutError, setBlackoutError] = useState<ApiError | null>(null);

  const rights = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/rights/rights', {
        params: { query: { limit: 100 } },
      });

      return data?.data ?? [];
    }, [client]),
  );

  const blackouts = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/rights/blackouts', {
        params: { query: { limit: 50 } },
      });

      return data?.data ?? [];
    }, [client]),
  );

  async function onPreview(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const values = new FormData(event.currentTarget);
    setPreviewError(null);
    setVerdict(null);

    try {
      const { data } = await client.POST('/rights/availability/preview', {
        body: {
          subject_type: 'channel',
          subject_id: String(values.get('subject_id') ?? ''),
          exploitation: String(values.get('exploitation') ?? 'live') as 'live',
          territory: String(values.get('territory') ?? '').toUpperCase(),
          platform: String(values.get('platform') ?? 'web'),
        },
      });

      setVerdict(data?.data ?? null);
    } catch (caught) {
      if (caught instanceof ApiError) {
        setPreviewError(caught);

        return;
      }

      throw caught;
    }
  }

  async function onBlackout(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const form = event.currentTarget;
    const values = new FormData(form);
    setBlackoutError(null);

    try {
      await client.POST('/rights/blackouts', {
        body: {
          subject_type: 'channel',
          subject_id: String(values.get('subject_id') ?? ''),
          starts_at: new Date(String(values.get('starts_at') ?? '')).toISOString(),
          ends_at: new Date(String(values.get('ends_at') ?? '')).toISOString(),
          reason: String(values.get('reason') ?? ''),
        },
      });

      form.reset();
      blackouts.reload();
    } catch (caught) {
      if (caught instanceof ApiError) {
        setBlackoutError(caught);

        return;
      }

      throw caught;
    }
  }

  return (
    <Screen
      title="Rights"
      intro={
        <p>
          Rights are superseded rather than edited, so a past decision stays explainable. Superseded
          entries are listed and marked, because "why was this denied on the 3rd?" needs the rule
          that applied then — not the one that applies now.
        </p>
      }
      error={rights.error ?? blackouts.error}
      onRetry={() => {
        rights.reload();
        blackouts.reload();
      }}
    >
      <form className="panel" onSubmit={onPreview} noValidate>
        <h2>Preview availability</h2>
        <p className="muted">
          Runs the same resolver playback uses and returns the same verdict and reason. A separate
          simulation could drift from the real thing, which would be worse than having none. Nothing
          is recorded and nobody is granted anything.
        </p>

        <Field name="subject_id" label="Channel id">
          <input id="subject_id" name="subject_id" required />
        </Field>

        <Field name="territory" label="Territory" hint="Two-letter code, e.g. GB.">
          <input id="territory" name="territory" required maxLength={2} />
        </Field>

        <Field name="platform" label="Device class">
          <select id="platform" name="platform" defaultValue="web">
            <option value="web">web</option>
            <option value="mobile">mobile</option>
            <option value="tablet">tablet</option>
            <option value="androidtv">androidtv</option>
            <option value="tizen">tizen</option>
            <option value="webos">webos</option>
            <option value="stb">stb</option>
          </select>
        </Field>

        <Field name="exploitation" label="Mode">
          <select id="exploitation" name="exploitation" defaultValue="live">
            <option value="live">live</option>
            <option value="restart">restart</option>
            <option value="catchup">catchup</option>
            <option value="vod">vod</option>
          </select>
        </Field>

        <button type="submit">Preview</button>

        {previewError !== null && <ProblemNotice error={previewError} />}

        {verdict !== null && (
          <p
            className={verdict.permitted === true ? 'notice notice--ok' : 'notice notice--error'}
            role="status"
          >
            {verdict.permitted === true ? 'Permitted' : 'Denied'}
            {verdict.reason !== null && verdict.reason !== undefined && (
              <>
                {' '}
                — <code>{verdict.reason}</code>
              </>
            )}
          </p>
        )}
      </form>

      <h2>Rights</h2>
      <DataTable<Right>
        loading={rights.loading}
        rows={rights.data ?? []}
        empty="No rights recorded. Nothing is playable until they are: absence of a right is a prohibition."
        columns={[
          {
            header: 'Agreement',
            render: (row) => row.agreement?.counterparty ?? '—',
          },
          { header: 'Subject', render: (row) => <code>{row.subject_id}</code> },
          { header: 'Mode', render: (row) => row.exploitation },
          { header: 'From', render: (row) => formatInstant(row.window_start) },
          {
            header: 'Until',
            // Null is an open-ended window, not a missing value.
            render: (row) =>
              row.window_end === null || row.window_end === undefined ? (
                <span className="muted">open-ended</span>
              ) : (
                formatInstant(row.window_end)
              ),
          },
          {
            header: 'Platforms',
            render: (row) => (row.platforms ?? []).join(', ') || '—',
          },
          {
            header: 'Quality cap',
            render: (row) => row.usage_rules?.max_resolution ?? <span className="muted">none</span>,
          },
          {
            header: 'State',
            render: (row) =>
              row.superseded_at === null || row.superseded_at === undefined ? (
                <span className="badge badge--allow">current</span>
              ) : (
                <span className="badge">superseded</span>
              ),
          },
        ]}
      />

      <h2>Blackouts</h2>
      <p className="muted">
        Never projected — read live at decision time, so one takes effect immediately for new
        sessions and within the revalidation interval for sessions already playing.
      </p>
      <DataTable<Blackout>
        loading={blackouts.loading}
        rows={blackouts.data ?? []}
        empty="No blackouts."
        columns={[
          { header: 'Subject', render: (row) => <code>{row.subject_id}</code> },
          { header: 'From', render: (row) => formatInstant(row.starts_at) },
          { header: 'Until', render: (row) => formatInstant(row.ends_at) },
          { header: 'Reason', render: (row) => row.reason ?? '—' },
          {
            header: 'Lifted',
            render: (row) =>
              row.lifted_at === null || row.lifted_at === undefined
                ? '—'
                : formatInstant(row.lifted_at),
          },
        ]}
      />

      <form className="panel" onSubmit={onBlackout} noValidate>
        <h3>Add a blackout</h3>

        <Field
          name="blackout_subject"
          label="Channel id"
          errors={blackoutError?.fieldErrors().subject_id}
        >
          <input id="blackout_subject" name="subject_id" required />
        </Field>

        <Field name="starts_at" label="Starts" errors={blackoutError?.fieldErrors().starts_at}>
          <input id="starts_at" name="starts_at" type="datetime-local" required />
        </Field>

        <Field name="ends_at" label="Ends" errors={blackoutError?.fieldErrors().ends_at}>
          <input id="ends_at" name="ends_at" type="datetime-local" required />
        </Field>

        <Field name="reason" label="Reason" errors={blackoutError?.fieldErrors().reason}>
          <input id="reason" name="reason" required maxLength={200} />
        </Field>

        {blackoutError !== null && <ProblemNotice error={blackoutError} />}

        <button type="submit">Create blackout</button>
      </form>
    </Screen>
  );
}
