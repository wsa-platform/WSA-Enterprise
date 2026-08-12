import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { createApiClient, getApiClients, revokeApiClient, unwrapModuleRows, type ApiClientRecord } from '../../api'
import { DataTable } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'
import i18n from '../../i18n/config'

export function ApiClientsPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [name, setName] = useState('')
  const [message, setMessage] = useState('')
  const [createdSecret, setCreatedSecret] = useState<{ name: string; clientId: string; secret: string } | null>(null)

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getApiClients(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('access.manage')) {
    return <ErrorBanner message={t('apiClients.noPermission')} />
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
      setMessage(t('apiClients.createdWithSecret'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('apiClients.createFailed'))
    }
  }

  const handleRevoke = async (client: ApiClientRecord) => {
    if (!token) return
    setMessage('')
    try {
      await revokeApiClient(token, client.id, organizationId ?? undefined)
      setMessage(t('apiClients.revokedNamed', { name: client.name }))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('apiClients.revokeClientFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('common.enterprise')}
      title={t('apiClients.title')}
      description={t('apiClients.m2mDescription')}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    {createdSecret && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.secret')}</p><h2>{createdSecret.name}</h2></div></div>
        <div className="detail-grid">
          <div><span>{t('apiClients.clientId')}</span><strong>{createdSecret.clientId}</strong></div>
          <div><span>{t('apiClients.clientSecret')}</span><strong>{createdSecret.secret}</strong></div>
        </div>
      </section>
    )}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.createSection')}</p><h2>{t('apiClients.newClient')}</h2></div></div>
      <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleCreate() }}>
        <label>{t('common.name')}<input value={name} onChange={(event) => setName(event.target.value)} required maxLength={255} /></label>
        <button type="submit">{t('apiClients.createClientButton')}</button>
      </form>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.registry')}</p><h2>{t('apiClients.clients')}</h2></div></div>
      {loading ? <p className="loading">{t('apiClients.loadingClients')}</p> : clients.length === 0 ? (
        <EmptyState title={t('apiClients.emptyTitle')} description={t('apiClients.emptyM2mDescription')} />
      ) : (
        <DataTable
          rows={clients}
          rowKey={(client) => client.id}
          columns={[
            { key: 'name', header: t('common.name'), render: (client) => client.name },
            { key: 'client_id', header: t('apiClients.clientId'), render: (client) => client.client_id },
            { key: 'status', header: t('common.status'), render: (client) => (
              <StatusBadge status={client.revoked_at ? 'inactive' : 'active'} />
            ) },
            {
              key: 'actions',
              header: t('common.actions'),
              render: (client) => !client.revoked_at ? (
                <button type="button" className="link-button inline danger" onClick={() => void handleRevoke(client)}>{t('common.revoke')}</button>
              ) : '—',
            },
          ]}
        />
      )}
    </section>
  </>
}
