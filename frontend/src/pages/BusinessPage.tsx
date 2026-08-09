import { useEffect, useState } from 'react'
import { createModuleRecord, getModule, unwrapModuleRows } from '../api'
import { ModuleTabs, Panel, RecordList } from '../components/AppShell'
import { RecordForm } from '../components/RecordForm'
import { useAuth } from '../context/AuthContext'

const businessTabs = [
  { label: 'Customers', path: '/catalog/customers' },
  { label: 'Products', path: '/catalog/products' },
  { label: 'Companies', path: '/directory/companies' },
  { label: 'Inventory', path: '/inventory' },
  { label: 'Sales orders', path: '/sales-orders' },
]

const createFieldsByPath: Record<string, Array<{ name: string; label: string; required?: boolean }>> = {
  '/catalog/customers': [
    { name: 'code', label: 'Code', required: true },
    { name: 'name', label: 'Name', required: true },
  ],
  '/catalog/products': [
    { name: 'sku', label: 'SKU', required: true },
    { name: 'name', label: 'Name', required: true },
  ],
  '/directory/companies': [
    { name: 'name', label: 'Company name', required: true },
  ],
}

export function BusinessPage() {
  const { token, organizationId } = useAuth()
  const [activePath, setActivePath] = useState('/catalog/customers')
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
      setError(requestError instanceof Error ? requestError.message : 'Unable to load business records.')
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
    setMessage('Business record created.')
    await load()
  }

  const createFields = createFieldsByPath[activePath.split('?')[0]]

  return (
    <Panel eyebrow="BUSINESS" title="Catalog, directory & commerce">
      <p className="notice">Business data is scoped to the active organization. Writes require business.manage permissions.</p>
      <ModuleTabs tabs={businessTabs} activePath={activePath} onSelect={setActivePath} />
      {createFields && (
        <RecordForm title="Create record" fields={createFields} submitLabel="Create" onSubmit={createRecord} />
      )}
      {message && <p className="notice">{message}</p>}
      {error && <p className="error">{error}</p>}
      {loading ? <p className="loading">Loading business records…</p> : <RecordList rows={rows} emptyLabel="No business records found." />}
    </Panel>
  )
}
