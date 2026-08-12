import { useCallback } from 'react';

import { useSession } from '../api/SessionProvider.js';
import { useResource } from '../api/useResource.js';
import { DataTable } from '../components/DataTable.js';
import { Screen } from '../components/Screen.js';
import { formatInstant, type AuditEntry } from '../types.js';

export function Audit() {
  const { client } = useSession();

  const entries = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/audit', {
        params: { query: { limit: 100 } },
      });

      return data?.data ?? [];
    }, [client]),
  );

  return (
    <Screen
      title="Audit"
      intro={
        <p>
          Who changed what. Append-only, and paged by cursor rather than by offset — on a table with
          continuous inserts, offset paging lets an investigator skip past an entry without ever
          seeing it.
        </p>
      }
      error={entries.error}
      onRetry={entries.reload}
    >
      <DataTable<AuditEntry>
        loading={entries.loading}
        rows={entries.data ?? []}
        empty="No audit entries."
        columns={[
          { header: 'When', render: (row) => formatInstant(row.occurred_at) },
          { header: 'Action', render: (row) => <code>{row.action}</code> },
          {
            header: 'Actor',
            render: (row) =>
              `${row.actor_type}${row.actor_id === null ? '' : ` · ${row.actor_id}`}`,
          },
          {
            header: 'Subject',
            render: (row) =>
              row.subject_type === null ? '—' : `${row.subject_type} · ${row.subject_id ?? ''}`,
          },
          {
            header: 'Context',
            render: (row) =>
              row.context === undefined || row.context === null ? (
                '—'
              ) : (
                <code className="context">{JSON.stringify(row.context)}</code>
              ),
          },
        ]}
      />
    </Screen>
  );
}
