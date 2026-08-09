import { useEffect, useState } from 'react'
import { createModuleRecord, deleteModuleRecord, getModule, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
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
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(true)

  const load = async (path = activePath) => {
    if (!token) return
    setLoading(true)
    setError('')
    try {
      setRows(unwrapModuleRows(await getModule(token, path, organizationId ?? undefined)))
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Unable to load records.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void load()
  }, [token, organizationId, activePath])

  const createRecord = async (values: Record<string, string>) => {
    if (!token) return
    await createModuleRecord(token, activePath, values, organizationId ?? undefined)
    setMessage('Record created successfully.')
    await load()
  }

  const removeRecord = async (record: Record<string, unknown>) => {
    if (!token || typeof record.id !== 'number') return
    const basePath = activePath.split('?')[0]
    await deleteModuleRecord(token, basePath, record.id, organizationId ?? undefined)
    setMessage('Record deleted.')
    await load()
  }

  return (
    <Panel eyebrow={eyebrow} title={title}>
      <ModuleTabs tabs={tabs} activePath={activePath} onSelect={setActivePath} />
      {createFields && (
        <RecordForm
          title="Create record"
          fields={createFields}
          submitLabel="Create"
          onSubmit={createRecord}
        />
      )}
      {message && <p className="notice">{message}</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">Loading records…</p> : (
        <>
          <RecordList rows={rows} emptyLabel="No records found." />
          {rows.length > 0 && createFields && (
            <div className="module-results">
              {rows.slice(0, 8).map((row, index) => {
                const record = row as Record<string, unknown>
                if (typeof record.id !== 'number') return null
                return (
                  <button key={index} type="button" className="link-button" onClick={() => void removeRecord(record)}>
                    Delete #{String(record.id)}
                  </button>
                )
              })}
            </div>
          )}
        </>
      )}
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
