import { request } from './client'
import type { PaginatedResponse } from './types'

export type CommunicationChannel = 'email' | 'sms' | 'whatsapp'

export type InboxItem = {
  id: number
  source: string
  type: string
  title: string
  body?: string | null
  created_at?: string
}

export type CommunicationMessage = {
  id: number
  subject: string
  body: string
  channel: CommunicationChannel
  status: string
  recipient_mode?: string
}

export type CommunicationContact = {
  id: number
  name?: string | null
  email?: string | null
  phone?: string | null
}

export async function fetchCommunicationsInbox(token: string, organizationId?: number, page = 1) {
  return request<{ data: InboxItem[]; meta?: { current_page: number; total: number; providers?: Record<string, boolean> } }>(
    `/communications/inbox?page=${page}`,
    {},
    token,
    organizationId,
  )
}

export async function fetchCommunicationMessages(token: string, organizationId?: number, page = 1) {
  return request<PaginatedResponse<CommunicationMessage> | CommunicationMessage[]>(
    `/communications/messages?page=${page}&per_page=15`,
    {},
    token,
    organizationId,
  )
}

export async function composeCommunication(
  token: string,
  payload: {
    subject: string
    body: string
    channel: CommunicationChannel
    recipient_mode?: string
    recipients?: Array<{ email?: string; phone?: string; name?: string }>
  },
  organizationId?: number,
) {
  return request<CommunicationMessage>(
    '/communications/messages',
    { method: 'POST', body: JSON.stringify(payload) },
    token,
    organizationId,
  )
}

export async function sendCommunication(token: string, messageId: number, organizationId?: number) {
  return request<CommunicationMessage & { delivery_stats?: { sent: number; failed: number; total: number } }>(
    `/communications/messages/${messageId}/send`,
    { method: 'POST' },
    token,
    organizationId,
  )
}

export async function fetchContacts(token: string, organizationId?: number, search?: string) {
  const query = new URLSearchParams({ per_page: '25' })
  if (search) query.set('search', search)
  return request<PaginatedResponse<CommunicationContact>>(
    `/communications/contacts?${query}`,
    {},
    token,
    organizationId,
  )
}

export async function createContact(
  token: string,
  payload: { name?: string; email?: string; phone?: string },
  organizationId?: number,
) {
  return request<CommunicationContact>(
    '/communications/contacts',
    { method: 'POST', body: JSON.stringify(payload) },
    token,
    organizationId,
  )
}
