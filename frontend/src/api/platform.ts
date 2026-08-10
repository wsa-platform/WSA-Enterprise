import { request } from './client'
import type { AccessSummary, MeContext } from './types'

export const getMeContext = (token: string, organizationId?: number) =>
  request<MeContext>('/platform/me', {}, token, organizationId)

export const getAccessSummary = (token: string, organizationId?: number) =>
  request<AccessSummary>('/platform/access-summary', {}, token, organizationId)
