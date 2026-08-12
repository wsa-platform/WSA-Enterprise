import { PageHeader } from '../../components/PageHeader'
import { ErrorBanner, SkeletonGrid } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { getAnalyticsOverview } from '../../api'

export function AnalyticsPage() {
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getAnalyticsOverview(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('platform.view')) {
    return <ErrorBanner message="You do not have permission to view analytics." />
  }

  if (loading && !data) return <SkeletonGrid count={4} />
  if (error) return <ErrorBanner message={error} onRetry={reload} />
  if (!data) return null

  return <>
    <PageHeader
      eyebrow="ENTERPRISE"
      title="Analytics overview"
      description={`Organization snapshot generated ${new Date(data.generated_at).toLocaleString()}`}
      actions={<button type="button" className="refresh" onClick={reload}>Refresh</button>}
    />

    <section className="metrics" aria-label="Analytics metrics">
      <Metric label="Users" value={data.users.total} />
      <Metric label="Teams" value={data.teams.total} />
      <Metric label="Farms" value={data.farms.total} />
      <Metric label="AI requests today" value={data.ai.requests_today} />
    </section>

    <div className="dashboard-grid">
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">AI</p><h2>Request status</h2></div></div>
        <div className="report-grid">
          {Object.entries(data.ai.by_status).map(([status, count]) => (
            <div key={status}><span>{status}</span><strong>{String(count)}</strong></div>
          ))}
        </div>
        <p className="muted">Total requests: {data.ai.requests_total}</p>
      </section>

      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">OPERATIONS</p><h2>Activity</h2></div></div>
        <div className="detail-grid">
          <div><span>Unread notifications</span><strong>{data.notifications.unread}</strong></div>
          <div><span>Audit events (24h)</span><strong>{data.audit.events_24h}</strong></div>
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
