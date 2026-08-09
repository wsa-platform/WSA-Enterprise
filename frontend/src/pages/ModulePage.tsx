import { useEffect, useState } from 'react'
import { ApiError, createModuleRecord, deleteModuleRecord, getModule, modulePaginationMeta, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { ConfirmDialog } from '../components/ConfirmDialog'
import { RecordForm } from '../components/RecordForm'
import { useAuth } from '../context/AuthContext'

type ModulePageProps = {
  eyebrow: string
  title: string
  tabs: Array<{ label: string; path: string }>
  defaultPath: string
  createFields?: Array<{ name: string; label: string; required?: boolean }>
}

export function ModulePage({ eyebrow, title, tabs, defaultPath, createFields }: ModulePageProps) {
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState(defaultPath)
  const [rows, setRows] = useState<unknown[]>([])
  const [pagination, setPagination] = useState<{ currentPage: number; lastPage: number; total: number } | null>(null)
  const [error, setError] = useState('')
  const [forbidden, setForbidden] = useState(false)
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(true)
  const [pendingDelete, setPendingDelete] = useState<Record<string, unknown> | null>(null)

  const load = async (path = activePath) => {
    if (!token) return
    setLoading(true)
    setError('')
    setForbidden(false)
    try {
      const payload = await getModule(token, path, organizationId ?? undefined)
      setRows(unwrapModuleRows(payload))
      setPagination(modulePaginationMeta(payload))
    } catch (requestError) {
      if (requestError instanceof ApiError && requestError.isForbidden) {
        setForbidden(true)
        setError('You do not have permission to view these records in the selected organization.')
      } else {
        setError(requestError instanceof Error ? requestError.message : 'Unable to load records.')
      }
      setRows([])
      setPagination(null)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void load()
  }, [token, organizationId, activePath])

  const createRecord = async (values: Record<string, string>) => {
    if (!token) return
    try {
      await createModuleRecord(token, activePath, values, organizationId ?? undefined)
      setMessage('Record created successfully.')
      await load()
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Unable to create record.')
    }
  }

  const removeRecord = async (record: Record<string, unknown>) => {
    if (!token || typeof record.id !== 'number') return
    const basePath = activePath.split('?')[0]
    try {
      await deleteModuleRecord(token, basePath, record.id, organizationId ?? undefined)
      setMessage('Record deleted.')
      setPendingDelete(null)
      await load()
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Unable to delete record.')
    }
  }

  return (
    <Panel eyebrow={eyebrow} title={title}>
      <ModuleTabs tabs={tabs} activePath={activePath} onSelect={setActivePath} />
      {createFields && !forbidden && (
        <RecordForm
          title="Create record"
          fields={createFields}
          submitLabel="Create"
          onSubmit={createRecord}
        />
      )}
      {message && <p className="notice">{message}</p>}
      {forbidden && <p className="banner forbidden">Access denied for this organization.</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">Loading records…</p> : (
        <>
          {pagination && (
            <p className="muted pagination-meta">
              Showing page {pagination.currentPage} of {pagination.lastPage} ({pagination.total} total)
            </p>
          )}
          <RecordList rows={rows} emptyLabel={forbidden ? 'Records unavailable.' : 'No records found.'} />
          {rows.length > 0 && createFields && !forbidden && (
            <div className="module-results">
              {rows.slice(0, 8).map((row, index) => {
                const record = row as Record<string, unknown>
                if (typeof record.id !== 'number') return null
                return (
                  <button key={index} type="button" className="link-button danger-link" onClick={() => setPendingDelete(record)}>
                    Delete #{String(record.id)}
                  </button>
                )
              })}
            </div>
          )}
        </>
      )}
      <ConfirmDialog
        open={pendingDelete !== null}
        title="Delete record"
        message="This action cannot be undone. Delete this record?"
        confirmLabel="Delete"
        onCancel={() => setPendingDelete(null)}
        onConfirm={() => pendingDelete && void removeRecord(pendingDelete)}
      />
    </Panel>
  )
}

export const farmTabs = [
  { label: 'Farms', path: '/farm/farms' },
  { label: 'Regions', path: '/farm/regions' },
  { label: 'Fields', path: '/farm/fields' },
  { label: 'Blocks', path: '/farm/blocks' },
  { label: 'Greenhouses', path: '/farm/greenhouses' },
  { label: 'Irrigation', path: '/farm/irrigation-zones' },
]

export const cropTabs = [
  { label: 'Crop types', path: '/crop/types' },
  { label: 'Varieties', path: '/crop/varieties' },
  { label: 'Seasons', path: '/crop/seasons' },
  { label: 'Growth stages', path: '/crop/growth-stages' },
  { label: 'Harvests', path: '/crop/harvests' },
  { label: 'Yields', path: '/crop/yields' },
]

export const soilTabs = [
  { label: 'Analyses', path: '/soil/analyses' },
  { label: 'Nutrients', path: '/soil/nutrients' },
  { label: 'Recommendations', path: '/soil/recommendations' },
]

export const farmCreateFields = [
  { name: 'code', label: 'Code', required: true },
  { name: 'name', label: 'Name', required: true },
]

export const cropCreateFields = [
  { name: 'code', label: 'Code', required: true },
  { name: 'name', label: 'Name', required: true },
]

export const soilCreateFields = [
  { name: 'sample_reference', label: 'Sample reference', required: true },
  { name: 'sampled_at', label: 'Sampled at (YYYY-MM-DD)', required: true },
]
