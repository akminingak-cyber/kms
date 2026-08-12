import { useCallback } from 'react';

import { useSession } from '../api/SessionProvider.js';
import { useResource } from '../api/useResource.js';
import { DataTable } from '../components/DataTable.js';
import { Screen } from '../components/Screen.js';
import type { Category } from '../types.js';

export function Categories() {
  const { client } = useSession();

  const categories = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/categories', {
        params: { query: { limit: 100 } },
      });

      return data?.data ?? [];
    }, [client]),
  );

  return (
    <Screen
      title="Categories"
      intro={<p>The content taxonomy, in the order viewers see it.</p>}
      error={categories.error}
      onRetry={categories.reload}
    >
      <DataTable<Category>
        loading={categories.loading}
        rows={categories.data ?? []}
        empty="No categories yet."
        columns={[
          { header: 'Order', render: (row) => row.sort_order ?? '—' },
          { header: 'Name', render: (row) => row.name },
          { header: 'Slug', render: (row) => <code>{row.slug}</code> },
          {
            header: 'Parent',
            render: (row) => row.parent_id ?? <span className="muted">top level</span>,
          },
        ]}
      />
    </Screen>
  );
}
