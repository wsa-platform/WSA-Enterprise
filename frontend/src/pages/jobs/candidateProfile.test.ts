import { describe, expect, it } from 'vitest'
import {
  ownerCompletenessPercent,
  timelineStepIndex,
  toCandidatePayload,
  validateCandidateProfile,
  authorizedUnlockFromPay,
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
  it('requires a full name and rejects invalid email', () => {
    expect(validateCandidateProfile(emptyForm())).toMatchObject({ fullName: 'jobs.fullNameRequired' })
    expect(validateCandidateProfile(emptyForm({ fullName: 'Ada', email: 'bad' })).email).toBe('jobs.emailInvalid')
    expect(validateCandidateProfile(emptyForm({ fullName: 'Ada', email: 'ada@wsa.test' }))).toEqual({})
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
