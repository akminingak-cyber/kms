import { useCallback } from 'react';

import { useSession } from '../api/SessionProvider.js';
import { useResource } from '../api/useResource.js';
import { DataTable } from '../components/DataTable.js';
import { Screen } from '../components/Screen.js';
import { formatInstant, type AdminSession } from '../types.js';

export function Sessions() {
  const { client } = useSession();

  const sessions = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/playback/sessions', {
        params: { query: { limit: 100 } },
      });

      return data?.data ?? [];
    }, [client]),
  );

  return (
    <Screen
      title="Sessions"
      intro={
        <p>
          What is playing right now. This is the other half of a concurrency complaint: a viewer
          told they have too many streams needs someone to be able to see what those streams are.
        </p>
      }
      error={sessions.error}
      onRetry={sessions.reload}
    >
      <DataTable<AdminSession>
        loading={sessions.loading}
        rows={sessions.data ?? []}
        empty="Nothing is playing."
        columns={[
          { header: 'Started', render: (row) => formatInstant(row.started_at) },
          { header: 'Account', render: (row) => <code>{row.account_id}</code> },
          { header: 'Content', render: (row) => <code>{row.content_id}</code> },
          { header: 'Mode', render: (row) => row.mode },
          { header: 'Device', render: (row) => row.device_class },
          {
            header: 'Last beat',
            render: (row) => formatInstant(row.last_heartbeat_at),
          },
          {
            header: 'Health',
            /*
             * A session still marked live whose heartbeat lapsed is what a
             * leaked concurrency slot looks like. Flagged rather than left for
             * the reader to work out from two timestamps, because nobody does
             * that arithmetic while a viewer is waiting.
             */
            render: (row) =>
              row.heartbeat_lapsed === true ? (
                <span className="badge badge--deny">heartbeat lapsed</span>
              ) : (
                <span className="badge badge--allow">live</span>
              ),
          },
        ]}
      />
    </Screen>
  );
}
