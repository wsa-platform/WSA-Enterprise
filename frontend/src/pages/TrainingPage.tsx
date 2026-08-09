import { useEffect, useState } from 'react'
import { createModuleRecord, getModule, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { RecordForm } from '../components/RecordForm'
import { useAuth } from '../context/AuthContext'

const trainingTabs = [
  { label: 'Courses', path: '/training/courses' },
  { label: 'Lessons', path: '/training/lessons' },
  { label: 'My enrollments', path: '/training/enrollments' },
]

const courseFields = [
  { name: 'code', label: 'Code', required: true },
  { name: 'title', label: 'Title', required: true },
  { name: 'title_ar', label: 'Arabic title' },
]

export function TrainingPage() {
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState('/training/courses')
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
      setError(requestError instanceof Error ? requestError.message : 'Unable to load training records.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void load()
  }, [token, organizationId, activePath])

  const createCourse = async (values: Record<string, string>) => {
    if (!token) return
    await createModuleRecord(token, '/training/courses', values, organizationId ?? undefined)
    setMessage('Training course created.')
    await load('/training/courses')
  }

  return (
    <Panel eyebrow="EDUCATION" title="Training & courses">
      <ModuleTabs tabs={trainingTabs} activePath={activePath} onSelect={setActivePath} />
      {activePath === '/training/courses' && (
        <RecordForm title="Create course" fields={courseFields} submitLabel="Create course" onSubmit={createCourse} />
      )}
      {message && <p className="notice">{message}</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">Loading training records…</p> : <RecordList rows={rows} emptyLabel="No training records found." />}
    </Panel>
  )
}
