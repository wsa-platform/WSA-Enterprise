import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { createModuleRecord, getModule, searchLibrary, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { RecordForm } from '../components/RecordForm'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'

export function LibraryPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState('/library/items?publication_status=published')
  const [rows, setRows] = useState<unknown[]>([])
  const [query, setQuery] = useState('طماطم')
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(true)

  const libraryTabs = [
    { label: t('modules.publishedItems'), path: '/library/items?publication_status=published' },
    { label: t('modules.categories'), path: '/library/categories' },
    { label: t('modules.tags'), path: '/library/tags' },
    { label: t('common.search'), path: '/library/search' },
  ]

  const itemFields = [
    { name: 'slug', label: t('common.slug'), required: true },
    { name: 'title', label: t('common.title'), required: true },
    { name: 'title_ar', label: t('modules.arabicTitle') },
    { name: 'summary', label: t('modules.summary') },
  ]

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
      setError(translateApiError(requestError) || t('modules.libraryLoadFailed'))
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
    setMessage(t('modules.libraryItemCreated'))
    await load('/library/items?publication_status=published')
  }

  return (
    <Panel eyebrow={t('modules.knowledge')} title={t('modules.libraryTitle')}>
      <ModuleTabs tabs={libraryTabs} activePath={activePath} onSelect={setActivePath} />
      {activePath.startsWith('/library/items') && (
        <RecordForm title={t('modules.createLibraryItem')} fields={itemFields} submitLabel={t('modules.publishItem')} onSubmit={createItem} />
      )}
      {activePath === '/library/search' && (
        <div className="search-bar">
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder={t('modules.librarySearchPlaceholder')} aria-label={t('modules.librarySearchAria')} dir="auto" />
          <button type="button" onClick={() => void load('/library/search')}>{t('common.search')}</button>
        </div>
      )}
      {message && <p className="notice">{message}</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">{t('modules.loadingLibrary')}</p> : <RecordList rows={rows} emptyLabel={t('modules.noLibraryRecords')} />}
    </Panel>
  )
}
