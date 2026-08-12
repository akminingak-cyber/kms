import { useCallback } from 'react';

import { useSession } from '../api/SessionProvider.js';
import { useResource } from '../api/useResource.js';
import { DataTable } from '../components/DataTable.js';
import { Screen } from '../components/Screen.js';
import { formatMoney, type AdminPackage, type AdminPlan } from '../types.js';

export function Plans() {
  const { client } = useSession();

  const plans = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/plans', {
        params: { query: { limit: 100 } },
      });

      return data?.data ?? [];
    }, [client]),
  );

  const packages = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/packages', {
        params: { query: { limit: 100 } },
      });

      return data?.data ?? [];
    }, [client]),
  );

  return (
    <Screen
      title="Plans & packages"
      intro={
        <p>
          What is sold, and what it includes. Prices shown are the ones in effect now — historical
          prices matter to finance and to a dispute, and are not what you are looking at when you
          ask what a plan costs.
        </p>
      }
      error={plans.error ?? packages.error}
      onRetry={() => {
        plans.reload();
        packages.reload();
      }}
    >
      <h2>Plans</h2>
      <DataTable<AdminPlan>
        loading={plans.loading}
        rows={plans.data ?? []}
        empty="No plans yet."
        columns={[
          { header: 'Name', render: (row) => row.name },
          { header: 'Package', render: (row) => row.package?.name ?? '—' },
          { header: 'Period', render: (row) => row.billing_period },
          { header: 'Price', render: (row) => formatMoney(row.price) },
          { header: 'Devices', render: (row) => row.device_limit },
          { header: 'Streams', render: (row) => row.concurrency_limit },
          {
            header: 'Quality cap',
            /*
             * Null means the plan caps nothing of its own. A licensor's cap
             * still applies on top at playback, and the stricter of the two
             * wins — so "no cap" here does not mean the viewer gets everything.
             */
            render: (row) => row.max_resolution ?? <span className="muted">none of its own</span>,
          },
        ]}
      />

      <h2>Packages</h2>
      <DataTable<AdminPackage>
        loading={packages.loading}
        rows={packages.data ?? []}
        empty="No packages yet."
        columns={[
          { header: 'Name', render: (row) => row.name },
          { header: 'Slug', render: (row) => <code>{row.slug}</code> },
          { header: 'Status', render: (row) => row.status },
          { header: 'Channels', render: (row) => row.channel_ids?.length ?? 0 },
        ]}
      />
    </Screen>
  );
}
