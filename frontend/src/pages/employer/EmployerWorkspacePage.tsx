import { useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { searchEmployerSeekers, type EmployerSeekerFilters } from '../../api/jobs'
import { useAuth } from '../../context/AuthContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { JobSeekerField } from '../jobs/JobSeekerField'
import { EmployerSeekerPhoto } from './EmployerSeekerPhoto'
import {
  EMPTY_EMPLOYER_FILTERS,
  compactEmployerSeekerFilters,
  employerSearchView,
  sanitizeEmployerSeeker,
  shouldFocusEmployerSearchResults,
} from './employerWorkspace'
import '../jobs/jobSeekerProfile.css'
import './employerWorkspace.css'

export function EmployerWorkspacePage() {
  const { t } = useTranslation()
  const { token, organizationId, user } = useAuth()
  const [draft, setDraft] = useState<EmployerSeekerFilters>(EMPTY_EMPLOYER_FILTERS)
  const [applied, setApplied] = useState<EmployerSeekerFilters>(EMPTY_EMPLOYER_FILTERS)
  const [page, setPage] = useState(1)
  const [searchSubmitted, setSearchSubmitted] = useState(false)
  const resultsHeadingRef = useRef<HTMLHeadingElement>(null)
  const dataAtSubmitRef = useRef<unknown>(undefined)

  const { data, loading, error } = useAsyncData(async () => {
    if (!token) throw new Error(t('errors.notAuthenticated'))
    const payload = await searchEmployerSeekers(token, compactEmployerSeekerFilters({ ...applied, page, per_page: 10 }), organizationId ?? undefined)
    return { ...payload, data: payload.data.map(sanitizeEmployerSeeker) }
  }, [token, organizationId, applied, page, t])

  const view = employerSearchView({ loading, error, count: data?.data.length ?? 0 })
  const resultsReady = data != null && data !== dataAtSubmitRef.current

  useEffect(() => {
    if (!shouldFocusEmployerSearchResults({ view, searchSubmitted, resultsReady })) return
    const heading = resultsHeadingRef.current
    if (!heading) return
    const frame = window.requestAnimationFrame(() => {
      heading.scrollIntoView({ behavior: 'smooth', block: 'start' })
      heading.focus({ preventScroll: true })
    })
    return () => window.cancelAnimationFrame(frame)
  }, [view, searchSubmitted, resultsReady, data])

  const setField = (key: keyof EmployerSeekerFilters, value: string) => {
    setDraft((current) => ({ ...current, [key]: value }))
  }

  const resetFilters = () => {
    dataAtSubmitRef.current = data
    setDraft(EMPTY_EMPLOYER_FILTERS)
    setApplied(EMPTY_EMPLOYER_FILTERS)
    setPage(1)
    setSearchSubmitted(false)
  }

  return (
    <div className="job-seeker-profile employer-workspace" data-testid="employer-workspace">
      <header className="page-header">
        <div>
          <p className="eyebrow">{t('auth.employer.navLabel')}</p>
          <h1>{t('auth.employer.title')}</h1>
          <p className="page-description">{t('auth.employer.workspaceIntro')}</p>
        </div>
      </header>

      <section className="panel">
        <h2>{t('auth.employer.identity')}</h2>
        <div className="employer-identity">
          <JobSeekerField label={t('common.name')} value={user?.name ?? '—'} />
          <JobSeekerField label={t('common.email')} value={user?.email ?? '—'} size="medium" />
          <JobSeekerField label={t('common.status')} value={t('auth.employer.accountActive')} size="short" />
        </div>
      </section>

      <form
        className="panel"
        onSubmit={(event) => {
          event.preventDefault()
          dataAtSubmitRef.current = data
          setPage(1)
          setSearchSubmitted(true)
          setApplied(compactEmployerSeekerFilters(draft))
        }}
      >
        <h2>{t('auth.employer.searchHeading')}</h2>
        <div className="employer-filter-grid">
          <JobSeekerField label={t('auth.employer.filters.jobTitle')} htmlFor="employer-job-title" editing>
            <input id="employer-job-title" value={draft.job_title ?? ''} onChange={(event) => setField('job_title', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.country')} htmlFor="employer-country" editing size="medium">
            <input id="employer-country" value={draft.country ?? ''} onChange={(event) => setField('country', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.city')} htmlFor="employer-city" editing size="medium">
            <input id="employer-city" value={draft.city ?? ''} onChange={(event) => setField('city', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.qualification')} htmlFor="employer-qualification" editing>
            <input id="employer-qualification" value={draft.qualification ?? ''} onChange={(event) => setField('qualification', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.experienceYears')} htmlFor="employer-experience" editing size="short">
            <input id="employer-experience" inputMode="numeric" value={draft.years_of_experience ?? ''} onChange={(event) => setField('years_of_experience', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.skills')} htmlFor="employer-skills" editing>
            <input id="employer-skills" value={draft.skills ?? ''} onChange={(event) => setField('skills', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.languages')} htmlFor="employer-languages" editing>
            <input id="employer-languages" value={draft.languages ?? ''} onChange={(event) => setField('languages', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.workType')} htmlFor="employer-work-type" editing size="medium">
            <input id="employer-work-type" value={draft.work_type ?? ''} onChange={(event) => setField('work_type', event.target.value)} />
          </JobSeekerField>
          <JobSeekerField label={t('auth.employer.filters.specialization')} htmlFor="employer-specialization" editing size="full">
            <input id="employer-specialization" value={draft.specialization ?? ''} onChange={(event) => setField('specialization', event.target.value)} />
          </JobSeekerField>
        </div>
        <div className="form-actions">
          <button type="submit" className="js-btn js-btn-primary">{t('auth.employer.filters.search')}</button>
          <button type="button" className="js-btn js-btn-secondary" onClick={resetFilters}>{t('auth.employer.resetFilters')}</button>
        </div>
      </form>

      {view === 'error' ? <p className="js-field-error" role="alert">{error}</p> : null}

      <section className="panel" data-testid="employer-results">
        <h2 ref={resultsHeadingRef} tabIndex={-1}>
          {t('auth.employer.results')}
        </h2>
        {view === 'loading' ? <p>{t('common.loading')}</p> : null}
        {view === 'empty' ? <p data-testid="employer-empty">{t('auth.employer.noResults')}</p> : null}
        <div className="employer-candidate-grid">
          {(data?.data ?? []).map((candidate) => (
            <article key={candidate.id} className="employer-candidate-card" data-testid="employer-candidate-card">
              <div className="employer-card-head">
                <EmployerSeekerPhoto seekerId={candidate.id} hasPhoto={candidate.has_photo} />
                <div>
                  <span className={`employer-status${candidate.employment_status === 'hired' ? ' is-hired' : ''}`}>
                    {candidate.employment_label}
                  </span>
                  <h3>{candidate.full_name}</h3>
                </div>
              </div>
              <JobSeekerField label={t('auth.employer.filters.jobTitle')} value={candidate.target_job_title ?? '—'} />
              <JobSeekerField label={t('auth.employer.filters.city')} value={[candidate.city, candidate.country].filter(Boolean).join(' / ') || '—'} />
              <JobSeekerField label={t('auth.employer.education')} value={(candidate.education ?? []).map((row) => row.degree).filter(Boolean).join(' · ') || '—'} />
              <JobSeekerField label={t('auth.employer.filters.experienceYears')} value={candidate.years_of_experience != null ? String(candidate.years_of_experience) : '—'} size="short" />
              <JobSeekerField label={t('auth.employer.skills')} value={(candidate.skills ?? []).slice(0, 4).join(' · ') || '—'} />
              <JobSeekerField label={t('auth.employer.languages')} value={(candidate.languages ?? []).join(' · ') || '—'} />
              <JobSeekerField label={t('auth.employer.summary')} value={candidate.biography ? candidate.biography.slice(0, 160) : '—'} size="full" />
              {candidate.has_cv ? <JobSeekerField label={t('auth.employer.cvAvailable')} value={t('auth.employer.cvPolicy')} /> : null}
              <Link className="js-btn js-btn-primary" to={`/employer/candidates/${candidate.id}`}>{t('auth.employer.viewProfile')}</Link>
            </article>
          ))}
        </div>
        {data && data.last_page > 1 ? (
          <div className="form-actions">
            <button type="button" className="js-btn" disabled={page <= 1} onClick={() => setPage((value) => value - 1)}>{t('common.previous')}</button>
            <span>{t('common.pageOf', { total: data.total, page: data.current_page, lastPage: data.last_page })}</span>
            <button type="button" className="js-btn" disabled={page >= data.last_page} onClick={() => setPage((value) => value + 1)}>{t('common.next')}</button>
          </div>
        ) : null}
      </section>
    </div>
  )
}
