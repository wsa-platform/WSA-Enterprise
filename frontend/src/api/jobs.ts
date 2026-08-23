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
  has_cv?: boolean
  cv_filename?: string | null
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
  request<{
    transaction: { id: number; payment_status: string; contact_exchange_status: string }
    exchange: {
      candidate_contact?: { email?: string | null; phone?: string | null }
      employer_contact?: { email?: string | null; name?: string | null }
      hiring_record_id?: number | null
    }
    hiring_record: { id: number; employment_status: string } | null
  }>(`/jobs/contact-requests/${contactRequestId}/pay`, {
    method: 'POST',
    body: JSON.stringify({ idempotency_key: idempotencyKey }),
  }, token, organizationId)

export const getUnlockedContact = (token: string, contactRequestId: number, organizationId?: number) =>
  request<{
    candidate_contact?: { email?: string | null; phone?: string | null }
    employer_contact?: { email?: string | null; name?: string | null }
  }>(`/jobs/contact-requests/${contactRequestId}/contact`, {}, token, organizationId)

export const markCandidateHired = (token: string, contactRequestId: number, organizationId?: number) =>
  request<{ id: number; employment_status: string }>(`/jobs/contact-requests/${contactRequestId}/hire`, {
    method: 'POST',
  }, token, organizationId)

export async function downloadUnlockedCv(token: string, contactRequestId: number, organizationId?: number) {
  const response = await fetch(`${apiUrl}/jobs/contact-requests/${contactRequestId}/cv`, {
    headers: buildHeaders(token, organizationId),
  })

  if (!response.ok) {
    const payload = await response.json().catch(() => null) as { message?: string } | null
    throw new ApiError(payload?.message ?? 'Unable to download CV.', response.status)
  }

  return response.blob()
}

export async function downloadMyTalentCv(token: string, organizationId?: number) {
  const response = await fetch(`${apiUrl}/jobs/talent/me/cv`, {
    headers: buildHeaders(token, organizationId),
  })

  if (!response.ok) {
    const payload = await response.json().catch(() => null) as { message?: string } | null
    throw new ApiError(payload?.message ?? 'Unable to download CV.', response.status)
  }

  return response.blob()
}

export const listMyContactRequests = (token: string, organizationId?: number) =>
  request<{ data: Array<{ id: number; status: string; job_reference: string | null; created_at: string }> }>(
    '/jobs/talent/me/contact-requests',
    {},
    token,
    organizationId,
  )

