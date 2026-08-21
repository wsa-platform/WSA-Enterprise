import { Link, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { getAiRequest } from '../api'
import { PageHeader } from '../components/PageHeader'
import { ErrorBanner, SkeletonGrid, StatusBadge } from '../components/UiPrimitives'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { useAsyncData } from '../hooks/useAsyncData'
import i18n from '../i18n/config'

export function AiRequestDetailPage() {
  const { t } = useTranslation()
  const { requestId } = useParams()
  const id = Number(requestId)
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token || !id) throw new Error(i18n.t('ai.invalidRequest'))
    return getAiRequest(token, id, organizationId ?? undefined)
  }, [token, organizationId, id])

  if (!can('ai.use')) {
    return <ErrorBanner message={t('ai.noPermissionView')} />
  }

  if (loading && !data) return <SkeletonGrid count={2} />
  if (error) return <ErrorBanner message={error} onRetry={reload} />
  if (!data) return null

  return <>
    <PageHeader
      eyebrow={t('nav.ai')}
      title={`#${data.id}`}
      description={data.request_type}
      breadcrumbs={[
        { label: t('nav.home'), to: '/account' },
        { label: t('ai.workspace'), to: '/ai/workspace' },
        { label: `#${data.id}` },
      ]}
      actions={<StatusBadge status={data.status} />}
    />

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.detail')}</p><h2>{t('ai.requestMetadata')}</h2></div></div>
      <div className="detail-grid">
        <div><span>{t('ai.organizationId')}</span><strong>{data.organization_id}</strong></div>
        <div><span>{t('ai.creatorUserId')}</span><strong>{data.user_id ?? '—'}</strong></div>
        <div><span>{t('common.provider')}</span><strong>{data.provider}</strong></div>
        <div><span>{t('common.created')}</span><strong>{new Date(data.created_at).toLocaleString()}</strong></div>
        <div><span>{t('ai.updated')}</span><strong>{new Date(data.updated_at).toLocaleString()}</strong></div>
        <div><span>{t('ai.latency')}</span><strong>{data.latency_ms != null ? t('ai.latencyMs', { value: data.latency_ms }) : '—'}</strong></div>
      </div>
    </section>

    {data.error_message && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('dashboard.failed')}</p><h2>{t('ai.failureDetails')}</h2></div></div>
        <p className="error">{data.error_message}</p>
      </section>
    )}

    {data.output && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.detail')}</p><h2>{t('ai.decisionSupportResult')}</h2></div></div>
        <pre className="audit-detail">{JSON.stringify(data.output, null, 2)}</pre>
      </section>
    )}

    <p className="muted"><Link to="/ai/workspace">{t('ai.backToWorkspace')}</Link></p>
  </>
}
