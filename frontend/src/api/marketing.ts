import { request } from './client'
import type { PaginatedResponse } from './types'

export type MarketingChannel = 'sms' | 'email' | 'whatsapp'

export type MarketingDashboardStats = {
  campaigns: number
  scheduled: number
  deliveries: number
  failed_deliveries: number
  segments: number
  templates: number
  suppressions: number
  channel_stats: Record<string, number>
}

export type MarketingAudienceSegment = {
  id: number
  organization_id: number
  name: string
  description: string | null
  criteria: Record<string, unknown> | null
  created_by_user_id: number
  created_at?: string
  updated_at?: string
}

export type MarketingTemplate = {
  id: number
  organization_id: number
  slug: string
  name: string
  channel: MarketingChannel
  translations: Record<string, { subject?: string; body?: string }>
  created_by_user_id: number
  created_at?: string
  updated_at?: string
}

export type MarketingCampaign = {
  id: number
  organization_id: number
  name: string
  description: string | null
  channel: MarketingChannel
  audience_segment_id: number | null
  template_id: number | null
  content: Record<string, unknown> | null
  status: string
  scheduled_at: string | null
  started_at: string | null
  completed_at: string | null
  created_by_user_id: number
  segment?: MarketingAudienceSegment | null
  template?: MarketingTemplate | null
  deliveries?: MarketingDelivery[]
  created_at?: string
  updated_at?: string
}

export type MarketingDelivery = {
  id: number
  organization_id: number
  campaign_id: number
  recipient_type: string | null
  recipient_id: number | null
  channel: MarketingChannel
  status: string
  provider: string | null
  provider_message_id: string | null
  error_code: string | null
  error_message: string | null
  queued_at: string | null
  sent_at: string | null
  delivered_at: string | null
  failed_at: string | null
  created_at?: string
}

export type MarketingConsent = {
  id: number
  organization_id: number
  user_id: number | null
  email: string | null
  phone: string | null
  channel: MarketingChannel
  opted_in: boolean
  opted_out_at: string | null
  source: string | null
  created_at?: string
}

export type MarketingSuppression = {
  id: number
  organization_id: number
  channel: MarketingChannel
  identifier: string
  reason: string | null
  created_by_user_id: number
  created_at?: string
}

export type MarketingCampaignPreview = {
  channel: MarketingChannel
  locale: string
  subject: string
  body: string
}

export type MarketingCampaignPayload = {
  name: string
  description?: string
  channel: MarketingChannel
  audience_segment_id?: number | null
  template_id?: number | null
  content?: Record<string, unknown>
  scheduled_at?: string | null
}

export const getMarketingDashboard = (token: string, organizationId?: number) =>
  request<MarketingDashboardStats>('/marketing/dashboard', {}, token, organizationId)

export const listMarketingCampaigns = (token: string, organizationId?: number, page = 1) =>
  request<PaginatedResponse<MarketingCampaign>>(`/marketing/campaigns?page=${page}`, {}, token, organizationId)

export const getMarketingCampaign = (token: string, campaignId: number, organizationId?: number) =>
  request<MarketingCampaign>(`/marketing/campaigns/${campaignId}`, {}, token, organizationId)

export const createMarketingCampaign = (
  token: string,
  payload: MarketingCampaignPayload,
  organizationId?: number,
) =>
  request<MarketingCampaign>('/marketing/campaigns', {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const updateMarketingCampaign = (
  token: string,
  campaignId: number,
  payload: Partial<MarketingCampaignPayload>,
  organizationId?: number,
) =>
  request<MarketingCampaign>(`/marketing/campaigns/${campaignId}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const deleteMarketingCampaign = (token: string, campaignId: number, organizationId?: number) =>
  request<void>(`/marketing/campaigns/${campaignId}`, { method: 'DELETE' }, token, organizationId)

export const scheduleMarketingCampaign = (token: string, campaignId: number, organizationId?: number) =>
  request<MarketingCampaign>(`/marketing/campaigns/${campaignId}/schedule`, { method: 'POST' }, token, organizationId)

export const cancelMarketingCampaign = (token: string, campaignId: number, organizationId?: number) =>
  request<MarketingCampaign>(`/marketing/campaigns/${campaignId}/cancel`, { method: 'POST' }, token, organizationId)

export const previewMarketingCampaign = (
  token: string,
  campaignId: number,
  locale = 'en',
  organizationId?: number,
) =>
  request<MarketingCampaignPreview>(`/marketing/campaigns/${campaignId}/preview?locale=${encodeURIComponent(locale)}`, {}, token, organizationId)

export const testSendMarketingCampaign = (
  token: string,
  campaignId: number,
  locale = 'en',
  organizationId?: number,
) =>
  request<MarketingDelivery>(`/marketing/campaigns/${campaignId}/test-send`, {
    method: 'POST',
    body: JSON.stringify({ locale }),
  }, token, organizationId)

export const processMarketingCampaign = (token: string, campaignId: number, organizationId?: number) =>
  request<MarketingCampaign>(`/marketing/campaigns/${campaignId}/process`, { method: 'POST' }, token, organizationId)

export const listMarketingSegments = (token: string, organizationId?: number, page = 1) =>
  request<PaginatedResponse<MarketingAudienceSegment>>(`/marketing/segments?page=${page}`, {}, token, organizationId)

export const createMarketingSegment = (
  token: string,
  payload: { name: string; description?: string; criteria?: Record<string, unknown> },
  organizationId?: number,
) =>
  request<MarketingAudienceSegment>('/marketing/segments', {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const listMarketingTemplates = (token: string, organizationId?: number, page = 1) =>
  request<PaginatedResponse<MarketingTemplate>>(`/marketing/templates?page=${page}`, {}, token, organizationId)

export const createMarketingTemplate = (
  token: string,
  payload: {
    slug: string
    name: string
    channel: MarketingChannel
    translations: Record<string, { subject?: string; body?: string }>
  },
  organizationId?: number,
) =>
  request<MarketingTemplate>('/marketing/templates', {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const listMarketingConsents = (token: string, organizationId?: number, page = 1) =>
  request<PaginatedResponse<MarketingConsent>>(`/marketing/consents?page=${page}`, {}, token, organizationId)

export const createMarketingConsent = (
  token: string,
  payload: {
    channel: MarketingChannel
    user_id?: number
    email?: string
    phone?: string
    opted_in: boolean
    source?: string
  },
  organizationId?: number,
) =>
  request<MarketingConsent>('/marketing/consents', {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const listMarketingSuppressions = (token: string, organizationId?: number, page = 1) =>
  request<PaginatedResponse<MarketingSuppression>>(`/marketing/suppressions?page=${page}`, {}, token, organizationId)

export const createMarketingSuppression = (
  token: string,
  payload: { channel: MarketingChannel; identifier: string; reason?: string },
  organizationId?: number,
) =>
  request<MarketingSuppression>('/marketing/suppressions', {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const listMarketingDeliveries = (
  token: string,
  organizationId?: number,
  page = 1,
  campaignId?: number,
) => {
  const params = new URLSearchParams({ page: String(page) })
  if (campaignId) params.set('campaign_id', String(campaignId))
  return request<PaginatedResponse<MarketingDelivery>>(`/marketing/deliveries?${params.toString()}`, {}, token, organizationId)
}