export type JobSeekerProfile = {
  id: number
  full_name: string
  email: string | null
  phone: string | null
  country: string | null
  region: string | null
  city: string | null
  specialization: string | null
  target_job_title: string | null
  biography: string | null
  skills: string[] | null
  experience: unknown[] | null
  education: unknown[] | null
  certifications: unknown[] | null
  languages: string[] | null
  availability_date: string | null
  date_of_birth: string | null
  nationality: string | null
  address: string | null
  recruitment_status: string
  is_active: boolean
  has_cv?: boolean
  cv_filename?: string | null
  has_photo?: boolean
  has_primary_qualification_document?: boolean
  primary_qualification_filename?: string | null
  cv_path?: string | null
  desired_salary: string | null
  salary_currency: string | null
  completeness_percent: number | null
  years_of_experience: number | null
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

export const deleteMyJobSeekerApplication = (token: string, organizationId?: number) =>
  request<{ message?: string }>('/job-seekers/me', { method: 'DELETE' }, token, organizationId)

export async function uploadMyJobSeekerCv(token: string, file: File, organizationId?: number) {
  const form = new FormData()
  form.append('cv', file)
  const response = await fetch(`${apiUrl}/job-seekers/me/cv`, {
    method: 'POST',
    headers: buildHeaders(token, organizationId),
    body: form,
  })

  if (!response.ok) {
    const payload = await response.json().catch(() => null) as { message?: string } | null
    throw new ApiError(payload?.message ?? 'Unable to upload CV.', response.status)
  }

  return response.json() as Promise<JobSeekerProfile>
}

export async function downloadMyJobSeekerCv(token: string, organizationId?: number) {
  const response = await fetch(`${apiUrl}/job-seekers/me/cv`, {
    headers: buildHeaders(token, organizationId),
  })

  if (!response.ok) {
    const payload = await response.json().catch(() => null) as { message?: string } | null
    throw new ApiError(payload?.message ?? 'Unable to download CV.', response.status)
  }

  return response.blob()
}

export async function uploadMyJobSeekerPrimaryQualification(token: string, file: File, organizationId?: number) {
  const form = new FormData()
  form.append('document', file)
  const response = await fetch(`${apiUrl}/job-seekers/me/primary-qualification`, {
    method: 'POST',
    headers: buildHeaders(token, organizationId),
    body: form,
  })

  if (!response.ok) {
    const payload = await response.json().catch(() => null) as { message?: string } | null
    throw new ApiError(payload?.message ?? 'Unable to upload qualification document.', response.status)
  }

  return response.json() as Promise<JobSeekerProfile>
}

export async function downloadMyJobSeekerPrimaryQualification(token: string, organizationId?: number) {
  const response = await fetch(`${apiUrl}/job-seekers/me/primary-qualification`, {
    headers: buildHeaders(token, organizationId),
  })

  if (!response.ok) {
    const payload = await response.json().catch(() => null) as { message?: string } | null
    throw new ApiError(payload?.message ?? 'Unable to download qualification document.', response.status)
  }

  return response.blob()
}

export type EmployerSeeker = {
  id: number
  full_name: string
  has_photo: boolean
  target_job_title: string | null
  country: string | null
  city: string | null
  region: string | null
  specialization: string | null
  years_of_experience: number | null
  skills: string[] | null
  languages: string[] | null
  education: Array<{ degree?: string; institution?: string; year?: string | number }> | null
  experience: Array<{ title?: string; company?: string; description?: string }> | null
  biography: string | null
  nationality: string | null
  employment_status: 'job_seeker' | 'hired'
  employment_label: string
  has_cv: boolean
}

export type EmployerSeekerFilters = {
  job_title?: string
  country?: string
  city?: string
  qualification?: string
  years_of_experience?: string
  skills?: string
  languages?: string
  work_type?: string
  specialization?: string
  page?: number
  per_page?: number
}

export const searchEmployerSeekers = (
  token: string,
  filters: EmployerSeekerFilters = {},
  organizationId?: number,
) => {
  const params = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (key === 'desired_salary') return
    if (value === undefined || value === null) return
    const serialized = String(value).trim()
    if (serialized === '') return
    params.set(key, serialized)
  })
  const suffix = params.toString() ? `?${params.toString()}` : ''
  return request<PaginatedResponse<EmployerSeeker>>(`/jobs/seekers${suffix}`, {}, token, organizationId)
}

export const getEmployerSeeker = (token: string, seekerId: number, organizationId?: number) =>
  request<EmployerSeeker>(`/jobs/seekers/${seekerId}`, {}, token, organizationId)

export const requestSeekerContact = (
  token: string,
  seekerId: number,
  payload: {
    employer_contact: { name: string; email: string; phone?: string; whatsapp?: string }
    job_reference?: string
    notes?: string
    idempotency_key?: string
  },
  organizationId?: number,
) =>
  request<{ id: number; status: string; job_reference: string | null; message?: string }>(
    `/jobs/seekers/${seekerId}/contact-requests`,
    { method: 'POST', body: JSON.stringify(payload) },
    token,
    organizationId,
  )

export async function fetchEmployerSeekerPhoto(
  token: string,
  seekerId: number,
  organizationId?: number,
): Promise<string | null> {
  const response = await fetch(`${apiUrl}/jobs/seekers/${seekerId}/photo`, {
    headers: buildHeaders(token, organizationId),
  })
  if (!response.ok) return null
  const blob = await response.blob()
  if (!blob.type.startsWith('image/')) return null
  return URL.createObjectURL(blob)
}
