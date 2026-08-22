import { describe, expect, it } from 'vitest'
import {
  ownerCompletenessPercent,
  timelineStepIndex,
  toCandidatePayload,
  toDateInputValue,
  nextProfileSection,
  countNameParts,
  validateCandidateProfile,
  validateCandidateSection,
  authorizedUnlockFromPay,
  PROFILE_SECTION_ORDER,
  type CandidateProfileForm,
} from './candidateProfile'

function emptyForm(overrides: Partial<CandidateProfileForm> = {}): CandidateProfileForm {
  return {
    fullName: '',
    email: '',
    phone: '',
    country: '',
    city: '',
    dateOfBirth: '',
    nationality: '',
    address: '',
    targetJobTitle: '',
    biography: '',
    yearsOfExperience: '',
    specialization: '',
    skills: '',
    languages: '',
    experienceItems: [],
    educationItems: [],
    ...overrides,
  }
}

describe('candidate profile helpers', () => {
  it('requires a four-part full name and the rest of the personal fields', () => {
    expect(validateCandidateProfile(emptyForm())).toMatchObject({
      fullName: 'jobs.fullNameRequired',
      email: 'jobs.emailRequired',
      phone: 'jobs.phoneRequired',
      country: 'jobs.countryRequired',
      city: 'jobs.cityRequired',
      dateOfBirth: 'jobs.dateOfBirthRequired',
      nationality: 'jobs.nationalityRequired',
      address: 'jobs.addressRequired',
    })
    expect(validateCandidateProfile(emptyForm({ fullName: 'Ada', email: 'bad' })).fullName).toBe('jobs.fullNameFourPartsRequired')
    expect(validateCandidateProfile(emptyForm({ fullName: 'Ahmed Mohamed Ali', email: 'ada@wsa.test' })).fullName).toBe('jobs.fullNameFourPartsRequired')
    expect(countNameParts('أحمد محمد علي حسن')).toBe(4)
    expect(validateCandidateProfile(emptyForm({
      fullName: 'أحمد محمد علي حسن',
      email: 'ada@wsa.test',
      phone: '+9665',
      country: 'SA',
      city: 'Riyadh',
      dateOfBirth: '1990-01-15',
      nationality: 'Saudi',
      address: 'Olaya',
    }))).toEqual({})
  })

  it('omits system-controlled fields from the save payload', () => {
    const payload = toCandidatePayload(emptyForm({
      fullName: 'Ada Lovelace',
      email: 'ada@wsa.test',
      yearsOfExperience: '6',
      skills: 'irrigation, soil',
    }))
    expect(payload).toMatchObject({
      full_name: 'Ada Lovelace',
      years_of_experience: 6,
      skills: ['irrigation', 'soil'],
    })
    expect(payload).not.toHaveProperty('recruitment_status')
    expect(payload).not.toHaveProperty('user_id')
    expect(payload).not.toHaveProperty('organization_id')
    expect(payload).not.toHaveProperty('payment_status')
    expect(payload).not.toHaveProperty('completeness_percent')
    expect(payload).not.toHaveProperty('recruiter_notes')
    expect(payload).not.toHaveProperty('interview_result')
    expect(payload).not.toHaveProperty('cv_path')
    expect(payload).not.toHaveProperty('photo_path')
    expect(payload).not.toHaveProperty('employment_status')
  })

  it('maps application statuses onto the server-controlled timeline', () => {
    expect(timelineStepIndex('new')).toBe(0)
    expect(timelineStepIndex('under_review')).toBe(1)
    expect(timelineStepIndex('qualified')).toBe(2)
    expect(timelineStepIndex('interview')).toBe(3)
    expect(timelineStepIndex('hired')).toBe(4)
    expect(timelineStepIndex('rejected')).toBe(4)
  })

  it('recalculates owner completion from supported sections only', () => {
    expect(ownerCompletenessPercent({})).toBe(0)
    expect(ownerCompletenessPercent({
      full_name: 'Ada',
      email: 'ada@wsa.test',
      phone: '+1',
      country: 'UK',
      city: 'London',
      target_job_title: 'Engineer',
      biography: 'Summary',
      specialization: 'Math',
      education: [{ degree: 'BSc' }],
      experience: [{ title: 'Analyst' }],
      skills: ['math'],
      languages: ['en'],
      has_cv: true,
    })).toBe(100)
  })

  it('maps personal fields onto the matching backend properties', () => {
    const payload = toCandidatePayload(emptyForm({
      fullName: 'فاطمة محمد علي العتيبي',
      email: 'seeker@wsa.test',
      phone: '+966500000001',
      country: 'SA',
      city: 'الرياض',
      dateOfBirth: '1994-05-12',
      nationality: 'سعودية',
      address: 'حي النخيل',
    }))
    expect(payload).toMatchObject({
      full_name: 'فاطمة محمد علي العتيبي',
      email: 'seeker@wsa.test',
      phone: '+966500000001',
      country: 'SA',
      city: 'الرياض',
      date_of_birth: '1994-05-12',
      nationality: 'سعودية',
      address: 'حي النخيل',
    })
  })

  it('normalizes ISO dates so the date-of-birth control can display them', () => {
    expect(toDateInputValue('1994-05-12T00:00:00.000000Z')).toBe('1994-05-12')
    expect(toDateInputValue('1994-05-12')).toBe('1994-05-12')
    expect(toDateInputValue(null)).toBe('')
  })

  it('keeps personal-section validation on personal fields only', () => {
    const validPersonal = {
      fullName: 'Ahmed Mohamed Ali Hassan',
      email: 'ada@wsa.test',
      phone: '+9665',
      country: 'SA',
      city: 'Riyadh',
      dateOfBirth: '1990-01-15',
      nationality: 'Saudi',
      address: 'Olaya',
    }
    expect(validateCandidateSection(emptyForm({ ...validPersonal, yearsOfExperience: 'bad' }), 'personal')).toEqual({})
    expect(validateCandidateSection(emptyForm({ fullName: 'Ada', email: 'bad' }), 'personal').email).toBe('jobs.emailInvalid')
    expect(validateCandidateSection(emptyForm({ ...validPersonal, yearsOfExperience: 'bad' }), 'professional').yearsOfExperience).toBe('jobs.yearsOfExperienceInvalid')
  })

  it('requires a primary qualification and document, but not additional qualifications', () => {
    const form = emptyForm({ educationItems: [{ degree: 'BSc Agricultural Engineering' }, { degree: 'Diploma' }] })
    expect(validateCandidateSection(form, 'education')).toMatchObject({
      primaryQualificationDocument: 'jobs.primaryQualificationDocumentRequired',
    })
    expect(validateCandidateSection(emptyForm({ educationItems: [{}] }), 'education').primaryQualification).toBe('jobs.primaryQualificationRequired')
    expect(validateCandidateSection(form, 'education', { hasPrimaryQualificationDocument: true })).toEqual({})
  })

  it('advances profile sections in the existing page order without leaving the page or opening the dashboard', () => {
    expect(nextProfileSection('personal')).toBe('professional')
    expect(nextProfileSection('professional')).toBe('education')
    expect(nextProfileSection('education')).toBe('experience')
    expect(nextProfileSection('experience')).toBe('cv')
    expect(nextProfileSection('cv')).toBe('photo')
    expect(nextProfileSection('photo')).toBeNull()
    for (const section of PROFILE_SECTION_ORDER) {
      const next = nextProfileSection(section)
      expect(String(next ?? '')).not.toContain('dashboard')
    }
  })

  it('unlocks contact only from a server-verified payment payload', () => {
    expect(authorizedUnlockFromPay(9, {
      transaction: { payment_status: 'completed' },
    })).toBeNull()
    expect(authorizedUnlockFromPay(9, {
      transaction: { payment_status: 'paid' },
      exchange: { candidate_contact: { email: 'forged@wsa.test' } },
    })).toBeNull()
    expect(authorizedUnlockFromPay(9, {
      transaction: { payment_status: 'completed' },
      exchange: { candidate_contact: { email: 'ada@wsa.test', phone: '+1' } },
      hiring_record: { id: 4 },
    })).toEqual({
      requestId: 9,
      candidateEmail: 'ada@wsa.test',
      candidatePhone: '+1',
      paymentStatus: 'completed',
      hired: true,
    })
  })
})
