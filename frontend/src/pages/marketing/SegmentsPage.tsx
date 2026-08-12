import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { createMarketingSegment, listMarketingSegments, type MarketingAudienceSegment } from '../../api/marketing'
import { DataTable, PaginationBar } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'

export function SegmentsPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [page, setPage] = useState(1)
  const [name, setName] = useState('')
  const [description, setDescription] = useState('')
  const [criteria, setCriteria] = useState('{"user_type":"member"}')
  const [notice, setNotice] = useState('')

  const canManage = can('marketing.manage')

  const { data: payload, loading, error, reload } = useAsyncData(async () => {
    if (!token || !can('marketing.view')) return null
    return listMarketingSegments(token, organizationId ?? undefined, page)
  }, [token, organizationId, page, can])

  if (!can('marketing.view')) {
    return <ErrorBanner message={t('marketing.noPermissionView')} />
  }

  const createSegment = async () => {
    if (!token || !canManage || !name.trim()) return
    setNotice('')
    try {
      let parsedCriteria: Record<string, unknown> | undefined
      if (criteria.trim()) {
        parsedCriteria = JSON.parse(criteria) as Record<string, unknown>
      }
      await createMarketingSegment(token, {
        name: name.trim(),
        description: description || undefined,
        criteria: parsedCriteria,
      }, organizationId ?? undefined)
      setName('')
      setDescription('')
      setNotice(t('marketing.segmentCreated'))
      await reload()
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('marketing.segmentCreateFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('nav.marketing')}
      title={t('marketing.segmentsTitle')}
      description={t('marketing.segmentsDescription')}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {notice && <p className="notice">{notice}</p>}

    {canManage && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.create')}</p><h2>{t('marketing.newSegment')}</h2></div></div>
        <div className="record-form">
          <label>
            {t('common.name')}
            <input value={name} onChange={(event) => setName(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('common.description')}
            <textarea value={description} onChange={(event) => setDescription(event.target.value)} rows={2} dir="auto" />
          </label>
          <label>
            {t('marketing.criteria')}
            <textarea value={criteria} onChange={(event) => setCriteria(event.target.value)} rows={3} dir="ltr" />
          </label>
          <button type="button" disabled={!name.trim()} onClick={() => void createSegment()}>{t('common.create')}</button>
        </div>
      </section>
    )}

    <section className="panel">
      {loading ? (
        <p className="loading">{t('marketing.loadingSegments')}</p>
      ) : (
        <>
          <DataTable<MarketingAudienceSegment>
            rows={payload?.data ?? []}
            rowKey={(row) => row.id}
            emptyLabel={t('marketing.noSegments')}
            columns={[
              { key: 'name', header: t('common.name'), render: (row) => row.name },
              { key: 'description', header: t('common.description'), render: (row) => row.description ?? '—' },
            ]}
          />
          {payload && (
            <PaginationBar page={payload.current_page} lastPage={payload.last_page} total={payload.total} onPageChange={setPage} />
          )}
        </>
      )}
    </section>
  </>
}
