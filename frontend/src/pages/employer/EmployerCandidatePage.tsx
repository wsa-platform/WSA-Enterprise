import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router-dom'
import { getEmployerSeeker, payContactRequest, requestSeekerContact } from '../../api/jobs'
import { useAuth } from '../../context/AuthContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { JobSeekerField } from '../jobs/JobSeekerField'
import { translateApiError } from '../../i18n/apiErrors'
import { EmployerSeekerPhoto } from './EmployerSeekerPhoto'
import { sanitizeEmployerSeeker, unlockFromVerifiedPayment } from './employerWorkspace'
import '../jobs/jobSeekerProfile.css'
import './employerWorkspace.css'

export function EmployerCandidatePage() {
  const { t } = useTranslation()
  const { candidateId } = useParams()
  const { token, organizationId, user } = useAuth()
  const seekerId = Number(candidateId)
  const [phase, setPhase] = useState<'locked' | 'requested' | 'paying' | 'unlocked' | 'failed'>('locked')
  const [requestId, setRequestId] = useState<number | null>(null)
  const [message, setMessage] = useState('')
  const [unlocked, setUnlocked] = useState<{ email?: string | null; phone?: string | null } | null>(null)
  const [hired, setHired] = useState(false)

  const { data, loading, error } = useAsyncData(async () => {
    if (!token || !Number.isFinite(seekerId)) throw new Error(t('errors.notAuthenticated'))
    return sanitizeEmployerSeeker(await getEmployerSeeker(token, seekerId, organizationId ?? undefined))
  }, [token, organizationId, seekerId, t])

  const requestContact = async () => {
    if (!token || !user) return
    setMessage('')
    try {
      const contactRequest = await requestSeekerContact(token, seekerId, {
        employer_contact: { name: user.name, email: user.email },
        idempotency_key: crypto.randomUUID(),
      }, organizationId ?? undefined)
      setRequestId(contactRequest.id)
      setPhase('requested')
      setMessage(contactRequest.message || t('auth.employer.contactLocked'))
    } catch (requestError) {
      setPhase('failed')
      setMessage(translateApiError(requestError) || t('jobs.contactFailed'))
    }
  }

  const continueToPayment = async () => {
    if (!token || requestId == null) return
    setPhase('paying')
    setMessage(t('auth.employer.paymentPending'))
    try {
      const paid = await payContactRequest(token, requestId, crypto.randomUUID(), organizationId ?? undefined)
      const verified = unlockFromVerifiedPayment(requestId, paid)
      if (!verified) {
        setUnlocked(null)
        setHired(false)
        setPhase('requested')
        setMessage(t('auth.employer.paymentFailed'))
        return
      }
      setUnlocked({ email: verified.candidateEmail, phone: verified.candidatePhone })
      setHired(Boolean(verified.hired))
      setPhase('unlocked')
      setMessage(t('auth.employer.hiredBanner'))
    } catch (requestError) {
      setUnlocked(null)
      setHired(false)
      setPhase('requested')
      setMessage(translateApiError(requestError) || t('auth.employer.paymentFailed'))
    }
  }

  if (loading) return <p>{t('common.loading')}</p>
  if (error || !data) return <p className="js-field-error" role="alert">{error || t('errors.forbidden')}</p>

  return (
    <div className="job-seeker-profile employer-workspace" data-testid="employer-candidate-preview">
      <header className="page-header">
        <div>
          <Link to="/employer">{t('auth.employer.search')}</Link>
          <h1>{data.full_name}</h1>
          <p className="page-description">{data.target_job_title}</p>
        </div>
        <span className={`employer-status${data.employment_status === 'hired' || hired ? ' is-hired' : ''}`}>
          {hired ? t('auth.employer.hiredStatus') : data.employment_label}
        </span>
      </header>
      <EmployerSeekerPhoto seekerId={data.id} hasPhoto={data.has_photo} />
      {hired ? <p className="notice" role="status">{t('auth.employer.hiredBanner')}</p> : null}
      <section className="panel">
        <JobSeekerField label={t('auth.employer.filters.city')} value={[data.city, data.country].filter(Boolean).join(' / ')} />
        <JobSeekerField label={t('auth.employer.education')} value={(data.education ?? []).map((row) => row.degree).filter(Boolean).join('، ')} />
        <JobSeekerField label={t('auth.employer.filters.experienceYears')} value={data.years_of_experience != null ? String(data.years_of_experience) : '—'} size="short" />
        <JobSeekerField
          label={t('auth.employer.experience')}
          value={(data.experience ?? []).map((row) => [row.title, row.company].filter(Boolean).join(' — ')).filter(Boolean).join('، ') || '—'}
        />
        <JobSeekerField label={t('auth.employer.skills')} value={(data.skills ?? []).join(' · ')} />
        <JobSeekerField label={t('auth.employer.languages')} value={(data.languages ?? []).join(' · ')} />
        <JobSeekerField label={t('auth.employer.summary')} value={data.biography ?? '—'} size="full" />
        {data.has_cv ? <JobSeekerField label={t('auth.employer.cvAvailable')} value={t('auth.employer.cvPolicy')} /> : null}
      </section>
      <section className="panel" data-testid="employer-contact-panel">
        <h2>{t('auth.employer.contactSection')}</h2>
        {phase === 'unlocked' && unlocked ? (
          <>
            <JobSeekerField label={t('common.email')} value={unlocked.email ?? '—'} />
            <JobSeekerField label={t('auth.employer.phone')} value={unlocked.phone ?? '—'} />
          </>
        ) : (
          <>
            <p data-testid="employer-contact-locked">{t('auth.employer.contactLocked')}</p>
            {phase === 'locked' || phase === 'failed' ? (
              <button type="button" className="js-btn js-btn-primary" onClick={() => void requestContact()}>
                {t('auth.employer.requestContact')}
              </button>
            ) : null}
            {phase === 'requested' ? (
              <button type="button" className="js-btn js-btn-primary" data-testid="employer-continue-payment" onClick={() => void continueToPayment()}>
                {t('auth.employer.continueToPayment')}
              </button>
            ) : null}
            {phase === 'paying' ? <p>{t('auth.employer.paymentPending')}</p> : null}
          </>
        )}
        {message ? <p role="status">{message}</p> : null}
      </section>
    </div>
  )
}
