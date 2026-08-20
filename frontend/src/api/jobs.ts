import { apiUrl, ApiError, buildHeaders, request } from './client'
import type { PaginatedResponse } from './types'

export type JobTalentProfile = {
  id: number
  professional_name: string
  specialization: string | null
  biography: string | null
  country: string | null
  region: string | null
  city: string | null
  skills: string[] | null
  experience: unknown[] | null
  education: unknown[] | null
  certificates: unknown[] | null
  languages: string[] | null
  disciplines: string[] | null
  work_preferences: unknown | null
  availability: unknown | null
  employment_status: string | null
  cv_path: string | null
  cv_parse_status: string | null
  is_public: boolean
  contact?: {
    email?: string | null
    phone?: string | null
    whatsapp?: string | null
  } | null
  created_at?: string
  updated_at?: string
}

export type JobContactRequest = {
  id: number
  organization_id: number
  talent_profile_id: number
  status: string
  job_reference: string | null
  notes: string | null
  employer_contact_name?: string
  employer_contact_email?: string
}

export type JobCandidateSearchFilters = {
  country?: string
  region?: string
  city?: string
  specialization?: string
  discipline?: string
  skill?: string
  employment_status?: string
  page?: number
  per_page?: number
}

export type JobTalentUpsertPayload = {
  professional_name: string
  specialization?: string
  biography?: string
  country?: string
  region?: string
  city?: string
  skills?: string[]
  employment_status?: string
  is_public?: boolean
  contact?: {
    email?: string
    phone?: string
    whatsapp?: string
  }
}

export const getMyTalentProfile = (token: string, organizationId?: number) =>
  request<JobTalentProfile | null>('/jobs/talent/me', {}, token, organizationId)

export const upsertMyTalentProfile = (
  token: string,
  payload: JobTalentUpsertPayload,
  organizationId?: number,
) =>
  request<JobTalentProfile>('/jobs/talent/me', {
    method: 'PUT',
    body: JSON.stringify(payload),
  }, token, organizationId)

export async function uploadTalentCv(token: string, file: File, organizationId?: number) {
  const form = new FormData()
  form.append('cv', file)
  const response = await fetch(`${apiUrl}/jobs/talent/me/cv`, {
    method: 'POST',
    headers: buildHeaders(token, organizationId),
    body: form,
  })

  if (!response.ok) {
    const payload = await response.json().catch(() => null) as { message?: string } | null
    throw new ApiError(payload?.message ?? 'Unable to upload CV.', response.status)
  }

  return response.json() as Promise<JobTalentProfile>
}

export const parseTalentCv = (token: string, organizationId?: number) =>
  request<Record<string, unknown>>('/jobs/talent/me/cv/parse', { method: 'POST' }, token, organizationId)

export const searchJobCandidates = (
  token: string,
  filters: JobCandidateSearchFilters = {},
  organizationId?: number,
) => {
  const params = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== '') params.set(key, String(value))
  })
  const suffix = params.toString() ? `?${params.toString()}` : ''
  return request<PaginatedResponse<JobTalentProfile>>(`/jobs/candidates${suffix}`, {}, token, organizationId)
}

export const getJobCandidate = (token: string, talentProfileId: number, organizationId?: number) =>
  request<JobTalentProfile>(`/jobs/candidates/${talentProfileId}`, {}, token, organizationId)

export const requestCandidateContact = (
  token: string,
  talentProfileId: number,
  payload: {
    employer_contact: { name: string; email: string; phone?: string; whatsapp?: string }
    job_reference?: string
    notes?: string
    idempotency_key?: string
  },
  organizationId?: number,
) =>
  request<JobContactRequest>(`/jobs/candidates/${talentProfileId}/contact-requests`, {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const payContactRequest = (
  token: string,
  contactRequestId: number,
  idempotencyKey: string,
  organizationId?: number,
) =>
  request<{ transaction: unknown; exchange: unknown }>(`/jobs/contact-requests/${contactRequestId}/pay`, {
    method: 'POST',
    body: JSON.stringify({ idempotency_key: idempotencyKey }),
  }, token, organizationId)

export type JobSeekerProfile = {
  id: number
  full_name: string
  email: string | null
  phone: string | null
  country: string | null
  region: string | null
  city: string | null
  specialization: string | null
  biography: string | null
  skills: string[] | null
  experience: unknown[] | null
  education: unknown[] | null
  certifications: unknown[] | null
  languages: string[] | null
  availability_date: string | null
  recruitment_status: string
  is_active: boolean
  cv_path: string | null
  desired_salary: string | null
  salary_currency: string | null
  completeness_percent: number | null
  created_at?: string
  updated_at?: string
}

export const getMyJobSeekerProfile = (token: string, organizationId?: number) =>
  request<JobSeekerProfile>('/job-seekers/me', {}, token, organizationId)

export const upsertMyJobSeekerProfile = (
  token: string,
  payload: Record<string, unknown>,
  organizationId?: number,
) =>
  request<JobSeekerProfile>('/job-seekers/me', {
    method: 'PUT',
    body: JSON.stringify(payload),
  }, token, organizationId)
