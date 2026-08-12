import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'

type Column<T> = {
  key: string
  header: string
  render: (row: T) => ReactNode
}

export function DataTable<T>({
  columns,
  rows,
  emptyLabel,
  rowKey,
}: {
  columns: Column<T>[]
  rows: T[]
  emptyLabel?: string
  rowKey: (row: T) => string | number
}) {
  const { t } = useTranslation()
  const label = emptyLabel ?? t('common.noRecords')

  if (rows.length === 0) {
    return <p className="muted">{label}</p>
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
  const { t } = useTranslation()

  return (
    <div className="pagination-bar">
      <span>{t('common.pageOf', { total, page, lastPage })}</span>
      <div className="pagination-actions">
        <button type="button" disabled={page <= 1} onClick={() => onPageChange(page - 1)}>{t('common.previous')}</button>
        <button type="button" disabled={page >= lastPage} onClick={() => onPageChange(page + 1)}>{t('common.next')}</button>
      </div>
    </div>
  )
}
