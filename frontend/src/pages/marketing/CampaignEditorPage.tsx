import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useParams } from 'react-router-dom'
import {
  cancelMarketingCampaign,
  createMarketingCampaign,
  deleteMarketingCampaign,
  getMarketingCampaign,
  listMarketingSegments,
  listMarketingTemplates,
  previewMarketingCampaign,
  processMarketingCampaign,
  scheduleMarketingCampaign,
  testSendMarketingCampaign,
  updateMarketingCampaign,
  type MarketingCampaignPreview,
  type MarketingChannel,
} from '../../api/marketing'
import { PageHeader } from '../../components/PageHeader'
import { ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'

const channels: MarketingChannel[] = ['email', 'sms', 'whatsapp']

export function CampaignEditorPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const navigate = useNavigate()
  const { campaignId } = useParams()
  const isNew = campaignId === 'new'
  const numericId = isNew ? null : Number(campaignId)

  const [name, setName] = useState('')
  const [description, setDescription] = useState('')
  const [channel, setChannel] = useState<MarketingChannel>('email')
  const [segmentId, setSegmentId] = useState('')
  const [templateId, setTemplateId] = useState('')
  const [subject, setSubject] = useState('')
  const [body, setBody] = useState('')
  const [previewLocale, setPreviewLocale] = useState('en')
  const [preview, setPreview] = useState<MarketingCampaignPreview | null>(null)
  const [notice, setNotice] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const canView = can('marketing.view')
  const canManage = can('marketing.manage')

  const { data: campaign, loading, error, reload } = useAsyncData(async () => {
    if (!token || !canView || isNew || !numericId) return null
    return getMarketingCampaign(token, numericId, organizationId ?? undefined)
  }, [token, organizationId, numericId, isNew, canView])

  const { data: segmentsPayload } = useAsyncData(async () => {
    if (!token || !canView) return null
    return listMarketingSegments(token, organizationId ?? undefined)
  }, [token, organizationId, canView])

  const { data: templatesPayload } = useAsyncData(async () => {
    if (!token || !canView) return null
    return listMarketingTemplates(token, organizationId ?? undefined)
  }, [token, organizationId, canView])

  useEffect(() => {
    if (!campaign) return
    setName(campaign.name)
    setDescription(campaign.description ?? '')
    setChannel(campaign.channel)
    setSegmentId(campaign.audience_segment_id != null ? String(campaign.audience_segment_id) : '')
    setTemplateId(campaign.template_id != null ? String(campaign.template_id) : '')
    const content = campaign.content ?? {}
    setSubject(String(content.subject ?? ''))
    setBody(String(content.body ?? ''))
  }, [campaign])

  if (!canView) {
    return <ErrorBanner message={t('marketing.noPermissionView')} />
  }

  const saveCampaign = async () => {
    if (!token || !canManage || !name.trim()) return
    setSubmitting(true)
    setNotice('')
    const payload = {
      name: name.trim(),
      description: description || undefined,
      channel,
      audience_segment_id: segmentId ? Number(segmentId) : null,
      template_id: templateId ? Number(templateId) : null,
      content: subject || body ? { subject: subject || undefined, body: body || undefined } : undefined,
    }
    try {
      if (isNew) {
        const created = await createMarketingCampaign(token, payload, organizationId ?? undefined)
        setNotice(t('marketing.campaignCreated'))
        navigate(`/marketing/campaigns/${created.id}`, { replace: true })
      } else if (numericId) {
        await updateMarketingCampaign(token, numericId, payload, organizationId ?? undefined)
        setNotice(t('marketing.campaignUpdated'))
        await reload()
      }
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('marketing.campaignSaveFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  const runAction = async (action: 'schedule' | 'cancel' | 'preview' | 'testSend' | 'process' | 'delete') => {
    if (!token || !numericId) return
    setSubmitting(true)
    setNotice('')
    try {
      switch (action) {
        case 'schedule':
          await scheduleMarketingCampaign(token, numericId, organizationId ?? undefined)
          setNotice(t('marketing.campaignScheduled'))
          break
        case 'cancel':
          await cancelMarketingCampaign(token, numericId, organizationId ?? undefined)
          setNotice(t('marketing.campaignCancelled'))
          break
        case 'preview':
          setPreview(await previewMarketingCampaign(token, numericId, previewLocale, organizationId ?? undefined))
          setNotice(t('marketing.previewReady'))
          break
        case 'testSend':
          await testSendMarketingCampaign(token, numericId, previewLocale, organizationId ?? undefined)
          setNotice(t('marketing.testSendComplete'))
          break
        case 'process':
          await processMarketingCampaign(token, numericId, organizationId ?? undefined)
          setNotice(t('marketing.campaignProcessed'))
          break
        case 'delete':
          if (!window.confirm(t('marketing.confirmDeleteCampaign'))) return
          await deleteMarketingCampaign(token, numericId, organizationId ?? undefined)
          navigate('/marketing/campaigns')
          return
      }
      await reload()
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('marketing.actionFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  return <>
    <PageHeader
      eyebrow={t('nav.marketing')}
      title={isNew ? t('marketing.newCampaign') : t('marketing.editCampaign')}
      description={t('marketing.campaignEditorDescription')}
      actions={<Link className="link-button" to="/marketing/campaigns">{t('marketing.backToCampaigns')}</Link>}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {notice && <p className="notice">{notice}</p>}

    {loading && !isNew ? (
      <p className="loading">{t('marketing.loadingCampaign')}</p>
    ) : (
      <>
        <section className="panel">
          <div className="panel-heading">
            <div><p className="eyebrow">{t('marketing.details')}</p><h2>{t('marketing.campaignForm')}</h2></div>
            {!isNew && campaign && <StatusBadge status={campaign.status} />}
          </div>
          <div className="record-form">
            <label>
              {t('common.name')}
              <input value={name} onChange={(event) => setName(event.target.value)} disabled={!canManage || (!isNew && campaign?.status !== 'draft')} dir="auto" />
            </label>
            <label>
              {t('common.description')}
              <textarea value={description} onChange={(event) => setDescription(event.target.value)} rows={2} disabled={!canManage || (!isNew && campaign?.status !== 'draft')} dir="auto" />
            </label>
            <label>
              {t('marketing.channelLabel')}
              <select value={channel} onChange={(event) => setChannel(event.target.value as MarketingChannel)} disabled={!canManage || (!isNew && campaign?.status !== 'draft')}>
                {channels.map((value) => (
                  <option key={value} value={value}>{t(`marketing.channel.${value}`)}</option>
                ))}
              </select>
            </label>
            <label>
              {t('marketing.segment')}
              <select value={segmentId} onChange={(event) => setSegmentId(event.target.value)} disabled={!canManage || (!isNew && campaign?.status !== 'draft')}>
                <option value="">{t('marketing.noSegment')}</option>
                {(segmentsPayload?.data ?? []).map((segment) => (
                  <option key={segment.id} value={segment.id}>{segment.name}</option>
                ))}
              </select>
            </label>
            <label>
              {t('marketing.template')}
              <select value={templateId} onChange={(event) => setTemplateId(event.target.value)} disabled={!canManage || (!isNew && campaign?.status !== 'draft')}>
                <option value="">{t('marketing.noTemplate')}</option>
                {(templatesPayload?.data ?? []).map((template) => (
                  <option key={template.id} value={template.id}>{template.name}</option>
                ))}
              </select>
            </label>
            <label>
              {t('marketing.subject')}
              <input value={subject} onChange={(event) => setSubject(event.target.value)} disabled={!canManage || (!isNew && campaign?.status !== 'draft')} dir="auto" />
            </label>
            <label>
              {t('marketing.body')}
              <textarea value={body} onChange={(event) => setBody(event.target.value)} rows={4} disabled={!canManage || (!isNew && campaign?.status !== 'draft')} dir="auto" />
            </label>
            {canManage && (isNew || campaign?.status === 'draft') && (
              <button type="button" disabled={submitting || !name.trim()} onClick={() => void saveCampaign()}>
                {submitting ? t('common.saving') : t('common.save')}
              </button>
            )}
          </div>
        </section>

        {!isNew && canManage && campaign && (
          <section className="panel">
            <div className="panel-heading"><div><p className="eyebrow">{t('marketing.actions')}</p><h2>{t('marketing.campaignActions')}</h2></div></div>
            <div className="record-form">
              <label>
                {t('marketing.previewLocale')}
                <input value={previewLocale} onChange={(event) => setPreviewLocale(event.target.value)} dir="auto" />
              </label>
              <div className="header-actions">
                <button type="button" disabled={submitting} onClick={() => void runAction('schedule')}>{t('marketing.schedule')}</button>
                <button type="button" disabled={submitting} onClick={() => void runAction('cancel')}>{t('marketing.cancelCampaign')}</button>
                <button type="button" disabled={submitting} onClick={() => void runAction('preview')}>{t('marketing.preview')}</button>
                <button type="button" disabled={submitting} onClick={() => void runAction('testSend')}>{t('marketing.testSend')}</button>
                <button type="button" disabled={submitting} onClick={() => void runAction('process')}>{t('marketing.process')}</button>
                {campaign.status === 'draft' && (
                  <button type="button" className="link-button" disabled={submitting} onClick={() => void runAction('delete')}>{t('common.delete')}</button>
                )}
              </div>
            </div>
            {preview && (
              <div className="code-block">
                <p><strong>{t('marketing.subject')}:</strong> {preview.subject}</p>
                <p dir="auto">{preview.body}</p>
              </div>
            )}
          </section>
        )}
      </>
    )}
  </>
}
