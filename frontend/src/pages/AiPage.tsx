import { useEffect, useState } from 'react'
import { createAiRequest, getAiProvider, getModule, unwrapModuleRows } from '../api'
import type { AiProviderInfo } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { useAuth } from '../context/AuthContext'

const aiTabs = [
  { label: 'Request log', path: '/ai/requests' },
]

export function AiPage() {
  const { token, organizationId } = useAuth()
  const [provider, setProvider] = useState<AiProviderInfo | null>(null)
  const [rows, setRows] = useState<unknown[]>([])
  const [query, setQuery] = useState('How should I manage tomato leaf spots?')
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(true)

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
      setError(requestError instanceof Error ? requestError.message : 'Unable to load AI services.')
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
      setMessage('Mock provider returned a decision-support response.')
      await load()
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Unable to run AI request.')
    }
  }

  return (
    <Panel eyebrow="AI FOUNDATION" title="AI services">
      {provider && <p className="notice">{provider.decision_support_notice} Provider: <strong>{provider.provider}</strong></p>}
      <ModuleTabs tabs={aiTabs} activePath="/ai/requests" onSelect={() => undefined} />
      <div className="search-bar">
        <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Ask the library assistant" aria-label="AI query" dir="auto" />
        <button type="button" onClick={() => void runLibraryQa()}>Run library Q&amp;A</button>
      </div>
      {message && <p className="notice">{message}</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">Loading AI request log…</p> : <RecordList rows={rows} emptyLabel="No AI requests yet." />}
    </Panel>
  )
}
