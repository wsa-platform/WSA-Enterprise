export type ExperienceItem = {
  title?: string
  company?: string
  start_date?: string
  end_date?: string
  current?: boolean
  description?: string
}

export type EducationItem = {
  degree?: string
  institution?: string
  country?: string
  year?: number | string
}

export type CandidateProfileForm = {
  fullName: string
  email: string
  phone: string
  country: string
  city: string
  dateOfBirth: string
  nationality: string
  address: string
  targetJobTitle: string
  biography: string
  yearsOfExperience: string
  specialization: string
  skills: string
  languages: string
  experienceItems: ExperienceItem[]
  educationItems: EducationItem[]
}

export const CANDIDATE_STATUS_TIMELINE = [
  { key: 'new', labelKey: 'jobs.statusTimeline.created', statuses: ['new'] },
  { key: 'applied', labelKey: 'jobs.statusTimeline.submitted', statuses: ['under_review'] },
  { key: 'review', labelKey: 'jobs.statusTimeline.review', statuses: ['qualified'] },
  { key: 'interview', labelKey: 'jobs.statusTimeline.interview', statuses: ['interview'] },
  { key: 'decision', labelKey: 'jobs.statusTimeline.decision', statuses: ['accepted', 'rejected', 'hired'] },
] as const

export function timelineStepIndex(status: string | null | undefined): number {
  if (!status) return -1
  return CANDIDATE_STATUS_TIMELINE.findIndex((step) => (step.statuses as readonly string[]).includes(status))
}

export function splitCsv(value: string): string[] {
  return value.split(',').map((item) => item.trim()).filter(Boolean)
}

export function validateCandidateProfile(form: CandidateProfileForm): Record<string, string> {
  const errors: Record<string, string> = {}
  if (!form.fullName.trim()) {
    errors.fullName = 'jobs.fullNameRequired'
  }
  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'jobs.emailInvalid'
  }
  if (form.yearsOfExperience !== '' && Number.isNaN(Number(form.yearsOfExperience))) {
    errors.yearsOfExperience = 'jobs.yearsOfExperienceInvalid'
  }
  const years = Number(form.yearsOfExperience)
  if (form.yearsOfExperience !== '' && (years < 0 || years > 80)) {
    errors.yearsOfExperience = 'jobs.yearsOfExperienceInvalid'
  }
  return errors
}

export function toCandidatePayload(form: CandidateProfileForm): Record<string, unknown> {
  return {
    full_name: form.fullName.trim(),
    email: form.email || undefined,
    phone: form.phone || undefined,
    specialization: form.specialization || undefined,
    target_job_title: form.targetJobTitle || undefined,
    biography: form.biography || undefined,
    country: form.country || undefined,
    city: form.city || undefined,
    date_of_birth: form.dateOfBirth || undefined,
    nationality: form.nationality || undefined,
    address: form.address || undefined,
    years_of_experience: form.yearsOfExperience === '' ? undefined : Number(form.yearsOfExperience),
    skills: splitCsv(form.skills),
    languages: splitCsv(form.languages),
    experience: form.experienceItems,
    education: form.educationItems,
  }
}

export function ownerCompletenessPercent(input: {
  full_name?: string | null
  email?: string | null
  phone?: string | null
  country?: string | null
  city?: string | null
  target_job_title?: string | null
  biography?: string | null
  specialization?: string | null
  education?: unknown[] | null
  experience?: unknown[] | null
  skills?: unknown[] | null
  languages?: unknown[] | null
  has_cv?: boolean | null
}): number {
  const filled = [
    Boolean(input.full_name && input.email && input.phone && input.country && input.city),
    Boolean(input.target_job_title && input.biography && input.specialization),
    Array.isArray(input.education) && input.education.length > 0,
    Array.isArray(input.experience) && input.experience.length > 0,
    Array.isArray(input.skills) && input.skills.length > 0,
    Array.isArray(input.languages) && input.languages.length > 0,
    Boolean(input.has_cv),
  ].filter(Boolean).length

  return Math.round((filled / 7) * 100)
}

export function authorizedUnlockFromPay(
  requestId: number,
  pay: {
    transaction?: { payment_status?: string | null }
    exchange?: { candidate_contact?: { email?: string | null; phone?: string | null } | null } | null
    hiring_record?: { id?: number } | null
  },
): {
  requestId: number
  candidateEmail?: string | null
  candidatePhone?: string | null
  paymentStatus?: string
  hired?: boolean
} | null {
  const email = pay.exchange?.candidate_contact?.email
  if (pay.transaction?.payment_status !== 'completed' || !email) {
    return null
  }

  return {
    requestId,
    candidateEmail: email,
    candidatePhone: pay.exchange?.candidate_contact?.phone,
    paymentStatus: pay.transaction.payment_status,
    hired: Boolean(pay.hiring_record?.id),
  }
}
