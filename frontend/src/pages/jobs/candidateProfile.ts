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

export type CandidateSectionValidationOptions = {
  hasPrimaryQualificationDocument?: boolean
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

export const PROFILE_SECTION_ORDER = [
  'personal',
  'professional',
  'education',
  'experience',
  'cv',
] as const

export const JOB_SEEKER_CV_MAX_KILOBYTES = 5120
export const JOB_SEEKER_CV_MAX_BYTES = JOB_SEEKER_CV_MAX_KILOBYTES * 1024

export function isPdfCvFile(file: { name: string; type?: string; size?: number }): boolean {
  const base = file.name.trim().toLowerCase().split(/[/\\]/).pop() ?? ''
  const extension = base.includes('.') ? base.slice(base.lastIndexOf('.')) : ''
  if (extension !== '.pdf') return false
  const type = (file.type ?? '').toLowerCase()
  if (type && type !== 'application/pdf' && type !== 'application/x-pdf') return false
  if (typeof file.size === 'number' && file.size > JOB_SEEKER_CV_MAX_BYTES) return false
  return true
}

export type ProfileSectionId = (typeof PROFILE_SECTION_ORDER)[number]

export function splitCsv(value: string): string[] {
  return value.split(',').map((item) => item.trim()).filter(Boolean)
}

export function toDateInputValue(value: string | null | undefined): string {
  if (!value) return ''
  const match = /^(\d{4}-\d{2}-\d{2})/.exec(value)
  return match?.[1] ?? ''
}

export function countNameParts(value: string): number {
  return value.trim().split(/\s+/).filter(Boolean).length
}

export function containsDigits(value: string): boolean {
  return /\p{N}/u.test(value)
}

export function isLettersOnlyText(value: string): boolean {
  const trimmed = value.trim()
  if (!trimmed) return false
  return /^[\p{L}\p{M}](?:[\p{L}\p{M}\s'.’-]*[\p{L}\p{M}])?$/u.test(trimmed) && !containsDigits(trimmed)
}

export function isNumericOnlyText(value: string): boolean {
  const trimmed = value.trim()
  if (!trimmed) return false
  return /\p{N}/u.test(trimmed) && !/\p{L}/u.test(trimmed)
}

export function isInternationalPhone(value: string): boolean {
  return /^\+[1-9]\d{7,14}$/.test(value.trim())
}

export function isValidEmail(value: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())
}

export function isValidIsoDate(value: string): boolean {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false
  const [year, month, day] = value.split('-').map(Number)
  const date = new Date(year, month - 1, day)
  return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day
}

export function sanitizeInternationalPhone(raw: string): string {
  const compact = raw.replace(/[^\d+]/g, '')
  if (!compact) return ''
  if (compact.startsWith('+')) return `+${compact.slice(1).replace(/\+/g, '')}`
  return compact.replace(/\+/g, '')
}

export function sanitizeYearsOfExperience(raw: string): string {
  return raw.replace(/\D/g, '').slice(0, 2)
}

export function primaryEducationItem(items: EducationItem[]): EducationItem {
  return items[0] ?? {}
}

export function additionalEducationItems(items: EducationItem[]): EducationItem[] {
  return items.slice(1)
}

export function parseYearsOfExperience(value: string | number | null | undefined): number | null {
  if (value === null || value === undefined || value === '') return null
  const years = Number(value)
  return Number.isFinite(years) ? years : null
}

export function nextProfileSection(section: ProfileSectionId): ProfileSectionId | null {
  const index = PROFILE_SECTION_ORDER.indexOf(section)
  if (index < 0 || index >= PROFILE_SECTION_ORDER.length - 1) return null
  return PROFILE_SECTION_ORDER[index + 1]
}

export function validateCandidateSection(
  form: CandidateProfileForm,
  section: ProfileSectionId,
  options: CandidateSectionValidationOptions = {},
): Record<string, string> {
  if (section === 'personal') {
    const errors: Record<string, string> = {}
    if (!form.fullName.trim()) {
      errors.fullName = 'jobs.fullNameRequired'
    } else if (countNameParts(form.fullName) < 4) {
      errors.fullName = 'jobs.fullNameFourPartsRequired'
    } else if (!isLettersOnlyText(form.fullName)) {
      errors.fullName = 'jobs.fullNameLettersOnly'
    }
    if (!form.email.trim()) {
      errors.email = 'jobs.emailRequired'
    } else if (!isValidEmail(form.email)) {
      errors.email = 'jobs.emailInvalid'
    }
    if (!form.phone.trim()) {
      errors.phone = 'jobs.phoneRequired'
    } else if (!isInternationalPhone(form.phone)) {
      errors.phone = 'jobs.phoneInvalid'
    }
    if (!form.country.trim()) errors.country = 'jobs.residenceCountryRequired'
    if (!form.city.trim()) {
      errors.city = 'jobs.cityRequired'
    } else if (!isLettersOnlyText(form.city)) {
      errors.city = 'jobs.cityLettersOnly'
    }
    if (!form.dateOfBirth.trim()) {
      errors.dateOfBirth = 'jobs.dateOfBirthRequired'
    } else if (!isValidIsoDate(form.dateOfBirth)) {
      errors.dateOfBirth = 'jobs.dateOfBirthInvalid'
    }
    if (!form.nationality.trim()) errors.nationality = 'jobs.nationalityRequired'
    if (!form.address.trim()) {
      errors.address = 'jobs.addressRequired'
    } else if (isNumericOnlyText(form.address)) {
      errors.address = 'jobs.naturalLanguageNotNumeric'
    }
    return errors
  }

  if (section === 'professional') {
    const errors: Record<string, string> = {}
    if (form.targetJobTitle.trim() && !isLettersOnlyText(form.targetJobTitle)) {
      errors.targetJobTitle = 'jobs.jobTitleLettersOnly'
    }
    if (form.specialization.trim() && !isLettersOnlyText(form.specialization)) {
      errors.specialization = 'jobs.professionalFieldLettersOnly'
    }
    if (form.biography.trim() && isNumericOnlyText(form.biography)) {
      errors.biography = 'jobs.naturalLanguageNotNumeric'
    }
    if (form.skills.trim() && isNumericOnlyText(form.skills)) {
      errors.skills = 'jobs.naturalLanguageNotNumeric'
    }
    if (form.languages.trim() && isNumericOnlyText(form.languages)) {
      errors.languages = 'jobs.naturalLanguageNotNumeric'
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

  if (section === 'education') {
    const errors: Record<string, string> = {}
    const primary = primaryEducationItem(form.educationItems)
    if (!primary.degree?.trim()) {
      errors.primaryQualification = 'jobs.primaryQualificationRequired'
    }
    if (!options.hasPrimaryQualificationDocument) {
      errors.primaryQualificationDocument = 'jobs.primaryQualificationDocumentRequired'
    }
    return errors
  }

  return {}
}

export function validateCandidateProfile(
  form: CandidateProfileForm,
): Record<string, string> {
  return {
    ...validateCandidateSection(form, 'personal'),
    ...validateCandidateSection(form, 'professional'),
  }
}

export function compactEducationItems(items: EducationItem[]): EducationItem[] {
  return items
    .map((item) => {
      const yearValue = item.year === '' || item.year == null ? undefined : Number(item.year)
      return {
        ...item,
        year: yearValue != null && Number.isFinite(yearValue) ? yearValue : undefined,
      }
    })
    .filter((item) => [item.degree, item.institution, item.country, item.year].some((value) => value != null && String(value).trim() !== ''))
}

export function compactExperienceItems(items: ExperienceItem[]): ExperienceItem[] {
  return items.filter((item) => [item.title, item.company, item.start_date, item.end_date, item.description].some((value) => Boolean(value && String(value).trim())))
}

export function toCandidatePayload(form: CandidateProfileForm): Record<string, unknown> {
  return {
    full_name: form.fullName.trim(),
    email: form.email.trim() || undefined,
    phone: form.phone.trim() || undefined,
    specialization: form.specialization || undefined,
    target_job_title: form.targetJobTitle || undefined,
    biography: form.biography || undefined,
    country: form.country.trim() || undefined,
    city: form.city.trim() || undefined,
    date_of_birth: form.dateOfBirth || undefined,
    nationality: form.nationality.trim() || undefined,
    address: form.address.trim() || undefined,
    years_of_experience: form.yearsOfExperience === '' ? undefined : Number(form.yearsOfExperience),
    skills: splitCsv(form.skills),
    languages: splitCsv(form.languages),
    experience: compactExperienceItems(form.experienceItems),
    education: compactEducationItems(form.educationItems),
  }
}

export function toCandidateSavePayload(
  form: CandidateProfileForm,
  options: { hasPrimaryQualificationDocument?: boolean } = {},
): Record<string, unknown> {
  const payload = toCandidatePayload(form)
  const education = Array.isArray(payload.education) ? (payload.education as EducationItem[]) : []
  const primaryDegree = String(primaryEducationItem(education).degree ?? '').trim()
  if (primaryDegree && !options.hasPrimaryQualificationDocument) {
    delete payload.education
  }
  return payload
}

const PERSONAL_PAYLOAD_KEYS = [
  'full_name',
  'email',
  'phone',
  'country',
  'city',
  'date_of_birth',
  'nationality',
  'address',
] as const

const PROFESSIONAL_PAYLOAD_KEYS = [
  'specialization',
  'target_job_title',
  'biography',
  'years_of_experience',
  'skills',
  'languages',
] as const

export function toCandidateSectionPayload(
  form: CandidateProfileForm,
  section: ProfileSectionId,
): Record<string, unknown> {
  const all = toCandidatePayload(form)
  const pick = (keys: readonly string[]) =>
    Object.fromEntries(keys.filter((key) => key in all).map((key) => [key, all[key]]))

  if (section === 'personal') return pick(PERSONAL_PAYLOAD_KEYS)
  if (section === 'professional') return { ...pick(PERSONAL_PAYLOAD_KEYS), ...pick(PROFESSIONAL_PAYLOAD_KEYS) }
  if (section === 'education') return { ...pick(PERSONAL_PAYLOAD_KEYS), education: all.education }
  if (section === 'experience') return { ...pick(PERSONAL_PAYLOAD_KEYS), experience: all.experience }
  return pick(PERSONAL_PAYLOAD_KEYS)
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
