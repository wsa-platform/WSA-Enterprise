import { Link } from 'react-router-dom'
import { useParams } from 'react-router-dom'
import { getAiRequest } from '../api'
import { PageHeader } from '../components/PageHeader'
import { ErrorBanner, SkeletonGrid, StatusBadge } from '../components/UiPrimitives'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { useAsyncData } from '../hooks/useAsyncData'

export function AiRequestDetailPage() {
  const { requestId } = useParams()
  const id = Number(requestId)
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token || !id) throw new Error('Invalid AI request.')
    return getAiRequest(token, id, organizationId ?? undefined)
  }, [token, organizationId, id])

  if (!can('ai.use')) {
    return <ErrorBanner message="You do not have permission to view AI requests." />
  }

  if (loading && !data) return <SkeletonGrid count={2} />
  if (error) return <ErrorBanner message={error} onRetry={reload} />
  if (!data) return null

  return <>
    <PageHeader
      eyebrow="AI"
      title={`Request #${data.id}`}
      description={data.request_type}
      breadcrumbs={[
        { label: 'Dashboard', to: '/' },
        { label: 'AI Workspace', to: '/ai/workspace' },
        { label: `#${data.id}` },
      ]}
      actions={<StatusBadge status={data.status} />}
    />

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">DETAILS</p><h2>Request metadata</h2></div></div>
      <div className="detail-grid">
        <div><span>Organization ID</span><strong>{data.organization_id}</strong></div>
        <div><span>Creator user ID</span><strong>{data.user_id ?? '—'}</strong></div>
        <div><span>Provider</span><strong>{data.provider}</strong></div>
        <div><span>Created</span><strong>{new Date(data.created_at).toLocaleString()}</strong></div>
        <div><span>Updated</span><strong>{new Date(data.updated_at).toLocaleString()}</strong></div>
        <div><span>Latency</span><strong>{data.latency_ms ?? '—'} ms</strong></div>
      </div>
    </section>

    {data.error_message && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">ERROR</p><h2>Failure details</h2></div></div>
        <p className="error">{data.error_message}</p>
      </section>
    )}

    {data.output && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">OUTPUT</p><h2>Decision-support result</h2></div></div>
        <pre className="audit-detail">{JSON.stringify(data.output, null, 2)}</pre>
      </section>
    )}

    <p className="muted"><Link to="/ai/workspace">Back to AI workspace</Link></p>
  </>
}
