import { describe, expect, it } from 'vitest'
import ar from '../../i18n/locales/ar.json'
import {
  EMPTY_EMPLOYER_FILTERS,
  employerGuestEntryPath,
  employerRoleGate,
  employerSearchView,
  employerSeekerLeaksProtectedData,
  sanitizeEmployerSeeker,
  unlockFromVerifiedPayment,
} from './employerWorkspace'
import { EMPLOYER_ENTER } from '../../navigation/roleDestinations'
import type { EmployerSeeker } from '../../api/jobs'

function publicSeeker(overrides: Partial<EmployerSeeker> = {}): EmployerSeeker {
  return {
    id: 7,
    full_name: 'أحمد محمد علي حسن',
    has_photo: false,
    target_job_title: 'مهندس زراعي',
    country: 'SA',
    city: 'Riyadh',
    region: null,
    specialization: 'زراعة',
    years_of_experience: 5,
    skills: ['ري'],
    languages: ['ar'],
    education: [{ degree: 'بكالوريوس' }],
    experience: [{ title: 'مهندس', company: 'مزرعة' }],
    biography: 'ملخص مهني',
    nationality: 'SA',
    employment_status: 'job_seeker',
    employment_label: 'طالب وظيفة',
    has_cv: true,
    ...overrides,
  }
}

describe('employer workspace helpers', () => {
  it('keeps every backend-supported search filter on the form', () => {
    expect(Object.keys(EMPTY_EMPLOYER_FILTERS).sort()).toEqual([
      'city',
      'country',
      'desired_salary',
      'job_title',
      'languages',
      'qualification',
      'skills',
      'specialization',
      'work_type',
      'years_of_experience',
    ])
  })

  it('sends unauthenticated employers to the existing entry route', () => {
    expect(employerGuestEntryPath()).toBe(EMPLOYER_ENTER)
    expect(employerGuestEntryPath()).toBe('/employer/enter')
    expect(employerRoleGate({ authenticated: false, isJobSeeker: false, isEmployer: false })).toBe('guest')
  })

  it('blocks a job-seeker from employer registration without creating a second role', () => {
    expect(employerRoleGate({ authenticated: true, isJobSeeker: true, isEmployer: false })).toBe('job_seeker')
    expect(ar.auth.employer.blockedJobSeeker).toContain('طالب وظيفة')
    expect(ar.auth.employer.blockedJobSeeker).toContain('صاحب عمل')
  })

  it('strips protected contact and identity fields from candidate cards', () => {
    const leaked = sanitizeEmployerSeeker({
      ...publicSeeker(),
      email: 'hidden@wsa.test',
      phone: '+966500000000',
      address: 'Olaya',
      user_id: 99,
      organization_id: 3,
      internal_notes: 'secret',
      internal_rating: 5,
    } as EmployerSeeker)
    expect(employerSeekerLeaksProtectedData(leaked)).toBe(false)
    expect(leaked).not.toHaveProperty('email')
    expect(leaked).not.toHaveProperty('phone')
    expect(leaked).not.toHaveProperty('address')
    expect(leaked).not.toHaveProperty('user_id')
    expect(leaked).not.toHaveProperty('organization_id')
  })

  it('renders loading, empty, error, and results search states', () => {
    expect(employerSearchView({ loading: true, error: '', count: 0 })).toBe('loading')
    expect(employerSearchView({ loading: false, error: 'fail', count: 0 })).toBe('error')
    expect(employerSearchView({ loading: false, error: '', count: 0 })).toBe('empty')
    expect(employerSearchView({ loading: false, error: '', count: 2 })).toBe('results')
  })

  it('unlocks contact only after backend-confirmed completed payment', () => {
    expect(unlockFromVerifiedPayment(4, {
      transaction: { id: 1, payment_status: 'failed', contact_exchange_status: 'locked' },
      exchange: { candidate_contact: { email: 'forged@wsa.test', phone: '+1' } },
      hiring_record: { id: 9, employment_status: 'hired' },
    })).toBeNull()

    expect(unlockFromVerifiedPayment(4, {
      transaction: { id: 1, payment_status: 'pending', contact_exchange_status: 'locked' },
      exchange: {},
      hiring_record: null,
    })).toBeNull()

    expect(unlockFromVerifiedPayment(4, {
      transaction: { id: 1, payment_status: 'completed', contact_exchange_status: 'unlocked' },
      exchange: { candidate_contact: { email: 'ada@wsa.test', phone: '+9665' } },
      hiring_record: { id: 12, employment_status: 'hired' },
    })).toEqual({
      candidateEmail: 'ada@wsa.test',
      candidatePhone: '+9665',
      hired: true,
    })
  })

  it('uses the required Arabic employer copy', () => {
    expect(ar.auth.employer.title).toBe('مساحة صاحب العمل')
    expect(ar.auth.employer.contactLocked).toBe('بيانات الاتصال محمية. لإتاحة بيانات التواصل المباشر، يرجى إتمام رسوم الخدمة.')
    expect(ar.auth.employer.continueToPayment).toBe('المتابعة إلى الدفع')
    expect(ar.auth.employer.requestContact).toBe('طلب بيانات الاتصال')
    expect(JSON.stringify(ar.auth.employer)).not.toContain('سيتم تصميم صفحة صاحب العمل لاحقاً')
  })
})
