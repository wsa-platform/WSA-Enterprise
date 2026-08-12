import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  archiveConversation,
  deleteConversation,
  executeAssistantAction,
  listAssistantConversations,
  sendAssistantMessage,
  showConversation,
  startAssistantConversation,
  type AiAssistantReply,
  type AiConversationMessage,
  type AiConversationRecord,
  type AiSuggestedAction,
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
  const [loadingThread, setLoadingThread] = useState(false)
  const [activeConversationId, setActiveConversationId] = useState<number | null>(null)
  const [thread, setThread] = useState<AiConversationMessage[]>([])
  const [suggestedActions, setSuggestedActions] = useState<AiSuggestedAction[]>([])

  const hasAccess = can('ai.assistant') || can('ai.use')
  const canExecuteActions = can('ai.actions.execute')

  const { data: conversationsPayload, loading, error, reload } = useAsyncData(async () => {
    if (!token || !hasAccess) return null
    return listAssistantConversations(token, organizationId ?? undefined)
  }, [token, organizationId, hasAccess])

  if (!hasAccess) {
    return <ErrorBanner message={t('aiAssistant.noPermission')} />
  }

  const conversations = conversationsPayload?.data ?? []

  const applyReply = (reply: AiAssistantReply, userText: string) => {
    setActiveConversationId(reply.conversation_id)
    setSuggestedActions(reply.suggested_actions ?? reply.message.metadata?.suggested_actions ?? [])
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
      applyReply(reply, message.trim())
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
      applyReply(reply, message.trim())
      setMessage('')
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('aiAssistant.sendFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  const selectConversation = async (conversation: AiConversationRecord) => {
    if (!token) return
    setActiveConversationId(conversation.id)
    setSuggestedActions([])
    setLoadingThread(true)
    setNotice('')
    try {
      const detail = await showConversation(token, conversation.id, organizationId ?? undefined)
      setThread(detail.messages)
      const lastAssistant = [...detail.messages].reverse().find((entry) => entry.role === 'assistant')
      setSuggestedActions(lastAssistant?.metadata?.suggested_actions ?? [])
    } catch (requestError) {
      setThread([])
      setNotice(translateApiError(requestError) || t('aiAssistant.loadThreadFailed'))
    } finally {
      setLoadingThread(false)
    }
  }

  const handleArchive = async () => {
    if (!token || !activeConversationId) return
    setSubmitting(true)
    setNotice('')
    try {
      await archiveConversation(token, activeConversationId, organizationId ?? undefined)
      setActiveConversationId(null)
      setThread([])
      setSuggestedActions([])
      setNotice(t('aiAssistant.archived'))
      await reload()
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('aiAssistant.archiveFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  const handleDelete = async () => {
    if (!token || !activeConversationId) return
    if (!window.confirm(t('aiAssistant.confirmDelete'))) return
    setSubmitting(true)
    setNotice('')
    try {
      await deleteConversation(token, activeConversationId, organizationId ?? undefined)
      setActiveConversationId(null)
      setThread([])
      setSuggestedActions([])
      setNotice(t('aiAssistant.deleted'))
      await reload()
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('aiAssistant.deleteFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  const runSuggestedAction = async (action: AiSuggestedAction) => {
    if (!token || !canExecuteActions) return
    const confirmed = !action.requires_confirmation || window.confirm(t('aiAssistant.confirmAction', { label: action.label }))
    if (!confirmed) return
    setSubmitting(true)
    setNotice('')
    try {
      const result = await executeAssistantAction(token, {
        action_type: action.type,
        confirmed: action.requires_confirmation === true,
      }, organizationId ?? undefined)
      setNotice(result.message || t('aiAssistant.actionExecuted'))
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('aiAssistant.actionFailed'))
    } finally {
      setSubmitting(false)
    }
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
              onClick={() => void selectConversation(conversation)}
            >
              <strong dir="auto">{conversation.title ?? t('aiAssistant.untitled', { domain: conversation.domain })}</strong>
              <span>{conversation.domain}</span>
            </button>
          ))}
        </div>
      )}
    </section>

    <section className="panel">
      <div className="panel-heading">
        <div><p className="eyebrow">{t('common.message')}</p><h2>{t('aiAssistant.chat')}</h2></div>
        {activeConversationId && (
          <div className="header-actions">
            <button type="button" className="link-button" disabled={submitting} onClick={() => void handleArchive()}>
              {t('aiAssistant.archive')}
            </button>
            <button type="button" className="link-button" disabled={submitting} onClick={() => void handleDelete()}>
              {t('aiAssistant.delete')}
            </button>
          </div>
        )}
      </div>
      <div className="conversation-thread">
        {loadingThread ? (
          <p className="loading">{t('aiAssistant.loadingThread')}</p>
        ) : thread.length === 0 ? (
          <p className="muted">{t('aiAssistant.emptyThread')}</p>
        ) : thread.map((entry) => (
          <article key={`${entry.id}-${entry.role}`} className={`chat-bubble chat-${entry.role}`}>
            <span className="chat-role">{entry.role === 'assistant' ? t('aiAssistant.assistant') : t('aiAssistant.you')}</span>
            <p dir="auto">{entry.content}</p>
          </article>
        ))}
      </div>
      {suggestedActions.length > 0 && (
        <div className="suggested-actions">
          <p className="eyebrow">{t('aiAssistant.suggestedActions')}</p>
          <div className="module-results">
            {suggestedActions.map((action) => (
              <button
                key={action.type}
                type="button"
                className="record-card"
                disabled={submitting || !canExecuteActions}
                onClick={() => void runSuggestedAction(action)}
              >
                <strong dir="auto">{action.label}</strong>
                {action.requires_confirmation && <span>{t('aiAssistant.requiresConfirmation')}</span>}
              </button>
            ))}
          </div>
          {!canExecuteActions && <p className="muted">{t('aiAssistant.actionsPermissionRequired')}</p>}
        </div>
      )}
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
          disabled={submitting || !message.trim() || loadingThread}
          onClick={() => void (activeConversationId ? continueConversation() : startConversation())}
        >
          {submitting ? t('aiAssistant.sending') : t('aiAssistant.send')}
        </button>
      </div>
    </section>
  </>
}
