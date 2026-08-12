import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { createMarketingConsent, listMarketingConsents, type MarketingChannel, type MarketingConsent } from '../../api/marketing'
import { DataTable, PaginationBar } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'

const channels: MarketingChannel[] = ['email', 'sms', 'whatsapp']

export function ConsentPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [page, setPage] = useState(1)
  const [channel, setChannel] = useState<MarketingChannel>('email')
  const [email, setEmail] = useState('')
  const [phone, setPhone] = useState('')
  const [optedIn, setOptedIn] = useState(true)
  const [source, setSource] = useState('')
  const [notice, setNotice] = useState('')

  const canManage = can('marketing.manage') || can('marketing.admin')

  const { data: payload, loading, error, reload } = useAsyncData(async () => {
    if (!token || !can('marketing.view')) return null
    return listMarketingConsents(token, organizationId ?? undefined, page)
  }, [token, organizationId, page, can])

  if (!can('marketing.view')) {
    return <ErrorBanner message={t('marketing.noPermissionView')} />
  }

  const recordConsent = async () => {
    if (!token || !canManage) return
    setNotice('')
    try {
      await createMarketingConsent(token, {
        channel,
        email: email || undefined,
        phone: phone || undefined,
        opted_in: optedIn,
        source: source || undefined,
      }, organizationId ?? undefined)
      setEmail('')
      setPhone('')
      setSource('')
      setNotice(t('marketing.consentRecorded'))
      await reload()
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('marketing.consentRecordFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('nav.marketing')}
      title={t('marketing.consentTitle')}
      description={t('marketing.consentDescription')}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {notice && <p className="notice">{notice}</p>}

    {canManage && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.create')}</p><h2>{t('marketing.recordConsent')}</h2></div></div>
        <div className="record-form">
          <label>
            {t('marketing.channelLabel')}
            <select value={channel} onChange={(event) => setChannel(event.target.value as MarketingChannel)}>
              {channels.map((value) => (
                <option key={value} value={value}>{t(`marketing.channel.${value}`)}</option>
              ))}
            </select>
          </label>
          <label>
            {t('common.email')}
            <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('marketing.phone')}
            <input value={phone} onChange={(event) => setPhone(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('marketing.optedIn')}
            <select value={optedIn ? 'true' : 'false'} onChange={(event) => setOptedIn(event.target.value === 'true')}>
              <option value="true">{t('marketing.optIn')}</option>
              <option value="false">{t('marketing.optOut')}</option>
            </select>
          </label>
          <label>
            {t('marketing.source')}
            <input value={source} onChange={(event) => setSource(event.target.value)} dir="auto" />
          </label>
          <button type="button" onClick={() => void recordConsent()}>{t('marketing.recordConsent')}</button>
        </div>
      </section>
    )}

    <section className="panel">
      {loading ? (
        <p className="loading">{t('marketing.loadingConsents')}</p>
      ) : (
        <>
          <DataTable<MarketingConsent>
            rows={payload?.data ?? []}
            rowKey={(row) => row.id}
            emptyLabel={t('marketing.noConsents')}
            columns={[
              { key: 'channel', header: t('marketing.channelLabel'), render: (row) => t(`marketing.channel.${row.channel}`) },
              { key: 'email', header: t('common.email'), render: (row) => row.email ?? '—' },
              { key: 'phone', header: t('marketing.phone'), render: (row) => row.phone ?? '—' },
              { key: 'opted_in', header: t('marketing.optedIn'), render: (row) => row.opted_in ? t('marketing.optIn') : t('marketing.optOut') },
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
