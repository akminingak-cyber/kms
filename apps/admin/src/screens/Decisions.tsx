import { useCallback, useState } from 'react';

import { useSession } from '../api/SessionProvider.js';
import { useResource } from '../api/useResource.js';
import { DataTable } from '../components/DataTable.js';
import { Screen } from '../components/Screen.js';
import { formatInstant, type Decision } from '../types.js';

/**
 * The screen this whole panel is most worth building.
 *
 * "Why could this person not watch?" is the commonest support question an OTT
 * platform receives, and without a record it is answered by guessing — nobody
 * can reproduce a viewer's territory, device, subscription and moment. Every
 * decision was recorded with the inputs that produced it, so the answer is a
 * row rather than an investigation.
 */
export function Decisions() {
  const { client } = useSession();
  const [outcome, setOutcome] = useState<'' | 'allow' | 'deny'>('deny');
  const [accountId, setAccountId] = useState('');
  const [contentId, setContentId] = useState('');

  const decisions = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/playback/decisions', {
        params: {
          query: {
            limit: 50,
            ...(outcome === '' ? {} : { outcome }),
            ...(accountId === '' ? {} : { account_id: accountId }),
            ...(contentId === '' ? {} : { content_id: contentId }),
          },
        },
      });

      return data?.data ?? [];
    }, [client, outcome, accountId, contentId]),
  );

  return (
    <Screen
      title="Playback decisions"
      intro={
        <>
          <p>
            Every allow and every denial, with the inputs that produced it — the rights rules, the
            entitlement grants, the territory and how it was determined.
          </p>
          <p className="muted">
            Identifiers only. There is deliberately no search by name or email address: that would
            turn a diagnostic tool into a viewing-history search engine over every subscriber. Start
            from the account you are already talking to.
          </p>
        </>
      }
      error={decisions.error}
      onRetry={decisions.reload}
    >
      <div className="filters">
        <label htmlFor="outcome">Outcome</label>
        <select
          id="outcome"
          value={outcome}
          onChange={(event) => setOutcome(event.target.value as '' | 'allow' | 'deny')}
        >
          <option value="">Any</option>
          <option value="deny">Denied</option>
          <option value="allow">Allowed</option>
        </select>

        <label htmlFor="account_id">Account</label>
        <input
          id="account_id"
          value={accountId}
          placeholder="account id"
          onChange={(event) => setAccountId(event.target.value.trim())}
        />

        <label htmlFor="content_id">Content</label>
        <input
          id="content_id"
          value={contentId}
          placeholder="channel id"
          onChange={(event) => setContentId(event.target.value.trim())}
        />
      </div>

      <DataTable<Decision>
        loading={decisions.loading}
        rows={decisions.data ?? []}
        empty="No decisions match these filters."
        columns={[
          { header: 'When', render: (row) => formatInstant(row.decided_at) },
          {
            header: 'Outcome',
            render: (row) => (
              <span className={row.outcome === 'deny' ? 'badge badge--deny' : 'badge badge--allow'}>
                {row.outcome}
              </span>
            ),
          },
          {
            header: 'Reason',
            // The specific code, never "not available". A generic denial makes
            // support impossible and hides bugs.
            render: (row) => (row.reason_code === null ? '—' : <code>{row.reason_code}</code>),
          },
          {
            header: 'Content',
            render: (row) => <code>{row.content_id ?? '—'}</code>,
          },
          { header: 'Mode', render: (row) => row.mode ?? '—' },
          { header: 'Device', render: (row) => row.device_class ?? '—' },
          {
            header: 'Territory',
            render: (row) => (
              <>
                {row.territory ?? '—'}{' '}
                <span className="muted">({row.territory_method ?? 'unrecorded'})</span>
              </>
            ),
          },
          {
            header: 'Correlation',
            render: (row) => <code>{row.correlation_id ?? '—'}</code>,
          },
        ]}
      />
    </Screen>
  );
}
