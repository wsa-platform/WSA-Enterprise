import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { searchEmployerSeekers, type EmployerSeekerFilters } from '../../api/jobs'
import { useAuth } from '../../context/AuthContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { JobSeekerField } from '../jobs/JobSeekerField'
import { translateApiError } from '../../i18n/apiErrors'
import '../jobs/jobSeekerProfile.css'
import './employerWorkspace.css'

const emptyFilters: EmployerSeekerFilters = {
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

export function EmployerSearchPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const [draft, setDraft] = useState(emptyFilters)
  const [applied, setApplied] = useState(emptyFilters)
  const [page, setPage] = useState(1)

  const { data, loading, error } = useAsyncData(async () => {
    if (!token) throw new Error(t('errors.notAuthenticated'))
    return searchEmployerSeekers(token, { ...applied, page, per_page: 10 }, organizationId ?? undefined)
  }, [token, organizationId, applied, page, t])

  const setField = (key: keyof EmployerSeekerFilters, value: string) => {
    setDraft((current) => ({ ...current, [key]: value }))
  }

  return (
    <div className="job-seeker-profile employer-workspace">
      <header className="page-header">
        <h1>{t('auth.employer.search')}</h1>
      </header>
      <form
        className="panel"
        onSubmit={(event) => {
          event.preventDefault()
          setPage(1)
          setApplied(draft)
        }}
      >
        <div className="employer-filter-grid">
          <JobSeekerField label={t('auth.employer.filters.jobTitle')} htmlFor="job_title" editing>
            <input id="job_title" value={draft.job_title} onChange={(event) => setField('job_title', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.country')} htmlFor="country" editing size="medium">
            <input id="country" value={draft.country} onChange={(event) => setField('country', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.city')} htmlFor="city" editing size="medium">
            <input id="city" value={draft.city} onChange={(event) => setField('city', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.qualification')} htmlFor="qualification" editing>
            <input id="qualification" value={draft.qualification} onChange={(event) => setField('qualification', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.experienceYears')} htmlFor="years_of_experience" editing size="short">
            <input id="years_of_experience" inputMode="numeric" value={draft.years_of_experience} onChange={(event) => setField('years_of_experience', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.skills')} htmlFor="skills" editing>
            <input id="skills" value={draft.skills} onChange={(event) => setField('skills', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.languages')} htmlFor="languages" editing>
            <input id="languages" value={draft.languages} onChange={(event) => setField('languages', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.workType')} htmlFor="work_type" editing size="medium">
            <input id="work_type" value={draft.work_type} onChange={(event) => setField('work_type', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.expectedSalary')} htmlFor="desired_salary" editing size="short">
            <input id="desired_salary" inputMode="decimal" value={draft.desired_salary} onChange={(event) => setField('desired_salary', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.specialization')} htmlFor="specialization" editing className="js-field-wide">
            <input id="specialization" value={draft.specialization} onChange={(event) => setField('specialization', event.target.value)} />
          </JobSeekerField>
        </div>
        <div className="form-actions">
          <button type="submit" className="js-btn js-btn-primary">{t('auth.employer.filters.search')}</button>
        </div>
      </form>
      {error ? <p className="js-field-error" role="alert">{translateApiError(error) || error}</p> : null}
      <section className="panel">
        <h2>{t('auth.employer.results')}</h2>
        {loading ? <p>{t('common.loading')}</p> : null}
        {!loading && (data?.data.length ?? 0) === 0 ? <p>{t('auth.employer.noResults')}</p> : null}
        <div className="employer-candidate-grid">
          {(data?.data ?? []).map((candidate) => (
            <article key={candidate.id} className="employer-candidate-card">
              <span className={`employer-status${candidate.employment_status === 'hired' ? ' is-hired' : ''}`}>
                {candidate.employment_label}
              </span>
              <h3>{candidate.full_name}</h3>
              <p>{candidate.target_job_title || '—'}</p>
              <p>{[candidate.city, candidate.country].filter(Boolean).join(' / ') || '—'}</p>
              <p>{(candidate.education ?? []).map((row) => row.degree).filter(Boolean).join(' · ') || '—'}</p>
              <p>{candidate.years_of_experience != null ? candidate.years_of_experience : '—'}</p>
              <p>{(candidate.skills ?? []).slice(0, 4).join(' · ')}</p>
              <p>{(candidate.languages ?? []).join(' · ') || '—'}</p>
              <p>{candidate.biography ? candidate.biography.slice(0, 160) : '—'}</p>
              <Link className="js-btn js-btn-primary" to={`/employer/candidates/${candidate.id}`}>{t('auth.employer.viewProfile')}</Link>
            </article>
          ))}
        </div>
        {data && data.last_page > 1 ? (
          <div className="form-actions">
            <button type="button" className="js-btn" disabled={page <= 1} onClick={() => setPage((value) => value - 1)}>{t('common.previous')}</button>
            <button type="button" className="js-btn" disabled={page >= data.last_page} onClick={() => setPage((value) => value + 1)}>{t('common.next')}</button>
          </div>
        ) : null}
      </section>
    </div>
  )
}
