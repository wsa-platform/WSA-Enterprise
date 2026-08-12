import { request } from './client'
import type { PaginatedResponse } from './types'

export type AiConversationRecord = {
  id: number
  organization_id: number
  user_id: number
  domain: string
  title: string | null
  context: Record<string, unknown> | null
  created_at: string
  updated_at: string
}

export type AiConversationMessage = {
  id: number
  conversation_id: number
  role: 'user' | 'assistant'
  content: string
  metadata?: {
    confidence?: number | null
    requires_more_information?: boolean
    ai_request_id?: number
  } | null
  created_at: string
}

export type AiAssistantReply = {
  conversation_id: number
  message: AiConversationMessage
  confidence: number | null
  requires_more_information: boolean
}

export const listAssistantConversations = (token: string, organizationId?: number, page = 1) =>
  request<PaginatedResponse<AiConversationRecord>>(`/ai/assistant/conversations?page=${page}`, {}, token, organizationId)

export const startAssistantConversation = (
  token: string,
  payload: { domain: string; title?: string; message: string },
  organizationId?: number,
) =>
  request<AiAssistantReply>('/ai/assistant/conversations', {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const sendAssistantMessage = (
  token: string,
  conversationId: number,
  message: string,
  organizationId?: number,
) =>
  request<AiAssistantReply>(`/ai/assistant/conversations/${conversationId}/messages`, {
    method: 'POST',
    body: JSON.stringify({ message }),
  }, token, organizationId)
