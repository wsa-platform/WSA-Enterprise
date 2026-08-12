import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { createAiRequest, getAiProvider, getModule, unwrapModuleRows } from '../api'
import type { AiProviderInfo } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'

export function AiPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const [provider, setProvider] = useState<AiProviderInfo | null>(null)
  const [rows, setRows] = useState<unknown[]>([])
  const [query, setQuery] = useState('How should I manage tomato leaf spots?')
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(true)

  const aiTabs = [
    { label: t('ai.requestLog'), path: '/ai/requests' },
  ]

  const load = async () => {
    if (!token) return
    setLoading(true)
    setError('')
    try {
      const [providerInfo, requests] = await Promise.all([
        getAiProvider(token, organizationId ?? undefined),
        getModule(token, '/ai/requests', organizationId ?? undefined),
      ])
      setProvider(providerInfo)
      setRows(unwrapModuleRows(requests))
    } catch (requestError) {
      setError(translateApiError(requestError) || t('ai.loadFailed'))
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void load()
  }, [token, organizationId])

  const runLibraryQa = async () => {
    if (!token) return
    setError('')
    setMessage('')
    try {
      await createAiRequest(token, {
        request_type: 'library_qa',
        input: { query },
      }, organizationId ?? undefined)
      setMessage(t('ai.mockResponse'))
      await load()
    } catch (requestError) {
      setError(translateApiError(requestError) || t('ai.runFailed'))
    }
  }

  return (
    <Panel eyebrow={t('ai.foundation')} title={t('ai.servicesTitle')}>
      {provider && <p className="notice">{provider.decision_support_notice} {t('ai.providerLabel', { name: provider.provider })}</p>}
      <ModuleTabs tabs={aiTabs} activePath="/ai/requests" onSelect={() => undefined} />
      <div className="search-bar">
        <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder={t('ai.askPlaceholder')} aria-label={t('ai.queryAria')} dir="auto" />
        <button type="button" onClick={() => void runLibraryQa()}>{t('ai.runLibraryQa')}</button>
      </div>
      {message && <p className="notice">{message}</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">{t('ai.loadingLog')}</p> : <RecordList rows={rows} emptyLabel={t('ai.noRequestsYet')} />}
    </Panel>
  )
}
