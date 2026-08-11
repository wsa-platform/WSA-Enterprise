import { request } from './client'
import type { OrganizationProfile } from './types'

export const getOrganizations = (token: string) =>
  request<import('./types').Organization[]>('/platform/organizations', {}, token)

export const getOrganization = (token: string, organizationId?: number) =>
  request<OrganizationProfile>('/organization', {}, token, organizationId)

export const updateOrganization = (
  token: string,
  payload: { name?: string; slug?: string },
  organizationId?: number,
) =>
  request<OrganizationProfile>('/organization', {
    method: 'PATCH',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const getOrganizationSettings = (token: string, organizationId?: number) =>
  request<Record<string, unknown>>('/organization/settings', {}, token, organizationId)

export const updateOrganizationSettings = (
  token: string,
  settings: Record<string, unknown>,
  organizationId?: number,
) =>
  request<Record<string, unknown>>('/organization/settings', {
    method: 'PUT',
    body: JSON.stringify({ settings }),
  }, token, organizationId)
