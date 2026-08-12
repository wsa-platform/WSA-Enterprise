import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { getAuditLogs, type AuditLogEntry } from '../../api'
import { DataTable, PaginationBar } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import i18n from '../../i18n/config'

export function AuditPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [action, setAction] = useState('')
  const [page, setPage] = useState(1)
  const [selected, setSelected] = useState<AuditLogEntry | null>(null)

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    const query: Record<string, string | number> = { page, per_page: 15 }
    if (action.trim()) query.action = action.trim()
    return getAuditLogs(token, organizationId ?? undefined, query)
  }, [token, organizationId, page, action])

  if (!can('access.manage')) {
    return <ErrorBanner message={t('audit.noPermission')} />
  }

  const rows = Array.isArray(data) ? data : data?.data ?? []
  const pagination = !Array.isArray(data) && data
    ? { page: data.current_page, lastPage: data.last_page, total: data.total }
    : null

  return <>
    <PageHeader eyebrow={t('common.enterprise')} title={t('audit.title')} description={t('audit.securityDescription')} />

    {error && <ErrorBanner message={error} onRetry={reload} />}

    <section className="panel">
      <div className="panel-heading">
        <div><p className="eyebrow">{t('common.filter')}</p><h2>{t('audit.auditEvents')}</h2></div>
        <input
          className="search-input"
          value={action}
          onChange={(event) => { setAction(event.target.value); setPage(1) }}
          placeholder={t('audit.filterPlaceholder')}
          aria-label={t('audit.filterAria')}
        />
      </div>

      {loading ? <p className="loading">{t('audit.loading')}</p> : rows.length === 0 ? (
        <EmptyState title={t('audit.emptyTitle')} description={t('audit.emptyFilterDescription')} />
      ) : (
        <>
          <DataTable
            rows={rows}
            rowKey={(entry) => entry.id}
            columns={[
              { key: 'action', header: t('audit.action'), render: (entry) => entry.action },
              { key: 'actor', header: t('audit.actor'), render: (entry) => entry.user?.name ?? t('common.system') },
              { key: 'target', header: t('audit.target'), render: (entry) => entry.auditable_type ? `${entry.auditable_type}#${entry.auditable_id}` : '—' },
              { key: 'time', header: t('audit.timestamp'), render: (entry) => new Date(entry.created_at).toLocaleString() },
              {
                key: 'detail',
                header: '',
                render: (entry) => <button type="button" className="link-button inline" onClick={() => setSelected(entry)}>{t('audit.details')}</button>,
              },
            ]}
          />
          {pagination && (
            <PaginationBar
              page={pagination.page}
              lastPage={pagination.lastPage}
              total={pagination.total}
              onPageChange={setPage}
            />
          )}
        </>
      )}
    </section>

    {selected && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.detail')}</p><h2>{selected.action}</h2></div></div>
        <pre className="audit-detail">{JSON.stringify(sanitizeAudit(selected), null, 2)}</pre>
        <button type="button" className="refresh" onClick={() => setSelected(null)}>{t('common.close')}</button>
      </section>
    )}
  </>
}

function sanitizeAudit(entry: AuditLogEntry) {
  const redactedKeys = ['password', 'token', 'secret', 'api_key', 'authorization']
  const scrub = (value: unknown): unknown => {
    if (!value || typeof value !== 'object') return value
    if (Array.isArray(value)) return value.map(scrub)
    return Object.fromEntries(Object.entries(value as Record<string, unknown>).map(([key, nested]) => {
      if (redactedKeys.some((blocked) => key.toLowerCase().includes(blocked))) return [key, '[redacted]']
      return [key, scrub(nested)]
    }))
  }

  return {
    id: entry.id,
    action: entry.action,
    actor: entry.user?.name ?? null,
    organization_id: entry.organization_id,
    auditable_type: entry.auditable_type,
    auditable_id: entry.auditable_id,
    created_at: entry.created_at,
    new_values: scrub(entry.new_values),
    old_values: scrub(entry.old_values),
  }
}
