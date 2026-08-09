import { useEffect, useState } from 'react'
import { createModuleRecord, getModule, searchLibrary, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { RecordForm } from '../components/RecordForm'
import { useAuth } from '../context/AuthContext'

const libraryTabs = [
  { label: 'Published items', path: '/library/items?publication_status=published' },
  { label: 'Categories', path: '/library/categories' },
  { label: 'Tags', path: '/library/tags' },
  { label: 'Search', path: '/library/search' },
]

const itemFields = [
  { name: 'slug', label: 'Slug', required: true },
  { name: 'title', label: 'Title', required: true },
  { name: 'title_ar', label: 'Arabic title' },
  { name: 'summary', label: 'Summary' },
]

export function LibraryPage() {
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState('/library/items?publication_status=published')
  const [rows, setRows] = useState<unknown[]>([])
  const [query, setQuery] = useState('طماطم')
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
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

  const createItem = async (values: Record<string, string>) => {
    if (!token) return
    await createModuleRecord(token, '/library/items', {
      ...values,
      publication_status: 'published',
    }, organizationId ?? undefined)
    setMessage('Library item created.')
    await load('/library/items?publication_status=published')
  }

  return (
    <Panel eyebrow="KNOWLEDGE" title="Agricultural library">
      <ModuleTabs tabs={libraryTabs} activePath={activePath} onSelect={setActivePath} />
      {activePath.startsWith('/library/items') && (
        <RecordForm title="Create library item" fields={itemFields} submitLabel="Publish item" onSubmit={createItem} />
      )}
      {activePath === '/library/search' && (
        <div className="search-bar">
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="ابحث في المكتبة الزراعية" aria-label="Library search" dir="auto" />
          <button type="button" onClick={() => void load('/library/search')}>Search</button>
        </div>
      )}
      {message && <p className="notice">{message}</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">Loading library records…</p> : <RecordList rows={rows} emptyLabel="No library records found." />}
    </Panel>
  )
}
