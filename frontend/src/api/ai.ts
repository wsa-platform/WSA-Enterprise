import { request, requestWithRetry } from './client'
import type { AiProviderInfo, AiRequestRecord, PaginatedResponse } from './types'

export const getAiProvider = (token: string, organizationId?: number) =>
  request<AiProviderInfo>('/ai/provider', {}, token, organizationId)

export const listAiRequests = (token: string, organizationId?: number) =>
  request<PaginatedResponse<AiRequestRecord>>('/ai/requests', {}, token, organizationId)

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
  attempts = 10,
  delayMs = 500,
): Promise<AiRequestRecord> {
  for (let attempt = 0; attempt < attempts; attempt += 1) {
    const record = await getAiRequest(token, id, organizationId)
    if (record.status === 'completed' || record.status === 'failed') {
      return record
    }
    await new Promise((resolve) => setTimeout(resolve, delayMs))
  }

  return getAiRequest(token, id, organizationId)
}
