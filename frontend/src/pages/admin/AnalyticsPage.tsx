import { useTranslation } from 'react-i18next'
import { PageHeader } from '../../components/PageHeader'
import { ErrorBanner, SkeletonGrid } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { getAnalyticsOverview } from '../../api'
import i18n from '../../i18n/config'

export function AnalyticsPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getAnalyticsOverview(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('platform.view')) {
    return <ErrorBanner message={t('analytics.noPermission')} />
  }

  if (loading && !data) return <SkeletonGrid count={4} />
  if (error) return <ErrorBanner message={error} onRetry={reload} />
  if (!data) return null

  return <>
    <PageHeader
      eyebrow={t('common.enterprise')}
      title={t('analytics.overviewTitle')}
      description={t('analytics.snapshotGenerated', { time: new Date(data.generated_at).toLocaleString() })}
      actions={<button type="button" className="refresh" onClick={reload}>{t('common.refresh')}</button>}
    />

    <section className="metrics" aria-label={t('analytics.metricsLabel')}>
      <Metric label={t('analytics.users')} value={data.users.total} />
      <Metric label={t('nav.teams')} value={data.teams.total} />
      <Metric label={t('dashboard.farms')} value={data.farms.total} />
      <Metric label={t('analytics.aiRequestsToday')} value={data.ai.requests_today} />
    </section>

    <div className="dashboard-grid">
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('nav.ai')}</p><h2>{t('analytics.requestStatus')}</h2></div></div>
        <div className="report-grid">
          {Object.entries(data.ai.by_status).map(([status, count]) => (
            <div key={status}><span>{status}</span><strong>{String(count)}</strong></div>
          ))}
        </div>
        <p className="muted">{t('analytics.totalRequests', { count: data.ai.requests_total })}</p>
      </section>

      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.operations')}</p><h2>{t('analytics.activity')}</h2></div></div>
        <div className="detail-grid">
          <div><span>{t('analytics.unreadNotifications')}</span><strong>{data.notifications.unread}</strong></div>
          <div><span>{t('analytics.auditEvents24h')}</span><strong>{data.audit.events_24h}</strong></div>
        </div>
      </section>
    </div>
  </>
}

function Metric({ label, value }: { label: string; value: number }) {
  return (
    <article className="metric-card">
      <p className="eyebrow">{label}</p>
      <strong>{value}</strong>
    </article>
  )
}
