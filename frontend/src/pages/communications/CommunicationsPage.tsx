import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import {
  composeCommunication,
  createContact,
  fetchCommunicationsInbox,
  fetchContacts,
  sendCommunication,
  type CommunicationChannel,
  type InboxItem,
} from '../../api/communications'
import { ModuleTabs } from '../../components/AppShell'
import { DataTable } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'
import { unwrapModuleRows } from '../../api'

export function CommunicationsPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [tab, setTab] = useState<'inbox' | 'compose' | 'contacts'>('inbox')
  const [subject, setSubject] = useState('')
  const [body, setBody] = useState('')
  const [channel, setChannel] = useState<CommunicationChannel>('email')
  const [recipientEmail, setRecipientEmail] = useState('')
  const [contactName, setContactName] = useState('')
  const [contactEmail, setContactEmail] = useState('')
  const [contactPhone, setContactPhone] = useState('')
  const [notice, setNotice] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const { data: inbox, loading: inboxLoading, error: inboxError, reload: reloadInbox } = useAsyncData(async () => {
    if (!token || !can('platform.view')) return null
    return fetchCommunicationsInbox(token, organizationId ?? undefined)
  }, [token, organizationId, can])

  const { data: contactsPayload, loading: contactsLoading, error: contactsError, reload: reloadContacts } = useAsyncData(async () => {
    if (!token || !can('platform.view')) return null
    return fetchContacts(token, organizationId ?? undefined)
  }, [token, organizationId, can])

  if (!can('platform.view')) {
    return <ErrorBanner message={t('communications.noPermission')} />
  }

  const sendMessage = async (event: FormEvent) => {
    event.preventDefault()
    if (!token || !subject.trim() || !body.trim()) return
    setSubmitting(true)
    setNotice('')
    try {
      const created = await composeCommunication(token, {
        subject: subject.trim(),
        body: body.trim(),
        channel,
        recipient_mode: 'individual',
        recipients: recipientEmail ? [{ email: recipientEmail.trim() }] : [],
      }, organizationId ?? undefined)
      const sent = await sendCommunication(token, created.id, organizationId ?? undefined)
      setNotice(t('communications.sent', { count: sent.delivery_stats?.sent ?? 1 }))
      setSubject('')
      setBody('')
      setRecipientEmail('')
      await reloadInbox()
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('communications.sendFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  const addContact = async (event: FormEvent) => {
    event.preventDefault()
    if (!token) return
    setSubmitting(true)
    setNotice('')
    try {
      await createContact(token, {
        name: contactName.trim() || undefined,
        email: contactEmail.trim() || undefined,
        phone: contactPhone.trim() || undefined,
      }, organizationId ?? undefined)
      setNotice(t('communications.contactSaved'))
      setContactName('')
      setContactEmail('')
      setContactPhone('')
      await reloadContacts()
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('communications.contactSaveFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  const contacts = unwrapModuleRows(contactsPayload ?? [])

  return <>
    <PageHeader
      eyebrow={t('nav.communications')}
      title={t('communications.title')}
      description={t('communications.description')}
    />
    {notice && <p className="notice">{notice}</p>}
    <ModuleTabs
      tabs={[
        { label: t('communications.inbox'), path: 'inbox' },
        { label: t('communications.compose'), path: 'compose' },
        { label: t('communications.contacts'), path: 'contacts' },
      ]}
      activePath={tab}
      onSelect={(path) => setTab(path as 'inbox' | 'compose' | 'contacts')}
    />

    {tab === 'inbox' && (
      <section className="panel">
        {inboxError && <ErrorBanner message={inboxError} onRetry={reloadInbox} />}
        {inboxLoading ? (
          <p className="loading">{t('common.loading')}</p>
        ) : (inbox?.data.length ?? 0) === 0 ? (
          <EmptyState title={t('communications.emptyInbox')} />
        ) : (
          <DataTable<InboxItem>
            rows={inbox?.data ?? []}
            rowKey={(row) => `${row.source}-${row.id}`}
            columns={[
              { key: 'title', header: t('common.title'), render: (row) => <span dir="auto">{row.title}</span> },
              { key: 'source', header: t('common.type'), render: (row) => <StatusBadge status={row.source} /> },
              { key: 'body', header: t('common.message'), render: (row) => row.body ?? '—' },
            ]}
          />
        )}
      </section>
    )}

    {tab === 'compose' && (
      <section className="panel">
        <form className="record-form" onSubmit={(event) => void sendMessage(event)}>
          <label>
            {t('communications.channel')}
            <select value={channel} onChange={(event) => setChannel(event.target.value as CommunicationChannel)}>
              <option value="email">{t('communications.channelEmail')}</option>
              <option value="sms">{t('communications.channelSms')}</option>
              <option value="whatsapp">{t('communications.channelWhatsapp')}</option>
            </select>
          </label>
          <label>
            {t('communications.recipientEmail')}
            <input value={recipientEmail} onChange={(event) => setRecipientEmail(event.target.value)} type="email" />
          </label>
          <label>
            {t('common.title')}
            <input value={subject} onChange={(event) => setSubject(event.target.value)} required dir="auto" />
          </label>
          <label>
            {t('common.message')}
            <textarea value={body} onChange={(event) => setBody(event.target.value)} rows={6} required dir="auto" />
          </label>
          <button disabled={submitting} type="submit">{submitting ? t('common.saving') : t('communications.send')}</button>
        </form>
      </section>
    )}

    {tab === 'contacts' && (
      <section className="panel">
        {contactsError && <ErrorBanner message={contactsError} onRetry={reloadContacts} />}
        <form className="record-form" onSubmit={(event) => void addContact(event)}>
          <label>
            {t('common.name')}
            <input value={contactName} onChange={(event) => setContactName(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('common.email')}
            <input value={contactEmail} onChange={(event) => setContactEmail(event.target.value)} type="email" />
          </label>
          <label>
            {t('communications.phone')}
            <input value={contactPhone} onChange={(event) => setContactPhone(event.target.value)} />
          </label>
          <button disabled={submitting} type="submit">{t('communications.addContact')}</button>
        </form>
        {contactsLoading ? (
          <p className="loading">{t('common.loading')}</p>
        ) : contacts.length === 0 ? (
          <EmptyState title={t('communications.noContacts')} />
        ) : (
          <DataTable
            rows={contacts}
            rowKey={(row) => row.id}
            columns={[
              { key: 'name', header: t('common.name'), render: (row) => row.name ?? '—' },
              { key: 'email', header: t('common.email'), render: (row) => row.email ?? '—' },
              { key: 'phone', header: t('communications.phone'), render: (row) => row.phone ?? '—' },
            ]}
          />
        )}
      </section>
    )}
  </>
}
