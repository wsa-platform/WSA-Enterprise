import { apiUrl, ApiError, buildHeaders, request } from './client'
import type { PaginatedResponse } from './types'

export type AiSuggestedAction = {
  type: string
  label: string
  requires_confirmation?: boolean
  permission?: string
}

export type AiConversationRecord = {
  id: number
  organization_id: number
  user_id: number
  domain: string
  title: string | null
  context: Record<string, unknown> | null
  archived_at?: string | null
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
    suggested_actions?: AiSuggestedAction[]
  } | null
  created_at: string
}

export type AiAssistantReply = {
  conversation_id: number
  message: AiConversationMessage
  confidence: number | null
  requires_more_information: boolean
  suggested_actions?: AiSuggestedAction[]
}

export type AiConversationDetail = AiConversationRecord & {
  messages: AiConversationMessage[]
}

export type AiVisionUpload = {
  id: number
  storage_path: string
  mime_type: string
  size_bytes: number
}

export type AiActionResult = {
  action: string
  status: string
  message: string
  payload?: Record<string, unknown>
}

export const listAssistantConversations = (token: string, organizationId?: number, page = 1) =>
  request<PaginatedResponse<AiConversationRecord>>(`/ai/assistant/conversations?page=${page}`, {}, token, organizationId)

export const showConversation = (token: string, conversationId: number, organizationId?: number) =>
  request<AiConversationDetail>(`/ai/assistant/conversations/${conversationId}`, {}, token, organizationId)

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

export const archiveConversation = (token: string, conversationId: number, organizationId?: number) =>
  request<AiConversationRecord>(`/ai/assistant/conversations/${conversationId}/archive`, {
    method: 'POST',
  }, token, organizationId)

export const deleteConversation = (token: string, conversationId: number, organizationId?: number) =>
  request<void>(`/ai/assistant/conversations/${conversationId}`, {
    method: 'DELETE',
  }, token, organizationId)

export const executeAssistantAction = (
  token: string,
  payload: { action_type: string; payload?: Record<string, unknown>; confirmed?: boolean },
  organizationId?: number,
) =>
  request<AiActionResult>('/ai/assistant/actions/execute', {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export async function uploadVisionImage(token: string, file: File, organizationId?: number) {
  const form = new FormData()
  form.append('file', file)
  const response = await fetch(`${apiUrl}/ai/vision/uploads`, {
    method: 'POST',
    headers: buildHeaders(token, organizationId),
    body: form,
  })

  if (!response.ok) {
    const payload = await response.json().catch(() => null) as { message?: string } | null
    throw new ApiError(payload?.message ?? 'Unable to upload image.', response.status)
  }

  return response.json() as Promise<AiVisionUpload>
}
