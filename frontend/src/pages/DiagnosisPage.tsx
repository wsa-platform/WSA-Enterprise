import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { createDiagnosisRequest, getModule, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'

export function DiagnosisPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState('/diagnosis/requests')
  const [rows, setRows] = useState<unknown[]>([])
  const [notes, setNotes] = useState('بقع بنية على أوراق الطماطم')
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(true)

  const diagnosisTabs = [
    { label: t('modules.requests'), path: '/diagnosis/requests' },
    { label: t('modules.categories'), path: '/diagnosis/categories' },
    { label: t('modules.symptoms'), path: '/diagnosis/symptoms' },
    { label: t('modules.diseases'), path: '/diagnosis/diseases' },
  ]

  const load = async (path = activePath) => {
    if (!token) return
    setLoading(true)
    setError('')
    try {
      setRows(unwrapModuleRows(await getModule(token, path, organizationId ?? undefined)))
    } catch (requestError) {
      setError(translateApiError(requestError) || t('modules.diagnosisLoadFailed'))
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
      setMessage(t('modules.diagnosisSubmitted'))
      await load('/diagnosis/requests')
      setActivePath('/diagnosis/requests')
    } catch (requestError) {
      setError(translateApiError(requestError) || t('modules.diagnosisSubmitFailed'))
    }
  }

  return (
    <Panel eyebrow={t('modules.decisionSupport')} title={t('modules.diagnosisTitle')}>
      <p className="notice">{t('modules.diagnosisNotice')}</p>
      <ModuleTabs tabs={diagnosisTabs} activePath={activePath} onSelect={setActivePath} />
      {activePath === '/diagnosis/requests' && (
        <div className="search-bar">
          <input value={notes} onChange={(event) => setNotes(event.target.value)} placeholder={t('modules.diagnosisNotesPlaceholder')} aria-label={t('modules.diagnosisNotesAria')} dir="auto" />
          <button type="button" onClick={() => void submitRequest()}>{t('modules.submitCase')}</button>
        </div>
      )}
      {message && <p className="notice">{message}</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">{t('modules.loadingDiagnosis')}</p> : <RecordList rows={rows} emptyLabel={t('modules.noDiagnosisRecords')} />}
    </Panel>
  )
}
