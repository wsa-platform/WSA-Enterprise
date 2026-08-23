import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router-dom'
import { getEmployerSeeker, payContactRequest, requestSeekerContact } from '../../api/jobs'
import { useAuth } from '../../context/AuthContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { JobSeekerField } from '../jobs/JobSeekerField'
import { translateApiError } from '../../i18n/apiErrors'
import '../jobs/jobSeekerProfile.css'
import './employerWorkspace.css'

export function EmployerCandidatePage() {
  const { t } = useTranslation()
  const { candidateId } = useParams()
  const { token, organizationId, user } = useAuth()
  const seekerId = Number(candidateId)
  const [message, setMessage] = useState('')
  const [paying, setPaying] = useState(false)
  const [unlocked, setUnlocked] = useState<{ email?: string | null; phone?: string | null } | null>(null)
  const [hired, setHired] = useState(false)

  const { data, loading, error } = useAsyncData(async () => {
    if (!token || !Number.isFinite(seekerId)) throw new Error(t('errors.notAuthenticated'))
    return getEmployerSeeker(token, seekerId, organizationId ?? undefined)
  }, [token, organizationId, seekerId, t])

  const handlePay = async () => {
    if (!token || !user) return
    setPaying(true)
    setMessage('')
    try {
      const contactRequest = await requestSeekerContact(token, seekerId, {
        employer_contact: { name: user.name, email: user.email },
        idempotency_key: crypto.randomUUID(),
      }, organizationId ?? undefined)
      setMessage(contactRequest.message || t('auth.employer.contactLocked'))
      const paid = await payContactRequest(token, contactRequest.id, crypto.randomUUID(), organizationId ?? undefined)
      const contact = paid.exchange?.candidate_contact
      setUnlocked({ email: contact?.email, phone: contact?.phone })
      setHired(Boolean(paid.hiring_record?.id))
    } catch (requestError) {
      setUnlocked(null)
      setHired(false)
      setMessage(translateApiError(requestError) || t('jobs.contactFailed'))
    } finally {
      setPaying(false)
    }
  }

  if (loading) return <p>{t('common.loading')}</p>
  if (error || !data) return <p className="js-field-error">{error}</p>

  return (
    <div className="job-seeker-profile employer-workspace">
      <header className="page-header">
        <div>
          <Link to="/employer/search">{t('auth.employer.search')}</Link>
          <h1>{data.full_name}</h1>
          <p className="page-description">{data.target_job_title}</p>
        </div>
        <span className={`employer-status${data.employment_status === 'hired' || hired ? ' is-hired' : ''}`}>
          {hired ? t('auth.employer.hiredStatus') : data.employment_label}
        </span>
      </header>
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
      </section>
      <section className="panel">
        <p>{t('auth.employer.contactLocked')}</p>
        {unlocked ? (
          <>
            <JobSeekerField label={t('jobs.employerEmail')} value={unlocked.email ?? '—'} />
            <JobSeekerField label={t('auth.employer.phone')} value={unlocked.phone ?? '—'} />
          </>
        ) : (
          <button type="button" className="js-btn js-btn-primary" disabled={paying || data.employment_status === 'hired'} onClick={() => void handlePay()}>
            {paying ? t('auth.employer.paymentPending') : t('auth.employer.continueToPayment')}
          </button>
        )}
        {message ? <p role="status">{message}</p> : null}
      </section>
    </div>
  )
}
