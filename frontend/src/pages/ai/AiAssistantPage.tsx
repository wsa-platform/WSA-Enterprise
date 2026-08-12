import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  listAssistantConversations,
  sendAssistantMessage,
  startAssistantConversation,
  type AiAssistantReply,
  type AiConversationMessage,
  type AiConversationRecord,
} from '../../api/assistant'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'

export function AiAssistantPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [domain, setDomain] = useState('agriculture')
  const [title, setTitle] = useState('')
  const [message, setMessage] = useState('')
  const [notice, setNotice] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [activeConversationId, setActiveConversationId] = useState<number | null>(null)
  const [thread, setThread] = useState<AiConversationMessage[]>([])

  const hasAccess = can('ai.assistant') || can('ai.use')

  const { data: conversationsPayload, loading, error, reload } = useAsyncData(async () => {
    if (!token || !hasAccess) return null
    return listAssistantConversations(token, organizationId ?? undefined)
  }, [token, organizationId, hasAccess])

  if (!hasAccess) {
    return <ErrorBanner message={t('aiAssistant.noPermission')} />
  }

  const conversations = conversationsPayload?.data ?? []

  const appendReply = (reply: AiAssistantReply, userText: string) => {
    setActiveConversationId(reply.conversation_id)
    setThread((current) => [
      ...current,
      {
        id: Date.now(),
        conversation_id: reply.conversation_id,
        role: 'user',
        content: userText,
        created_at: new Date().toISOString(),
      },
      reply.message,
    ])
  }

  const startConversation = async () => {
    if (!token || !message.trim()) return
    setSubmitting(true)
    setNotice('')
    try {
      const reply = await startAssistantConversation(token, {
        domain,
        title: title || undefined,
        message: message.trim(),
      }, organizationId ?? undefined)
      appendReply(reply, message.trim())
      setMessage('')
      setTitle('')
      setNotice(t('aiAssistant.conversationStarted'))
      await reload()
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('aiAssistant.sendFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  const continueConversation = async () => {
    if (!token || !activeConversationId || !message.trim()) return
    setSubmitting(true)
    setNotice('')
    try {
      const reply = await sendAssistantMessage(token, activeConversationId, message.trim(), organizationId ?? undefined)
      appendReply(reply, message.trim())
      setMessage('')
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('aiAssistant.sendFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  const selectConversation = (conversation: AiConversationRecord) => {
    setActiveConversationId(conversation.id)
    setThread([])
    setNotice(t('aiAssistant.continueConversation', { id: conversation.id }))
  }

  return <>
    <PageHeader
      eyebrow={t('nav.ai')}
      title={t('aiAssistant.title')}
      description={t('aiAssistant.description')}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {notice && <p className="notice">{notice}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('aiAssistant.conversations')}</p><h2>{t('aiAssistant.history')}</h2></div></div>
      {loading ? <p className="loading">{t('aiAssistant.loadingConversations')}</p> : conversations.length === 0 ? (
        <EmptyState title={t('aiAssistant.noConversations')} description={t('aiAssistant.noConversationsDescription')} />
      ) : (
        <div className="module-results">
          {conversations.map((conversation) => (
            <button
              key={conversation.id}
              type="button"
              className={`record-card ${activeConversationId === conversation.id ? 'selected' : ''}`}
              onClick={() => selectConversation(conversation)}
            >
              <strong dir="auto">{conversation.title ?? t('aiAssistant.untitled', { domain: conversation.domain })}</strong>
              <span>{conversation.domain}</span>
            </button>
          ))}
        </div>
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.message')}</p><h2>{t('aiAssistant.chat')}</h2></div></div>
      <div className="conversation-thread">
        {thread.length === 0 ? <p className="muted">{t('aiAssistant.emptyThread')}</p> : thread.map((entry) => (
          <article key={`${entry.id}-${entry.role}`} className={`chat-bubble chat-${entry.role}`}>
            <span className="chat-role">{entry.role === 'assistant' ? t('aiAssistant.assistant') : t('aiAssistant.you')}</span>
            <p dir="auto">{entry.content}</p>
          </article>
        ))}
      </div>
      <div className="record-form">
        {!activeConversationId && (
          <>
            <label>
              {t('aiAssistant.domain')}
              <input value={domain} onChange={(event) => setDomain(event.target.value)} dir="auto" />
            </label>
            <label>
              {t('aiAssistant.conversationTitle')}
              <input value={title} onChange={(event) => setTitle(event.target.value)} dir="auto" />
            </label>
          </>
        )}
        <label>
          {t('aiAssistant.messagePlaceholder')}
          <textarea value={message} onChange={(event) => setMessage(event.target.value)} rows={4} dir="auto" />
        </label>
        <button
          type="button"
          disabled={submitting || !message.trim()}
          onClick={() => void (activeConversationId ? continueConversation() : startConversation())}
        >
          {submitting ? t('aiAssistant.sending') : t('aiAssistant.send')}
        </button>
      </div>
    </section>
  </>
}
