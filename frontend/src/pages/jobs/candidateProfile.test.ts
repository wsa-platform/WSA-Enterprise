import { describe, expect, it } from 'vitest'
import {
  ownerCompletenessPercent,
  timelineStepIndex,
  toCandidatePayload,
  toCandidateSavePayload,
  toCandidateSectionPayload,
  toDateInputValue,
  nextProfileSection,
  parseYearsOfExperience,
  countNameParts,
  validateCandidateProfile,
  validateCandidateSection,
  authorizedUnlockFromPay,
  isInternationalPhone,
  isLettersOnlyText,
  isNumericOnlyText,
  PROFILE_SECTION_ORDER,
  isPdfCvFile,
  isPdfQualificationFile,
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
  it('keeps years of experience as a numeric professional-summary value', () => {
    expect(parseYearsOfExperience('5')).toBe(5)
    expect(parseYearsOfExperience('')).toBeNull()
    expect(parseYearsOfExperience('bad')).toBeNull()
  })

  it('requires a four-part full name and the rest of the personal fields', () => {
    expect(validateCandidateProfile(emptyForm())).toMatchObject({
      fullName: 'jobs.fullNameRequired',
      email: 'jobs.emailRequired',
      phone: 'jobs.phoneRequired',
      country: 'jobs.residenceCountryRequired',
      city: 'jobs.cityRequired',
      dateOfBirth: 'jobs.dateOfBirthRequired',
      nationality: 'jobs.nationalityRequired',
      address: 'jobs.addressRequired',
    })
    expect(validateCandidateProfile(emptyForm({ fullName: 'Ada', email: 'bad' })).fullName).toBe('jobs.fullNameFourPartsRequired')
    expect(validateCandidateProfile(emptyForm({ fullName: 'Ahmed Mohamed Ali', email: 'ada@wsa.test' })).fullName).toBe('jobs.fullNameFourPartsRequired')
    expect(countNameParts('أحمد محمد علي حسن')).toBe(4)
    const completePersonal = emptyForm({
      fullName: 'أحمد محمد علي حسن',
      email: 'ada@wsa.test',
      phone: '+966522222222',
      country: 'SA',
      city: 'Riyadh',
      dateOfBirth: '1990-01-15',
      nationality: 'SA',
      address: 'Olaya',
    })
    expect(validateCandidateProfile(completePersonal).fullName).toBeUndefined()
    expect(validateCandidateProfile(completePersonal).email).toBeUndefined()
    expect(validateCandidateProfile(completePersonal).primaryQualification).toBe('jobs.primaryQualificationRequired')
    expect(validateCandidateProfile(completePersonal).primaryQualificationDocument).toBe('jobs.primaryQualificationDocumentRequired')
    expect(validateCandidateProfile(emptyForm({
      ...completePersonal,
      educationItems: [{ degree: 'BSc Agricultural Engineering' }],
    }), { qualificationFile: { name: 'degree.jpg', type: 'image/jpeg', size: 1200 } }).primaryQualificationDocument).toBe('jobs.primaryQualificationPdfOnly')
    expect(validateCandidateProfile(emptyForm({
      ...completePersonal,
      educationItems: [{ degree: 'BSc Agricultural Engineering' }],
    }), { hasPrimaryQualificationDocument: true })).toEqual({})
    expect(validateCandidateProfile(emptyForm({
      fullName: 'Ahmed Mohamed Ali 123',
      email: 'ada@wsa.test',
      phone: '+966522222222',
      country: 'SA',
      city: 'Riyadh',
      dateOfBirth: '1990-01-15',
      nationality: 'EG',
      address: 'Olaya',
    })).fullName).toBe('jobs.fullNameLettersOnly')
    expect(validateCandidateProfile(emptyForm({
      fullName: 'Ahmed Mohamed Ali Hassan',
      email: 'not-an-email',
      phone: '+966522222222',
      country: 'TR',
      city: 'Riyadh',
      dateOfBirth: '1990-01-15',
      nationality: 'EG',
      address: 'Olaya',
    })).email).toBe('jobs.emailInvalid')
    expect(validateCandidateProfile(emptyForm({
      fullName: 'Ahmed Mohamed Ali Hassan',
      email: 'ada@wsa.test',
      phone: '90555abc',
      country: 'TR',
      city: 'Riyadh',
      dateOfBirth: '1990-01-15',
      nationality: 'EG',
      address: 'Olaya',
    })).phone).toBe('jobs.phoneInvalid')
    expect(isLettersOnlyText('Ahmed123')).toBe(false)
    expect(isLettersOnlyText('François')).toBe(true)
    expect(isLettersOnlyText('Ahmed Mohamed Ali Hassan')).toBe(true)
    expect(isNumericOnlyText('12345')).toBe(true)
    expect(isNumericOnlyText('3D')).toBe(false)
    expect(isNumericOnlyText('irrigation, soil')).toBe(false)
    expect(isInternationalPhone('+905551112233')).toBe(true)
    expect(isInternationalPhone('+9665')).toBe(false)
    expect(validateCandidateProfile(emptyForm({
      fullName: 'Ahmed Mohamed Ali Hassan',
      email: 'ada@wsa.test',
      phone: '+966522222222',
      country: 'SA',
      city: 'Riyadh12',
      dateOfBirth: '1990-01-15',
      nationality: 'EG',
      address: 'Olaya',
    })).city).toBe('jobs.cityLettersOnly')
    expect(validateCandidateProfile(emptyForm({
      fullName: 'Ahmed Mohamed Ali Hassan',
      email: 'ada@wsa.test',
      phone: '+966522222222',
      country: 'SA',
      city: 'Riyadh',
      dateOfBirth: '1990-13-40',
      nationality: 'EG',
      address: 'Olaya',
    })).dateOfBirth).toBe('jobs.dateOfBirthInvalid')
    expect(validateCandidateProfile(emptyForm({
      fullName: 'Ahmed Mohamed Ali Hassan',
      email: 'ada@wsa.test',
      phone: '+966522222222',
      country: 'SA',
      city: 'Riyadh',
      dateOfBirth: '1990-01-15',
      nationality: 'EG',
      address: '12345',
    })).address).toBe('jobs.naturalLanguageNotNumeric')
    expect(validateCandidateProfile(emptyForm({
      fullName: 'Ahmed Mohamed Ali Hassan',
      email: 'ada@wsa.test',
      phone: '+966522222222',
      country: 'SA',
      city: 'Riyadh',
      dateOfBirth: '1990-01-15',
      nationality: 'EG',
      address: 'Olaya',
      targetJobTitle: 'Engineer123',
    })).targetJobTitle).toBe('jobs.jobTitleLettersOnly')
  })

  it('accepts PDF CVs and rejects other file types', () => {
    expect(isPdfCvFile({ name: 'resume.pdf', type: 'application/pdf', size: 1200 })).toBe(true)
    expect(isPdfCvFile({ name: 'resume.PDF', type: 'application/pdf', size: 1200 })).toBe(true)
    expect(isPdfCvFile({ name: 'resume.docx', type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', size: 1200 })).toBe(false)
    expect(isPdfCvFile({ name: 'resume.jpg', type: 'image/jpeg', size: 1200 })).toBe(false)
    expect(isPdfCvFile({ name: 'resume.png', type: 'image/png', size: 1200 })).toBe(false)
    expect(isPdfCvFile({ name: 'resume.pdf', type: 'image/jpeg', size: 1200 })).toBe(false)
    expect(isPdfCvFile({ name: 'resume.pdf.exe', type: 'application/pdf', size: 1200 })).toBe(false)
    expect(isPdfQualificationFile({ name: 'degree.pdf', type: 'application/pdf', size: 1200 })).toBe(true)
    expect(isPdfQualificationFile({ name: 'degree.jpg', type: 'image/jpeg', size: 1200 })).toBe(false)
  })

  it('keeps nationality independent from current country of residence', () => {
    const payload = toCandidatePayload(emptyForm({
      fullName: 'Ahmed Mohamed Ali Hassan',
      email: 'ada@wsa.test',
      phone: '+966522222222',
      country: 'TR',
      city: 'Istanbul',
      dateOfBirth: '1990-01-15',
      nationality: 'EG',
      address: 'Kadikoy',
    }))
    expect(payload.nationality).toBe('EG')
    expect(payload.country).toBe('TR')
  })

  it('omits incomplete education from save until a qualification document exists', () => {
    const form = emptyForm({
      fullName: 'Ahmed Mohamed Ali Hassan',
      educationItems: [{ degree: 'BSc', institution: 'Cairo University' }],
    })
    expect(toCandidatePayload(form).education).toEqual([{ degree: 'BSc', institution: 'Cairo University' }])
    expect(toCandidateSavePayload(form, { hasPrimaryQualificationDocument: false }).education).toBeUndefined()
    expect(toCandidateSavePayload(form, { hasPrimaryQualificationDocument: true }).education).toEqual([
      { degree: 'BSc', institution: 'Cairo University' },
    ])
  })

  it('omits empty education and experience rows from the save payload', () => {
    const payload = toCandidatePayload(emptyForm({
      fullName: 'Ahmed Mohamed Ali Hassan',
      educationItems: [{}, { degree: 'BSc', institution: 'Cairo University', year: 2018 }],
      experienceItems: [{}, { title: 'Engineer', company: 'WSA' }],
    }))
    expect(payload.education).toEqual([{ degree: 'BSc', institution: 'Cairo University', year: 2018 }])
    expect(payload.experience).toEqual([{ title: 'Engineer', company: 'WSA' }])
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
      nationality: 'SA',
      address: 'حي النخيل',
    }))
    expect(payload).toMatchObject({
      full_name: 'فاطمة محمد علي العتيبي',
      email: 'seeker@wsa.test',
      phone: '+966500000001',
      country: 'SA',
      city: 'الرياض',
      date_of_birth: '1994-05-12',
      nationality: 'SA',
      address: 'حي النخيل',
    })
  })

  it('keeps intermediate personal saves from wiping education or experience', () => {
    const form = emptyForm({
      fullName: 'Ahmed Mohamed Ali Hassan',
      email: 'ada@wsa.test',
      phone: '+966522222222',
      country: 'SA',
      city: 'Riyadh',
      dateOfBirth: '1990-01-15',
      nationality: 'SA',
      address: 'Olaya',
      educationItems: [{ degree: 'BSc' }],
      experienceItems: [{ title: 'Analyst' }],
      skills: 'irrigation',
      languages: 'ar',
    })
    const personal = toCandidateSectionPayload(form, 'personal')
    expect(personal).toMatchObject({
      full_name: 'Ahmed Mohamed Ali Hassan',
      email: 'ada@wsa.test',
      nationality: 'SA',
    })
    expect(personal).not.toHaveProperty('experience')
    expect(personal).not.toHaveProperty('education')
    expect(personal).not.toHaveProperty('skills')
    expect(personal).not.toHaveProperty('languages')
    expect(toCandidateSectionPayload(form, 'education')).toMatchObject({
      education: [{ degree: 'BSc' }],
    })
    expect(toCandidateSectionPayload(form, 'education')).not.toHaveProperty('experience')
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
      phone: '+966522222222',
      country: 'SA',
      city: 'Riyadh',
      dateOfBirth: '1990-01-15',
      nationality: 'SA',
      address: 'Olaya',
    }
    expect(validateCandidateSection(emptyForm({ ...validPersonal, yearsOfExperience: 'bad' }), 'personal')).toEqual({})
    expect(validateCandidateSection(emptyForm({ fullName: 'Ada', email: 'bad' }), 'personal').email).toBe('jobs.emailInvalid')
    expect(validateCandidateSection(emptyForm({ ...validPersonal, yearsOfExperience: 'bad' }), 'professional').yearsOfExperience).toBe('jobs.yearsOfExperienceInvalid')
    expect(validateCandidateSection(emptyForm({ ...validPersonal, biography: '12345' }), 'professional').biography).toBe('jobs.naturalLanguageNotNumeric')
    expect(validateCandidateSection(emptyForm({ ...validPersonal, skills: '3D, irrigation' }), 'professional').skills).toBeUndefined()
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
    expect(nextProfileSection('cv')).toBeNull()
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
