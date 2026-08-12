import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import {
  cancelAiRequest,
  createAiRequest,
  getAiProvider,
  getAiUsage,
  listAiRequests,
  pollAiRequest,
  type AiProviderInfo,
  type AiRequestRecord,
} from '../api'
import { DataTable, PaginationBar } from '../components/DataTable'
import { PageHeader } from '../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../components/UiPrimitives'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { useAsyncData } from '../hooks/useAsyncData'
import { translateApiError } from '../i18n/apiErrors'
import i18n from '../i18n/config'

const requestTypes = ['library_summary', 'library_qa', 'training_assistance', 'diagnosis']

export function AiWorkspacePage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [requestType, setRequestType] = useState('library_summary')
  const [input, setInput] = useState('How should I manage tomato leaf spots?')
  const [statusFilter, setStatusFilter] = useState('')
  const [page, setPage] = useState(1)
  const [message, setMessage] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const { data: provider, reload: reloadProvider } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getAiProvider(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: usage, reload: reloadUsage } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getAiUsage(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: requestsPayload, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    const query: Record<string, string | number> = { page, per_page: 10 }
    if (statusFilter) query.status = statusFilter
    return listAiRequests(token, organizationId ?? undefined, query)
  }, [token, organizationId, page, statusFilter])

  if (!can('ai.use')) {
    return <ErrorBanner message={t('ai.noPermissionUse')} />
  }

  const rows = requestsPayload?.data ?? []

  const handleSubmit = async () => {
    if (!token) return
    setSubmitting(true)
    setMessage('')
    try {
      const created = await createAiRequest(token, {
        request_type: requestType,
        input: requestType === 'diagnosis'
          ? { reference: `UI-${Date.now()}`, notes: input, symptom_ids: [] }
          : { content: input, query: input },
      }, organizationId ?? undefined)

      let final = created
      if (created.status === 'pending' || created.status === 'processing') {
        setMessage(t('ai.requestAccepted'))
        final = await pollAiRequest(token, created.id, organizationId ?? undefined)
      }

      setMessage(t('ai.requestFinished', { id: final.id, status: final.status }))
      await reload()
      await reloadUsage()
      await reloadProvider()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('ai.submitFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  const handleCancel = async (record: AiRequestRecord) => {
    if (!token) return
    setMessage('')
    try {
      await cancelAiRequest(token, record.id, organizationId ?? undefined)
      setMessage(t('ai.requestCancelled', { id: record.id }))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('ai.cancelFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('nav.ai')}
      title={t('ai.workspace')}
      description={provider?.decision_support_notice}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    <QuotaPanel provider={provider ?? null} usage={usage ?? null} />

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.create')}</p><h2>{t('ai.newRequest')}</h2></div></div>
      <div className="record-form">
        <label>
          {t('ai.requestType')}
          <select value={requestType} onChange={(event) => setRequestType(event.target.value)}>
            {(provider?.supported_request_types ?? requestTypes).map((type) => (
              <option key={type} value={type}>{type}</option>
            ))}
          </select>
        </label>
        <label>
          {t('common.input')}
          <textarea value={input} onChange={(event) => setInput(event.target.value)} rows={4} dir="auto" />
        </label>
        <button type="button" disabled={submitting} onClick={() => void handleSubmit()}>
          {submitting ? t('ai.submitting') : t('ai.submitRequest')}
        </button>
      </div>
    </section>

    <section className="panel">
      <div className="panel-heading">
        <div><p className="eyebrow">{t('common.activity')}</p><h2>{t('ai.requestLog')}</h2></div>
        <select value={statusFilter} onChange={(event) => { setStatusFilter(event.target.value); setPage(1) }} aria-label={t('ai.filterByStatus')}>
          <option value="">{t('ai.allStatuses')}</option>
          {['pending', 'processing', 'completed', 'failed', 'cancelled'].map((status) => (
            <option key={status} value={status}>{status}</option>
          ))}
        </select>
      </div>

      {loading ? <p className="loading">{t('ai.loadingRequests')}</p> : rows.length === 0 ? (
        <EmptyState title={t('ai.emptyTitle')} description={t('ai.emptyDescription')} />
      ) : (
        <>
          <DataTable
            rows={rows}
            rowKey={(record) => record.id}
            columns={[
              { key: 'id', header: t('common.id'), render: (record) => <Link to={`/ai/requests/${record.id}`}>#{record.id}</Link> },
              { key: 'type', header: t('common.type'), render: (record) => record.request_type },
              { key: 'status', header: t('common.status'), render: (record) => <StatusBadge status={record.status} /> },
              { key: 'provider', header: t('common.provider'), render: (record) => record.provider },
              { key: 'created', header: t('common.created'), render: (record) => new Date(record.created_at).toLocaleString() },
              {
                key: 'actions',
                header: t('common.actions'),
                render: (record) => ['pending', 'processing'].includes(record.status)
                  ? <button type="button" className="link-button inline" onClick={() => void handleCancel(record)}>{t('common.cancel')}</button>
                  : '—',
              },
            ]}
          />
          {requestsPayload && (
            <PaginationBar
              page={requestsPayload.current_page}
              lastPage={requestsPayload.last_page}
              total={requestsPayload.total}
              onPageChange={setPage}
            />
          )}
        </>
      )}
    </section>
  </>
}

function QuotaPanel({ provider, usage }: { provider: AiProviderInfo | null; usage: { enabled: boolean; used: number; limit: number | null; remaining: number | null } | null }) {
  const { t } = useTranslation()
  const quota = usage ?? provider?.quota
  if (!quota) return null

  return (
    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.usage')}</p><h2>{t('ai.quotaProvider')}</h2></div></div>
      <div className="detail-grid">
        <div><span>{t('common.provider')}</span><strong>{provider?.provider ?? '—'}</strong></div>
        <div><span>{t('ai.asyncDispatch')}</span><strong>{provider?.async_dispatch ? t('common.enabled') : t('common.disabled')}</strong></div>
        <div><span>{t('ai.used')}</span><strong>{quota.used}</strong></div>
        <div><span>{t('ai.remaining')}</span><strong>{quota.enabled ? quota.remaining ?? 0 : t('common.unlimited')}</strong></div>
      </div>
    </section>
  )
}
