import { useEffect, useState } from 'react'
import { getModule, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { useAuth } from '../context/AuthContext'

type ModulePageProps = {
  eyebrow: string
  title: string
  tabs: Array<{ label: string; path: string }>
  defaultPath: string
}

export function ModulePage({ eyebrow, title, tabs, defaultPath }: ModulePageProps) {
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState(defaultPath)
  const [rows, setRows] = useState<unknown[]>([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!token) return
    setLoading(true)
    setError('')
    void getModule(token, activePath, organizationId ?? undefined)
      .then((payload) => setRows(unwrapModuleRows(payload)))
      .catch((requestError) => setError(requestError instanceof Error ? requestError.message : 'Unable to load records.'))
      .finally(() => setLoading(false))
  }, [token, organizationId, activePath])

  return (
    <Panel eyebrow={eyebrow} title={title}>
      <ModuleTabs tabs={tabs} activePath={activePath} onSelect={setActivePath} />
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">Loading records…</p> : <RecordList rows={rows} />}
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
