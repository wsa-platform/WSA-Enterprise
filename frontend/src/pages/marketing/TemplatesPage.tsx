import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { createMarketingTemplate, listMarketingTemplates, type MarketingChannel, type MarketingTemplate } from '../../api/marketing'
import { DataTable, PaginationBar } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'

const channels: MarketingChannel[] = ['email', 'sms', 'whatsapp']

export function TemplatesPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [page, setPage] = useState(1)
  const [slug, setSlug] = useState('')
  const [name, setName] = useState('')
  const [channel, setChannel] = useState<MarketingChannel>('email')
  const [subject, setSubject] = useState('')
  const [body, setBody] = useState('')
  const [notice, setNotice] = useState('')

  const canManage = can('marketing.manage')

  const { data: payload, loading, error, reload } = useAsyncData(async () => {
    if (!token || !can('marketing.view')) return null
    return listMarketingTemplates(token, organizationId ?? undefined, page)
  }, [token, organizationId, page, can])

  if (!can('marketing.view')) {
    return <ErrorBanner message={t('marketing.noPermissionView')} />
  }

  const createTemplate = async () => {
    if (!token || !canManage || !slug.trim() || !name.trim()) return
    setNotice('')
    try {
      await createMarketingTemplate(token, {
        slug: slug.trim(),
        name: name.trim(),
        channel,
        translations: {
          en: { subject: subject || undefined, body: body || undefined },
        },
      }, organizationId ?? undefined)
      setSlug('')
      setName('')
      setSubject('')
      setBody('')
      setNotice(t('marketing.templateCreated'))
      await reload()
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('marketing.templateCreateFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('nav.marketing')}
      title={t('marketing.templatesTitle')}
      description={t('marketing.templatesDescription')}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {notice && <p className="notice">{notice}</p>}

    {canManage && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.create')}</p><h2>{t('marketing.newTemplate')}</h2></div></div>
        <div className="record-form">
          <label>
            {t('marketing.slug')}
            <input value={slug} onChange={(event) => setSlug(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('common.name')}
            <input value={name} onChange={(event) => setName(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('marketing.channelLabel')}
            <select value={channel} onChange={(event) => setChannel(event.target.value as MarketingChannel)}>
              {channels.map((value) => (
                <option key={value} value={value}>{t(`marketing.channel.${value}`)}</option>
              ))}
            </select>
          </label>
          <label>
            {t('marketing.subject')}
            <input value={subject} onChange={(event) => setSubject(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('marketing.body')}
            <textarea value={body} onChange={(event) => setBody(event.target.value)} rows={3} dir="auto" />
          </label>
          <button type="button" disabled={!slug.trim() || !name.trim()} onClick={() => void createTemplate()}>{t('common.create')}</button>
        </div>
      </section>
    )}

    <section className="panel">
      {loading ? (
        <p className="loading">{t('marketing.loadingTemplates')}</p>
      ) : (
        <>
          <DataTable<MarketingTemplate>
            rows={payload?.data ?? []}
            rowKey={(row) => row.id}
            emptyLabel={t('marketing.noTemplates')}
            columns={[
              { key: 'name', header: t('common.name'), render: (row) => row.name },
              { key: 'slug', header: t('marketing.slug'), render: (row) => row.slug },
              { key: 'channel', header: t('marketing.channelLabel'), render: (row) => t(`marketing.channel.${row.channel}`) },
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
