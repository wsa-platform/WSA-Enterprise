import { useEffect, useState } from 'react'
import { createDiagnosisRequest, getModule, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { useAuth } from '../context/AuthContext'

const diagnosisTabs = [
  { label: 'Requests', path: '/diagnosis/requests' },
  { label: 'Categories', path: '/diagnosis/categories' },
  { label: 'Symptoms', path: '/diagnosis/symptoms' },
  { label: 'Diseases', path: '/diagnosis/diseases' },
]

export function DiagnosisPage() {
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState('/diagnosis/requests')
  const [rows, setRows] = useState<unknown[]>([])
  const [notes, setNotes] = useState('بقع بنية على أوراق الطماطم')
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
      setError(requestError instanceof Error ? requestError.message : 'Unable to load diagnosis records.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void load()
  }, [token, organizationId, activePath])

  const submitRequest = async () => {
    if (!token) return
    setError('')
    setMessage('')
    try {
      await createDiagnosisRequest(token, {
        reference: `DX-WEB-${Date.now()}`,
        notes,
      }, organizationId ?? undefined)
      setMessage('Diagnosis request submitted. Results are decision support only.')
      await load('/diagnosis/requests')
      setActivePath('/diagnosis/requests')
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Unable to submit diagnosis request.')
    }
  }

  return (
    <Panel eyebrow="DECISION SUPPORT" title="Disease diagnosis">
      <p className="notice">Diagnosis outputs are agricultural decision support only and are not authoritative scientific diagnoses.</p>
      <ModuleTabs tabs={diagnosisTabs} activePath={activePath} onSelect={setActivePath} />
      {activePath === '/diagnosis/requests' && (
        <div className="search-bar">
          <input value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="وصف الأعراض / Symptom notes" aria-label="Diagnosis notes" dir="auto" />
          <button type="button" onClick={() => void submitRequest()}>Submit case</button>
        </div>
      )}
      {message && <p className="notice">{message}</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">Loading diagnosis records…</p> : <RecordList rows={rows} emptyLabel="No diagnosis records yet." />}
    </Panel>
  )
}
