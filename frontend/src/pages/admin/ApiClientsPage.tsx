import { useState } from 'react'
import { createApiClient, getApiClients, revokeApiClient, unwrapModuleRows, type ApiClientRecord } from '../../api'
import { DataTable } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'

export function ApiClientsPage() {
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [name, setName] = useState('')
  const [message, setMessage] = useState('')
  const [createdSecret, setCreatedSecret] = useState<{ name: string; clientId: string; secret: string } | null>(null)

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getApiClients(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('access.manage')) {
    return <ErrorBanner message="You do not have permission to manage API clients." />
  }

  const clients = unwrapModuleRows(data ?? []) as ApiClientRecord[]

  const handleCreate = async () => {
    if (!token || !name.trim()) return
    setMessage('')
    setCreatedSecret(null)
    try {
      const result = await createApiClient(token, { name: name.trim() }, organizationId ?? undefined)
      setCreatedSecret({
        name: result.client.name,
        clientId: result.client.client_id,
        secret: result.client_secret,
      })
      setName('')
      setMessage('API client created. Copy the secret now — it will not be shown again.')
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to create API client.')
    }
  }

  const handleRevoke = async (client: ApiClientRecord) => {
    if (!token) return
    setMessage('')
    try {
      await revokeApiClient(token, client.id, organizationId ?? undefined)
      setMessage(`Revoked client "${client.name}".`)
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to revoke client.')
    }
  }

  return <>
    <PageHeader
      eyebrow="ENTERPRISE"
      title="API clients"
      description="Register machine-to-machine clients with scoped read access."
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    {createdSecret && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">SECRET</p><h2>{createdSecret.name}</h2></div></div>
        <div className="detail-grid">
          <div><span>Client ID</span><strong>{createdSecret.clientId}</strong></div>
          <div><span>Client secret</span><strong>{createdSecret.secret}</strong></div>
        </div>
      </section>
    )}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">CREATE</p><h2>New API client</h2></div></div>
      <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleCreate() }}>
        <label>Name<input value={name} onChange={(event) => setName(event.target.value)} required maxLength={255} /></label>
        <button type="submit">Create client</button>
      </form>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">REGISTRY</p><h2>Clients</h2></div></div>
      {loading ? <p className="loading">Loading clients…</p> : clients.length === 0 ? (
        <EmptyState title="No API clients" description="Create a client to enable M2M integrations." />
      ) : (
        <DataTable
          rows={clients}
          rowKey={(client) => client.id}
          columns={[
            { key: 'name', header: 'Name', render: (client) => client.name },
            { key: 'client_id', header: 'Client ID', render: (client) => client.client_id },
            { key: 'status', header: 'Status', render: (client) => (
              <StatusBadge status={client.revoked_at ? 'inactive' : 'active'} />
            ) },
            {
              key: 'actions',
              header: 'Actions',
              render: (client) => !client.revoked_at ? (
                <button type="button" className="link-button inline danger" onClick={() => void handleRevoke(client)}>Revoke</button>
              ) : '—',
            },
          ]}
        />
      )}
    </section>
  </>
}
