import { request, requestWithRetry } from './client'
import type { AiProviderInfo, AiQuotaSummary, AiRequestRecord, PaginatedResponse } from './types'

export const getAiProvider = (token: string, organizationId?: number) =>
  request<AiProviderInfo>('/ai/provider', {}, token, organizationId)

export const getAiUsage = (token: string, organizationId?: number) =>
  request<AiQuotaSummary>('/ai/usage', {}, token, organizationId)

export const listAiRequests = (
  token: string,
  organizationId?: number,
  query: Record<string, string | number> = {},
) => {
  const params = new URLSearchParams()
  Object.entries(query).forEach(([key, value]) => params.set(key, String(value)))
  const suffix = params.toString() ? `?${params.toString()}` : ''
  return request<PaginatedResponse<AiRequestRecord>>(`/ai/requests${suffix}`, {}, token, organizationId)
}

export const getAiRequest = (token: string, id: number, organizationId?: number) =>
  request<AiRequestRecord>(`/ai/requests/${id}`, {}, token, organizationId)

export const createAiRequest = (
  token: string,
  payload: { request_type: string; input: Record<string, unknown> },
  organizationId?: number,
) =>
  requestWithRetry(() =>
    request<AiRequestRecord>('/ai/requests', {
      method: 'POST',
      body: JSON.stringify(payload),
    }, token, organizationId),
  )

export async function pollAiRequest(
  token: string,
  id: number,
  organizationId?: number,
  attempts = 20,
  delayMs = 750,
): Promise<AiRequestRecord> {
  for (let attempt = 0; attempt < attempts; attempt += 1) {
    const record = await getAiRequest(token, id, organizationId)
    if (['completed', 'failed', 'cancelled'].includes(record.status)) {
      return record
    }
    await new Promise((resolve) => setTimeout(resolve, delayMs))
  }

  return getAiRequest(token, id, organizationId)
}

export const cancelAiRequest = (token: string, id: number, organizationId?: number) =>
  request<AiRequestRecord>(`/ai/requests/${id}/cancel`, { method: 'POST' }, token, organizationId)
