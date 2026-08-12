import { request } from './client'
import type { AnalyticsOverview } from './types'

export const getAnalyticsOverview = (token: string, organizationId?: number) =>
  request<AnalyticsOverview>('/analytics/overview', {}, token, organizationId)
