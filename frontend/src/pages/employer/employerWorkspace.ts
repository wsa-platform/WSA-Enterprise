import { authorizedUnlockFromPay } from '../jobs/candidateProfile'
import { EMPLOYER_ENTER } from '../../navigation/roleDestinations'
import type { EmployerSeeker, EmployerSeekerFilters } from '../../api/jobs'
import { payContactRequest } from '../../api/jobs'

export const EMPTY_EMPLOYER_FILTERS: EmployerSeekerFilters = {
  job_title: '',
  country: '',
  city: '',
  qualification: '',
  years_of_experience: '',
  skills: '',
  languages: '',
  work_type: '',
  desired_salary: '',
  specialization: '',
}

export const EMPLOYER_PROTECTED_FIELDS = [
  'email',
  'phone',
  'address',
  'user_id',
  'organization_id',
  'internal_notes',
  'internal_rating',
] as const

export function employerGuestEntryPath() {
  return EMPLOYER_ENTER
}

export function sanitizeEmployerSeeker(candidate: EmployerSeeker): EmployerSeeker {
  const copy = { ...candidate } as EmployerSeeker & Record<string, unknown>
  for (const field of EMPLOYER_PROTECTED_FIELDS) {
    delete copy[field]
  }
  return copy
}

export function employerSeekerLeaksProtectedData(candidate: object) {
  return EMPLOYER_PROTECTED_FIELDS.some((field) => Object.prototype.hasOwnProperty.call(candidate, field))
}

export type EmployerSearchView = 'loading' | 'error' | 'empty' | 'results'

export function employerSearchView(input: { loading: boolean; error: string; count: number }): EmployerSearchView {
  if (input.loading) return 'loading'
  if (input.error) return 'error'
  if (input.count === 0) return 'empty'
  return 'results'
}

export type EmployerRoleGate = 'guest' | 'job_seeker' | 'employer' | 'unknown'

export function employerRoleGate(input: {
  authenticated: boolean
  isJobSeeker: boolean
  isEmployer: boolean
}): EmployerRoleGate {
  if (!input.authenticated) return 'guest'
  if (input.isJobSeeker) return 'job_seeker'
  if (input.isEmployer) return 'employer'
  return 'unknown'
}

export function unlockFromVerifiedPayment(
  requestId: number,
  pay: Awaited<ReturnType<typeof payContactRequest>>,
) {
  const unlocked = authorizedUnlockFromPay(requestId, {
    transaction: { payment_status: pay.transaction.payment_status },
    exchange: { candidate_contact: pay.exchange.candidate_contact },
    hiring_record: pay.hiring_record,
  })
  if (!unlocked?.candidateEmail) return null

  return {
    candidateEmail: unlocked.candidateEmail,
    candidatePhone: unlocked.candidatePhone ?? null,
    hired: Boolean(unlocked.hired),
  }
}
