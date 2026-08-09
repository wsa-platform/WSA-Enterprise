import { useEffect, useState } from 'react'
import { getModule, searchLibrary, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { useAuth } from '../context/AuthContext'

const libraryTabs = [
  { label: 'Published items', path: '/library/items?publication_status=published' },
  { label: 'Categories', path: '/library/categories' },
  { label: 'Tags', path: '/library/tags' },
  { label: 'Search', path: '/library/search' },
]

export function LibraryPage() {
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState('/library/items?publication_status=published')
  const [rows, setRows] = useState<unknown[]>([])
  const [query, setQuery] = useState('طماطم')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  const load = async (path = activePath) => {
    if (!token) return
    setLoading(true)
    setError('')
    try {
      if (path === '/library/search') {
        setRows(unwrapModuleRows(await searchLibrary(token, query, organizationId ?? undefined)))
      } else {
        setRows(unwrapModuleRows(await getModule(token, path, organizationId ?? undefined)))
      }
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Unable to load library records.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void load()
  }, [token, organizationId, activePath])

  return (
    <Panel eyebrow="KNOWLEDGE" title="Agricultural library">
      <ModuleTabs tabs={libraryTabs} activePath={activePath} onSelect={setActivePath} />
      {activePath === '/library/search' && (
        <div className="search-bar">
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="ابحث في المكتبة الزراعية" aria-label="Library search" dir="auto" />
          <button type="button" onClick={() => void load('/library/search')}>Search</button>
        </div>
      )}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">Loading library records…</p> : <RecordList rows={rows} emptyLabel="No library records found." />}
    </Panel>
  )
}
