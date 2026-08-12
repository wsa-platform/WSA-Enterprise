import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { createModuleRecord, getModule, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { RecordForm } from '../components/RecordForm'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'

export function BusinessPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState('/catalog/customers')
  const [rows, setRows] = useState<unknown[]>([])
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(true)

  const businessTabs = [
    { label: t('modules.customers'), path: '/catalog/customers' },
    { label: t('modules.products'), path: '/catalog/products' },
    { label: t('modules.companies'), path: '/directory/companies' },
    { label: t('modules.inventory'), path: '/inventory' },
    { label: t('modules.salesOrders'), path: '/sales-orders' },
  ]

  const createFieldsByPath: Record<string, Array<{ name: string; label: string; required?: boolean }>> = {
    '/catalog/customers': [
      { name: 'code', label: t('common.code'), required: true },
      { name: 'name', label: t('common.name'), required: true },
    ],
    '/catalog/products': [
      { name: 'sku', label: t('modules.sku'), required: true },
      { name: 'name', label: t('common.name'), required: true },
    ],
    '/directory/companies': [
      { name: 'name', label: t('modules.companyName'), required: true },
    ],
  }

  const load = async (path = activePath) => {
    if (!token) return
    setLoading(true)
    setError('')
    try {
      setRows(unwrapModuleRows(await getModule(token, path, organizationId ?? undefined)))
    } catch (requestError) {
      setError(translateApiError(requestError) || t('modules.businessLoadFailed'))
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
    setMessage(t('modules.businessRecordCreated'))
    await load()
  }

  const createFields = createFieldsByPath[activePath.split('?')[0]]

  return (
    <Panel eyebrow={t('modules.business')} title={t('modules.businessTitle')}>
      <p className="notice">{t('modules.businessNotice')}</p>
      <ModuleTabs tabs={businessTabs} activePath={activePath} onSelect={setActivePath} />
      {createFields && (
        <RecordForm title={t('modules.createRecord')} fields={createFields} submitLabel={t('common.create')} onSubmit={createRecord} />
      )}
      {message && <p className="notice">{message}</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">{t('modules.loadingBusiness')}</p> : <RecordList rows={rows} emptyLabel={t('modules.noBusinessRecords')} />}
    </Panel>
  )
}
