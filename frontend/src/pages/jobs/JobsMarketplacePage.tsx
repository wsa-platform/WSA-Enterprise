import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  downloadUnlockedCv,
  getJobCandidate,
  markCandidateHired,
  payContactRequest,
  requestCandidateContact,
  searchJobCandidates,
  type JobTalentProfile,
} from '../../api/jobs'
import { ApiError } from '../../api/client'
import { DataTable, PaginationBar } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'
import i18n from '../../i18n/config'
import { authorizedUnlockFromPay } from './candidateProfile'

export function JobsMarketplacePage() {
  const { t } = useTranslation()
  const { token, organizationId, user } = useAuth()
  const { can } = usePermissions()
  const [specialization, setSpecialization] = useState('')
  const [country, setCountry] = useState('')
  const [skill, setSkill] = useState('')
  const [page, setPage] = useState(1)
  const [message, setMessage] = useState('')
  const [selected, setSelected] = useState<JobTalentProfile | null>(null)
  const [contactName, setContactName] = useState(user?.name ?? '')
  const [contactEmail, setContactEmail] = useState(user?.email ?? '')
  const [contactNotes, setContactNotes] = useState('')
  const [jobReference, setJobReference] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [hireSubmitting, setHireSubmitting] = useState(false)
  const [unlocked, setUnlocked] = useState<{
    requestId: number
    candidateEmail?: string | null
    candidatePhone?: string | null
    paymentStatus?: string
    hired?: boolean
  } | null>(null)

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return searchJobCandidates(token, {
      specialization: specialization || undefined,
      country: country || undefined,
      skill: skill || undefined,
      page,
      per_page: 10,
    }, organizationId ?? undefined)
  }, [token, organizationId, specialization, country, skill, page])

  if (!can('jobs.view')) {
    return <ErrorBanner message={t('jobs.noPermissionView')} />
  }

  const rows = data?.data ?? []
  const canManage = can('jobs.manage')

  const handleSearch = () => {
    setPage(1)
    void reload()
  }

  const openContact = async (candidate: JobTalentProfile) => {
    if (!token) return
    setMessage('')
    try {
      const detail = await getJobCandidate(token, candidate.id, organizationId ?? undefined)
      setSelected(detail)
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('jobs.loadCandidateFailed'))
    }
  }

  const submitContact = async () => {
    if (!token || !selected) return
    setSubmitting(true)
    setMessage('')
    setUnlocked(null)
    try {
      const contactRequest = await requestCandidateContact(token, selected.id, {
        employer_contact: {
          name: contactName,
          email: contactEmail,
        },
        job_reference: jobReference || undefined,
        notes: contactNotes || undefined,
        idempotency_key: `web-${Date.now()}`,
      }, organizationId ?? undefined)
      setMessage(t('jobs.contactRequested', { id: contactRequest.id }))

      if (canManage) {
        const paid = await payContactRequest(token, contactRequest.id, `pay-${contactRequest.id}`, organizationId ?? undefined)
        const unlockedContact = authorizedUnlockFromPay(contactRequest.id, paid)
        if (unlockedContact) {
          setUnlocked(unlockedContact)
          setMessage(t('jobs.contactUnlocked'))
        } else {
          setMessage(t('jobs.paymentPending'))
        }
      }
      setSelected(null)
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('jobs.contactFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  const submitHire = async () => {
    if (!token || !unlocked) return
    setHireSubmitting(true)
    setMessage('')
    try {
      await markCandidateHired(token, unlocked.requestId, organizationId ?? undefined)
      setUnlocked({ ...unlocked, hired: true })
      setMessage(t('jobs.hireSuccess'))
      await reload()
    } catch (requestError) {
      if (requestError instanceof ApiError && requestError.isConflict) {
        setMessage(t('jobs.alreadyHired'))
      } else {
        setMessage(translateApiError(requestError) || t('jobs.hireFailed'))
      }
    } finally {
      setHireSubmitting(false)
    }
  }

  const handleDownloadUnlockedCv = async () => {
    if (!token || !unlocked) return
    setMessage('')
    try {
      const blob = await downloadUnlockedCv(token, unlocked.requestId, organizationId ?? undefined)
      const url = URL.createObjectURL(blob)
      window.open(url, '_blank', 'noopener,noreferrer')
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('jobs.cvDownloadFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('nav.ecosystem')}
      title={t('jobs.marketplaceTitle')}
      description={t('jobs.marketplaceDescription')}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.search')}</p><h2>{t('jobs.searchCandidates')}</h2></div></div>
      <div className="record-form">
        <label>
          {t('jobs.specialization')}
          <input value={specialization} onChange={(event) => setSpecialization(event.target.value)} dir="auto" />
        </label>
        <label>
          {t('jobs.country')}
          <input value={country} onChange={(event) => setCountry(event.target.value)} dir="auto" />
        </label>
        <label>
          {t('jobs.skill')}
          <input value={skill} onChange={(event) => setSkill(event.target.value)} dir="auto" />
        </label>
        <button type="button" onClick={handleSearch}>{t('common.search')}</button>
      </div>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.directory')}</p><h2>{t('jobs.candidates')}</h2></div></div>
      {loading ? <p className="loading">{t('jobs.loadingCandidates')}</p> : rows.length === 0 ? (
        <EmptyState title={t('jobs.noCandidates')} description={t('jobs.noCandidatesDescription')} />
      ) : (
        <>
          <DataTable
            rows={rows}
            rowKey={(row) => row.id}
            columns={[
              { key: 'name', header: t('common.name'), render: (row) => row.professional_name },
              { key: 'specialization', header: t('jobs.specialization'), render: (row) => row.specialization ?? '—' },
              { key: 'location', header: t('jobs.location'), render: (row) => [row.city, row.country].filter(Boolean).join(', ') || '—' },
              { key: 'status', header: t('common.status'), render: (row) => t(`jobs.employmentStatus.${row.employment_status ?? 'available'}`) },
              {
                key: 'actions',
                header: t('common.actions'),
                render: (row) => canManage
                  ? <button type="button" className="link-button inline" onClick={() => void openContact(row)}>{t('jobs.requestContact')}</button>
                  : '—',
              },
            ]}
          />
          {data && (
            <PaginationBar
              page={data.current_page}
              lastPage={data.last_page}
              total={data.total}
              onPageChange={setPage}
            />
          )}
        </>
      )}
    </section>

    {selected && canManage && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('jobs.contactFlow')}</p><h2>{selected.professional_name}</h2></div></div>
        <p className="muted" dir="auto">{selected.biography ?? t('jobs.noBiography')}</p>
        <div className="record-form">
          <label>
            {t('jobs.employerName')}
            <input value={contactName} onChange={(event) => setContactName(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('jobs.employerEmail')}
            <input type="email" value={contactEmail} onChange={(event) => setContactEmail(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('jobs.jobReference')}
            <input value={jobReference} onChange={(event) => setJobReference(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('jobs.notes')}
            <textarea value={contactNotes} onChange={(event) => setContactNotes(event.target.value)} rows={3} dir="auto" />
          </label>
          <div className="form-actions">
            <button type="button" disabled={submitting} onClick={() => void submitContact()}>
              {submitting ? t('jobs.submittingContact') : t('jobs.submitContact')}
            </button>
            <button type="button" className="link-button" onClick={() => setSelected(null)}>{t('common.cancel')}</button>
          </div>
        </div>
      </section>
    )}

    {unlocked && canManage && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('jobs.contactFlow')}</p><h2>{t('jobs.contactUnlocked')}</h2></div></div>
        <p className="muted">{t('jobs.paymentCompleted')}</p>
        <div className="detail-grid">
          <div><span>{t('jobs.contactEmail')}</span><strong dir="auto">{unlocked.candidateEmail || '—'}</strong></div>
          <div><span>{t('jobs.contactPhone')}</span><strong dir="auto">{unlocked.candidatePhone || '—'}</strong></div>
        </div>
        <div className="form-actions" style={{ marginTop: 16 }}>
          <button type="button" onClick={() => void handleDownloadUnlockedCv()}>{t('jobs.downloadCv')}</button>
          <button type="button" disabled={hireSubmitting || unlocked.hired} onClick={() => void submitHire()}>
            {unlocked.hired ? t('jobs.hired') : hireSubmitting ? t('jobs.hiring') : t('jobs.hireConfirm')}
          </button>
        </div>
      </section>
    )}
  </>
}
