import { useEffect, useState } from 'react'
import { getModule, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { useAuth } from '../context/AuthContext'

const trainingTabs = [
  { label: 'Courses', path: '/training/courses' },
  { label: 'Lessons', path: '/training/lessons' },
  { label: 'My enrollments', path: '/training/enrollments' },
]

export function TrainingPage() {
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState('/training/courses')
  const [rows, setRows] = useState<unknown[]>([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!token) return
    setLoading(true)
    setError('')
    void getModule(token, activePath, organizationId ?? undefined)
      .then((payload) => setRows(unwrapModuleRows(payload)))
      .catch((requestError) => setError(requestError instanceof Error ? requestError.message : 'Unable to load training records.'))
      .finally(() => setLoading(false))
  }, [token, organizationId, activePath])

  return (
    <Panel eyebrow="EDUCATION" title="Training & courses">
      <ModuleTabs tabs={trainingTabs} activePath={activePath} onSelect={setActivePath} />
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">Loading training records…</p> : <RecordList rows={rows} emptyLabel="No training records found." />}
    </Panel>
  )
}
