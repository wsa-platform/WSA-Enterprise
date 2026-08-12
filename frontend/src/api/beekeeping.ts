import { request } from './client'
import type { PaginatedResponse } from './types'

export type BeekeeperProfile = {
  id: number
  organization_id: number
  user_id: number
  display_name: string
  country: string | null
  region: string | null
  location: string | null
  hive_count: number | null
  colony_count: number | null
  experience_years: number | null
  production_types: string[] | null
  goals: string[] | null
  seasonal_activity: unknown | null
  notes: string | null
}

export type Apiary = {
  id: number
  organization_id: number
  beekeeper_profile_id: number
  name: string
  country: string | null
  region: string | null
  location: string | null
  latitude: number | null
  longitude: number | null
  notes: string | null
  hives_count?: number
}

export type Hive = {
  id: number
  organization_id: number
  apiary_id: number
  code: string
  colony_status: string | null
  queen_info: unknown | null
  frame_count: number | null
  notes: string | null
}

export type BeeCalendarTask = {
  id: number
  organization_id: number
  apiary_id: number | null
  hive_id: number | null
  task_type: string
  severity: string | null
  title: string
  description: string | null
  scheduled_for: string | null
  due_at: string | null
}

export type PollinationPlant = {
  id: number
  organization_id: number
  species_name: string
  common_name: string | null
  flowering_start: string | null
  flowering_end: string | null
  location: string | null
  country: string | null
  region: string | null
  pollination_relevance: number | null
  notes: string | null
}

export const getBeekeeperProfile = (token: string, organizationId?: number) =>
  request<BeekeeperProfile | null>('/beekeeping/profile', {}, token, organizationId)

export const upsertBeekeeperProfile = (
  token: string,
  payload: Partial<Omit<BeekeeperProfile, 'id' | 'organization_id' | 'user_id'>> & { display_name: string },
  organizationId?: number,
) =>
  request<BeekeeperProfile>('/beekeeping/profile', {
    method: 'PUT',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const listApiaries = (token: string, organizationId?: number, page = 1) =>
  request<PaginatedResponse<Apiary>>(`/beekeeping/apiaries?page=${page}`, {}, token, organizationId)

export const createApiary = (
  token: string,
  payload: {
    beekeeper_profile_id: number
    name: string
    country?: string
    region?: string
    location?: string
    notes?: string
  },
  organizationId?: number,
) =>
  request<Apiary>('/beekeeping/apiaries', {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const listHives = (token: string, apiaryId: number, organizationId?: number) =>
  request<PaginatedResponse<Hive>>(`/beekeeping/apiaries/${apiaryId}/hives`, {}, token, organizationId)

export const createHive = (
  token: string,
  apiaryId: number,
  payload: { code: string; colony_status?: string; frame_count?: number; notes?: string },
  organizationId?: number,
) =>
  request<Hive>(`/beekeeping/apiaries/${apiaryId}/hives`, {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const listCalendarTasks = (token: string, organizationId?: number, page = 1) =>
  request<PaginatedResponse<BeeCalendarTask>>(`/beekeeping/calendar/tasks?page=${page}`, {}, token, organizationId)

export const createCalendarTask = (
  token: string,
  payload: {
    task_type: string
    title: string
    description?: string
    scheduled_for?: string
    apiary_id?: number
    hive_id?: number
  },
  organizationId?: number,
) =>
  request<BeeCalendarTask>('/beekeeping/calendar/tasks', {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const listPollinationPlants = (token: string, organizationId?: number, page = 1) =>
  request<PaginatedResponse<PollinationPlant>>(`/beekeeping/pollination/plants?page=${page}`, {}, token, organizationId)

export const createPollinationPlant = (
  token: string,
  payload: {
    species_name: string
    common_name?: string
    flowering_start?: string
    flowering_end?: string
    location?: string
    pollination_relevance?: number
    notes?: string
  },
  organizationId?: number,
) =>
  request<PollinationPlant>('/beekeeping/pollination/plants', {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)
