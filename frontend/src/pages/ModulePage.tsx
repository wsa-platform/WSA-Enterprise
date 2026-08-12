import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { ApiError, createModuleRecord, deleteModuleRecord, getModule, modulePaginationMeta, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { ConfirmDialog } from '../components/ConfirmDialog'
import { RecordForm } from '../components/RecordForm'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'

type ModuleTab = { labelKey: string; path: string }
type CreateField = { name: string; labelKey: string; required?: boolean }

type ModulePageProps = {
  eyebrow: string
  title: string
  tabs: ModuleTab[]
  defaultPath: string
  createFields?: CreateField[]
}

export function ModulePage({ eyebrow, title, tabs, defaultPath, createFields }: ModulePageProps) {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState(defaultPath)
  const [rows, setRows] = useState<unknown[]>([])
  const [pagination, setPagination] = useState<{ currentPage: number; lastPage: number; total: number } | null>(null)
  const [error, setError] = useState('')
  const [forbidden, setForbidden] = useState(false)
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(true)
  const [pendingDelete, setPendingDelete] = useState<Record<string, unknown> | null>(null)

  const translatedTabs = tabs.map(({ labelKey, path }) => ({ label: t(labelKey), path }))
  const translatedCreateFields = createFields?.map(({ name, labelKey, required }) => ({
    name,
    label: t(labelKey),
    required,
  }))

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
        setError(t('modules.forbiddenMessage'))
      } else {
        setError(translateApiError(requestError) || t('modules.loadFailed'))
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
      setMessage(t('modules.recordCreated'))
      await load()
    } catch (requestError) {
      setError(translateApiError(requestError) || t('modules.recordCreateFailed'))
    }
  }

  const removeRecord = async (record: Record<string, unknown>) => {
    if (!token || typeof record.id !== 'number') return
    const basePath = activePath.split('?')[0]
    try {
      await deleteModuleRecord(token, basePath, record.id, organizationId ?? undefined)
      setMessage(t('modules.recordDeleted'))
      setPendingDelete(null)
      await load()
    } catch (requestError) {
      setError(translateApiError(requestError) || t('modules.recordDeleteFailed'))
    }
  }

  return (
    <Panel eyebrow={eyebrow} title={title}>
      <ModuleTabs tabs={translatedTabs} activePath={activePath} onSelect={setActivePath} />
      {translatedCreateFields && !forbidden && (
        <RecordForm
          title={t('modules.createRecord')}
          fields={translatedCreateFields}
          submitLabel={t('common.create')}
          onSubmit={createRecord}
        />
      )}
      {message && <p className="notice">{message}</p>}
      {forbidden && <p className="banner forbidden">{t('modules.accessDenied')}</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">{t('modules.loadingRecords')}</p> : (
        <>
          {pagination && (
            <p className="muted pagination-meta">
              {t('modules.showingPage', { current: pagination.currentPage, last: pagination.lastPage, total: pagination.total })}
            </p>
          )}
          <RecordList rows={rows} emptyLabel={forbidden ? t('modules.recordsUnavailable') : t('modules.noRecords')} />
          {rows.length > 0 && createFields && !forbidden && (
            <div className="module-results">
              {rows.slice(0, 8).map((row, index) => {
                const record = row as Record<string, unknown>
                if (typeof record.id !== 'number') return null
                return (
                  <button key={index} type="button" className="link-button danger-link" onClick={() => setPendingDelete(record)}>
                    {t('modules.deleteRecordLabel', { id: record.id })}
                  </button>
                )
              })}
            </div>
          )}
        </>
      )}
      <ConfirmDialog
        open={pendingDelete !== null}
        title={t('modules.deleteRecord')}
        message={t('modules.deleteRecordConfirm')}
        confirmLabel={t('common.delete')}
        onCancel={() => setPendingDelete(null)}
        onConfirm={() => pendingDelete && void removeRecord(pendingDelete)}
      />
    </Panel>
  )
}

export const farmTabs: ModuleTab[] = [
  { labelKey: 'nav.farms', path: '/farm/farms' },
  { labelKey: 'modules.regions', path: '/farm/regions' },
  { labelKey: 'modules.fields', path: '/farm/fields' },
  { labelKey: 'modules.blocks', path: '/farm/blocks' },
  { labelKey: 'modules.greenhouses', path: '/farm/greenhouses' },
  { labelKey: 'modules.irrigation', path: '/farm/irrigation-zones' },
]

export const cropTabs: ModuleTab[] = [
  { labelKey: 'modules.cropTypes', path: '/crop/types' },
  { labelKey: 'modules.varieties', path: '/crop/varieties' },
  { labelKey: 'modules.seasons', path: '/crop/seasons' },
  { labelKey: 'modules.growthStages', path: '/crop/growth-stages' },
  { labelKey: 'modules.harvests', path: '/crop/harvests' },
  { labelKey: 'modules.yields', path: '/crop/yields' },
]

export const soilTabs: ModuleTab[] = [
  { labelKey: 'modules.analyses', path: '/soil/analyses' },
  { labelKey: 'modules.nutrients', path: '/soil/nutrients' },
  { labelKey: 'modules.recommendations', path: '/soil/recommendations' },
]

export const farmCreateFields: CreateField[] = [
  { name: 'code', labelKey: 'common.code', required: true },
  { name: 'name', labelKey: 'common.name', required: true },
]

export const cropCreateFields: CreateField[] = [
  { name: 'code', labelKey: 'common.code', required: true },
  { name: 'name', labelKey: 'common.name', required: true },
]

export const soilCreateFields: CreateField[] = [
  { name: 'sample_reference', labelKey: 'modules.sampleReference', required: true },
  { name: 'sampled_at', labelKey: 'modules.sampledAt', required: true },
]
