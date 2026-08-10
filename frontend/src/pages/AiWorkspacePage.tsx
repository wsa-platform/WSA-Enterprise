import { useState } from 'react'
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

const requestTypes = ['library_summary', 'library_qa', 'training_assistance', 'diagnosis']

export function AiWorkspacePage() {
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [requestType, setRequestType] = useState('library_summary')
  const [input, setInput] = useState('How should I manage tomato leaf spots?')
  const [statusFilter, setStatusFilter] = useState('')
  const [page, setPage] = useState(1)
  const [message, setMessage] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const { data: provider, reload: reloadProvider } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getAiProvider(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: usage, reload: reloadUsage } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getAiUsage(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: requestsPayload, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    const query: Record<string, string | number> = { page, per_page: 10 }
    if (statusFilter) query.status = statusFilter
    return listAiRequests(token, organizationId ?? undefined, query)
  }, [token, organizationId, page, statusFilter])

  if (!can('ai.use')) {
    return <ErrorBanner message="You do not have permission to use AI services." />
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
        setMessage('Request accepted (async). Polling for completion…')
        final = await pollAiRequest(token, created.id, organizationId ?? undefined)
      }

      setMessage(`Request #${final.id} finished with status: ${final.status}.`)
      await reload()
      await reloadUsage()
      await reloadProvider()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to submit AI request.')
    } finally {
      setSubmitting(false)
    }
  }

  const handleCancel = async (record: AiRequestRecord) => {
    if (!token) return
    setMessage('')
    try {
      await cancelAiRequest(token, record.id, organizationId ?? undefined)
      setMessage(`Request #${record.id} cancelled.`)
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to cancel request.')
    }
  }

  return <>
    <PageHeader
      eyebrow="AI"
      title="AI Workspace"
      description={provider?.decision_support_notice}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    <QuotaPanel provider={provider ?? null} usage={usage ?? null} />

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">CREATE</p><h2>New AI request</h2></div></div>
      <div className="record-form">
        <label>
          Request type
          <select value={requestType} onChange={(event) => setRequestType(event.target.value)}>
            {(provider?.supported_request_types ?? requestTypes).map((type) => (
              <option key={type} value={type}>{type}</option>
            ))}
          </select>
        </label>
        <label>
          Input
          <textarea value={input} onChange={(event) => setInput(event.target.value)} rows={4} dir="auto" />
        </label>
        <button type="button" disabled={submitting} onClick={() => void handleSubmit()}>
          {submitting ? 'Submitting…' : 'Submit request'}
        </button>
      </div>
    </section>

    <section className="panel">
      <div className="panel-heading">
        <div><p className="eyebrow">HISTORY</p><h2>Request log</h2></div>
        <select value={statusFilter} onChange={(event) => { setStatusFilter(event.target.value); setPage(1) }} aria-label="Filter by status">
          <option value="">All statuses</option>
          {['pending', 'processing', 'completed', 'failed', 'cancelled'].map((status) => (
            <option key={status} value={status}>{status}</option>
          ))}
        </select>
      </div>

      {loading ? <p className="loading">Loading AI requests…</p> : rows.length === 0 ? (
        <EmptyState title="No AI requests" description="Submit a request to see history here." />
      ) : (
        <>
          <DataTable
            rows={rows}
            rowKey={(record) => record.id}
            columns={[
              { key: 'id', header: 'ID', render: (record) => <Link to={`/ai/requests/${record.id}`}>#{record.id}</Link> },
              { key: 'type', header: 'Type', render: (record) => record.request_type },
              { key: 'status', header: 'Status', render: (record) => <StatusBadge status={record.status} /> },
              { key: 'provider', header: 'Provider', render: (record) => record.provider },
              { key: 'created', header: 'Created', render: (record) => new Date(record.created_at).toLocaleString() },
              {
                key: 'actions',
                header: 'Actions',
                render: (record) => ['pending', 'processing'].includes(record.status)
                  ? <button type="button" className="link-button inline" onClick={() => void handleCancel(record)}>Cancel</button>
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
  const quota = usage ?? provider?.quota
  if (!quota) return null

  return (
    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">USAGE</p><h2>Quota & provider</h2></div></div>
      <div className="detail-grid">
        <div><span>Provider</span><strong>{provider?.provider ?? '—'}</strong></div>
        <div><span>Async dispatch</span><strong>{provider?.async_dispatch ? 'Enabled' : 'Disabled'}</strong></div>
        <div><span>Used</span><strong>{quota.used}</strong></div>
        <div><span>Remaining</span><strong>{quota.enabled ? quota.remaining ?? 0 : 'Unlimited'}</strong></div>
      </div>
    </section>
  )
}
