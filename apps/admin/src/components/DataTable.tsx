import type { ReactNode } from 'react';

export interface Column<T> {
  readonly header: string;
  readonly render: (row: T) => ReactNode;
}

/**
 * A list, with the two states people forget.
 *
 * **Empty is not the same as loading**, and conflating them is how an operator
 * concludes their data is gone when it is merely slow. **Empty is also not an
 * error**: a channel list with nothing in it on a new deployment is correct,
 * and saying so plainly stops someone raising a ticket about it.
 */
export function DataTable<T>({
  columns,
  rows,
  loading,
  empty,
  caption,
}: {
  readonly columns: readonly Column<T>[];
  readonly rows: readonly T[];
  readonly loading: boolean;
  readonly empty: string;
  readonly caption?: string;
}) {
  if (loading && rows.length === 0) {
    return <p className="muted">Loading…</p>;
  }

  if (rows.length === 0) {
    return <p className="muted">{empty}</p>;
  }

  return (
    <table>
      {caption !== undefined && <caption>{caption}</caption>}
      <thead>
        <tr>
          {columns.map((column) => (
            <th key={column.header} scope="col">
              {column.header}
            </th>
          ))}
        </tr>
      </thead>
      <tbody>
        {rows.map((row, index) => (
          // The index is the key deliberately: these rows come from a cursor
          // page and are not reordered or edited in place, so a stable domain
          // key would buy nothing and several of these lists have no single
          // identifier to use.
          <tr key={index}>
            {columns.map((column) => (
              <td key={column.header}>{column.render(row)}</td>
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  );
}
