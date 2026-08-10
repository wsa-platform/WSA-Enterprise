import type { ReactNode } from 'react'

type Column<T> = {
  key: string
  header: string
  render: (row: T) => ReactNode
}

export function DataTable<T>({
  columns,
  rows,
  emptyLabel = 'No records found.',
  rowKey,
}: {
  columns: Column<T>[]
  rows: T[]
  emptyLabel?: string
  rowKey: (row: T) => string | number
}) {
  if (rows.length === 0) {
    return <p className="muted">{emptyLabel}</p>
  }

  return (
    <div className="table-wrap">
      <table className="data-table">
        <thead>
          <tr>
            {columns.map((column) => <th key={column.key}>{column.header}</th>)}
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={rowKey(row)}>
              {columns.map((column) => <td key={column.key}>{column.render(row)}</td>)}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

export function PaginationBar({
  page,
  lastPage,
  total,
  onPageChange,
}: {
  page: number
  lastPage: number
  total: number
  onPageChange: (page: number) => void
}) {
  return (
    <div className="pagination-bar">
      <span>{total} total · page {page} of {lastPage}</span>
      <div className="pagination-actions">
        <button type="button" disabled={page <= 1} onClick={() => onPageChange(page - 1)}>Previous</button>
        <button type="button" disabled={page >= lastPage} onClick={() => onPageChange(page + 1)}>Next</button>
      </div>
    </div>
  )
}
