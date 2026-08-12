import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { createModuleRecord, getModule, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { RecordForm } from '../components/RecordForm'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'

export function TrainingPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState('/training/courses')
  const [rows, setRows] = useState<unknown[]>([])
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(true)

  const trainingTabs = [
    { label: t('modules.courses'), path: '/training/courses' },
    { label: t('modules.lessons'), path: '/training/lessons' },
    { label: t('modules.myEnrollments'), path: '/training/enrollments' },
  ]

  const courseFields = [
    { name: 'code', label: t('common.code'), required: true },
    { name: 'title', label: t('common.title'), required: true },
    { name: 'title_ar', label: t('modules.arabicTitle') },
  ]

  const load = async (path = activePath) => {
    if (!token) return
    setLoading(true)
    setError('')
    try {
      setRows(unwrapModuleRows(await getModule(token, path, organizationId ?? undefined)))
    } catch (requestError) {
      setError(translateApiError(requestError) || t('modules.trainingLoadFailed'))
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
    setMessage(t('modules.trainingCourseCreated'))
    await load('/training/courses')
  }

  return (
    <Panel eyebrow={t('modules.education')} title={t('modules.trainingTitle')}>
      <ModuleTabs tabs={trainingTabs} activePath={activePath} onSelect={setActivePath} />
      {activePath === '/training/courses' && (
        <RecordForm title={t('modules.createCourse')} fields={courseFields} submitLabel={t('modules.createCourseButton')} onSubmit={createCourse} />
      )}
      {message && <p className="notice">{message}</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">{t('modules.loadingTraining')}</p> : <RecordList rows={rows} emptyLabel={t('modules.noTrainingRecords')} />}
    </Panel>
  )
}
