import { useCallback } from 'react';

import { useSession } from '../api/SessionProvider.js';
import { useResource } from '../api/useResource.js';
import { DataTable } from '../components/DataTable.js';
import { Screen } from '../components/Screen.js';
import { formatInstant, type StaffMember } from '../types.js';

export function Staff() {
  const { client } = useSession();

  const staff = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/staff', {
        params: { query: { limit: 100 } },
      });

      return data?.data ?? [];
    }, [client]),
  );

  return (
    <Screen
      title="Staff"
      intro={<p>Who has access. An access review is not possible without a list.</p>}
      error={staff.error}
      onRetry={staff.reload}
    >
      <DataTable<StaffMember>
        loading={staff.loading}
        rows={staff.data ?? []}
        empty="No staff accounts."
        columns={[
          { header: 'Name', render: (row) => row.name },
          { header: 'Email', render: (row) => row.email },
          {
            header: 'Role',
            render: (row) => <span className="badge">{row.role?.replace(/_/g, ' ')}</span>,
          },
          { header: 'Status', render: (row) => row.status },
          {
            header: 'MFA',
            /*
             * Staff multi-factor is mandatory, so a row without it is an
             * anomaly worth seeing rather than a field worth hiding.
             */
            render: (row) =>
              row.mfa_enrolled === true ? (
                'Enrolled'
              ) : (
                <span className="badge badge--deny">not enrolled</span>
              ),
          },
          {
            header: 'Last sign-in',
            render: (row) => formatInstant(row.last_login_at),
          },
        ]}
      />
    </Screen>
  );
}
