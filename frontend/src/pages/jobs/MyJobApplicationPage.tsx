import { useEffect, useState, type FormEvent, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import {
  getMyJobSeekerProfile,
  upsertMyJobSeekerProfile,
  uploadMyJobSeekerCv,
} from '../../api/jobs'
import { PageHeader } from '../../components/PageHeader'
import { Panel } from '../../components/AppShell'
import { ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'

type ExperienceItem = {
  title?: string
  company?: string
  start_date?: string
  end_date?: string
  current?: boolean
  description?: string
}

type EducationItem = {
  degree?: string
  institution?: string
  country?: string
  year?: number | string
}

function initials(name: string): string {
  return name
    .split(' ')
    .map((part) => part[0])
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

function formatExperienceItem(item: ExperienceItem): string {
  const parts = [item.title, item.company].filter(Boolean)
  const dates = [item.start_date, item.current ? 'الحاضر' : item.end_date].filter(Boolean)
  return [parts.join(' • '), dates.join(' — ')].filter(Boolean).join('\n')
}

function formatEducationItem(item: EducationItem): string {
  const parts = [item.degree, item.institution].filter(Boolean)
  const meta = [item.country, item.year].filter(Boolean)
  return [parts.join(' • '), meta.join(' • ')].filter(Boolean).join('\n')
}

const STATUS_FLOW = [
  { key: 'new', labelKey: 'jobs.statusTimeline.created' },
  { key: 'under_review', labelKey: 'jobs.statusTimeline.submitted' },
  { key: 'qualified', labelKey: 'jobs.statusTimeline.qualified' },
  { key: 'interview', labelKey: 'jobs.statusTimeline.interview' },
  { key: 'accepted', labelKey: 'jobs.statusTimeline.hired' },
  { key: 'hired', labelKey: 'jobs.statusTimeline.hired' },
  { key: 'rejected', labelKey: 'jobs.statusTimeline.rejected' },
]

export function MyJobApplicationPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [viewMode, setViewMode] = useState(true)
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState('')
  const [cvFile, setCvFile] = useState<File | null>(null)
  const [experienceItems, setExperienceItems] = useState<ExperienceItem[]>([])
  const [educationItems, setEducationItems] = useState<EducationItem[]>([])
  const [editingExperienceIndex, setEditingExperienceIndex] = useState<number | null>(null)
  const [editingEducationIndex, setEditingEducationIndex] = useState<number | null>(null)
  const [experienceForm, setExperienceForm] = useState<ExperienceItem>({})
  const [educationForm, setEducationForm] = useState<EducationItem>({})

  const [fullName, setFullName] = useState('')
  const [email, setEmail] = useState('')
  const [phone, setPhone] = useState('')
  const [specialization, setSpecialization] = useState('')
  const [targetJobTitle, setTargetJobTitle] = useState('')
  const [biography, setBiography] = useState('')
  const [country, setCountry] = useState('')
  const [city, setCity] = useState('')
  const [dateOfBirth, setDateOfBirth] = useState('')
  const [nationality, setNationality] = useState('')
  const [address, setAddress] = useState('')
  const [skills, setSkills] = useState('')
  const [languages, setLanguages] = useState('')
  const [desiredSalary, setDesiredSalary] = useState('')
  const [salaryCurrency, setSalaryCurrency] = useState('')
  const [availabilityDate, setAvailabilityDate] = useState('')

  const canAccess = can('jobs.talent.register') || can('jobs.talent.manage')

  const { data: profile, loading, error, reload } = useAsyncData(async () => {
    if (!token || !canAccess) return null
    return getMyJobSeekerProfile(token, organizationId ?? undefined)
  }, [token, organizationId, canAccess])

  useEffect(() => {
    if (!profile) return
    setFullName(profile.full_name ?? '')
    setEmail(profile.email ?? '')
    setPhone(profile.phone ?? '')
    setSpecialization(profile.specialization ?? '')
    setTargetJobTitle(profile.target_job_title ?? '')
    setBiography(profile.biography ?? '')
    setCountry(profile.country ?? '')
    setCity(profile.city ?? '')
    setDateOfBirth(profile.date_of_birth ?? '')
    setNationality(profile.nationality ?? '')
    setAddress(profile.address ?? '')
    setSkills((profile.skills ?? []).join(', '))
    setLanguages((profile.languages ?? []).join(', '))
    setExperienceItems(Array.isArray(profile.experience) ? (profile.experience as ExperienceItem[]) : [])
    setEducationItems(Array.isArray(profile.education) ? (profile.education as EducationItem[]) : [])
    setDesiredSalary(profile.desired_salary ?? '')
    setSalaryCurrency(profile.salary_currency ?? '')
    setAvailabilityDate(profile.availability_date ?? '')
    setViewMode(true)
    setMessage('')
    setCvFile(null)
    setEditingExperienceIndex(null)
    setEditingEducationIndex(null)
    setExperienceForm({})
    setEducationForm({})
  }, [profile])

  if (!canAccess) {
    return <ErrorBanner message={t('jobs.noPermissionTalent')} />
  }

  const handleSave = async () => {
    if (!token) return
    setSaving(true)
    setMessage('')
    try {
      const payload: Record<string, unknown> = {
        full_name: fullName,
        email: email || undefined,
        phone: phone || undefined,
        specialization: specialization || undefined,
        target_job_title: targetJobTitle || undefined,
        biography: biography || undefined,
        country: country || undefined,
        city: city || undefined,
        date_of_birth: dateOfBirth || undefined,
        nationality: nationality || undefined,
        address: address || undefined,
        skills: skills.split(',').map((item) => item.trim()).filter(Boolean),
        languages: languages.split(',').map((item) => item.trim()).filter(Boolean),
        experience: experienceItems,
        education: educationItems,
        desired_salary: desiredSalary ? Number(desiredSalary) : undefined,
        salary_currency: salaryCurrency || undefined,
        availability_date: availabilityDate || undefined,
      }
      await upsertMyJobSeekerProfile(token, payload, organizationId ?? undefined)
      setMessage(t('jobs.applicationSaved'))
      await reload()
      setViewMode(true)
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('jobs.applicationSaveFailed'))
    } finally {
      setSaving(false)
    }
  }

  const handleCancel = () => {
    setViewMode(true)
    setMessage('')
    if (profile) {
      setFullName(profile.full_name ?? '')
      setEmail(profile.email ?? '')
      setPhone(profile.phone ?? '')
      setSpecialization(profile.specialization ?? '')
      setTargetJobTitle(profile.target_job_title ?? '')
      setBiography(profile.biography ?? '')
      setCountry(profile.country ?? '')
      setCity(profile.city ?? '')
      setDateOfBirth(profile.date_of_birth ?? '')
      setNationality(profile.nationality ?? '')
      setAddress(profile.address ?? '')
      setSkills((profile.skills ?? []).join(', '))
      setLanguages((profile.languages ?? []).join(', '))
      setExperienceItems(Array.isArray(profile.experience) ? (profile.experience as ExperienceItem[]) : [])
      setEducationItems(Array.isArray(profile.education) ? (profile.education as EducationItem[]) : [])
      setDesiredSalary(profile.desired_salary ?? '')
      setSalaryCurrency(profile.salary_currency ?? '')
      setAvailabilityDate(profile.availability_date ?? '')
      setCvFile(null)
      setEditingExperienceIndex(null)
      setEditingEducationIndex(null)
      setExperienceForm({})
      setEducationForm({})
    }
  }

  const handleCvUpload = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!token || !cvFile) return
    setMessage('')
    try {
      await uploadMyJobSeekerCv(token, cvFile, organizationId ?? undefined)
      setMessage(t('jobs.cvUploaded'))
      setCvFile(null)
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('jobs.cvUploadFailed'))
    }
  }

  const currentStatusIndex = STATUS_FLOW.findIndex((item) => item.key === profile?.recruitment_status)
  const isComplete = (profile?.completeness_percent ?? 0) >= 80

  const renderExperienceForm = (index: number | null, initial: ExperienceItem): ReactNode => (
    <div className="record-form" style={{ marginTop: 12 }}>
      <label>
        {t('jobs.jobTitle')}
        <input
          dir="auto"
          value={initial.title ?? ''}
          onChange={(e) => setExperienceForm({ ...initial, title: e.target.value })}
        />
      </label>
      <label>
        {t('jobs.employer')}
        <input
          dir="auto"
          value={initial.company ?? ''}
          onChange={(e) => setExperienceForm({ ...initial, company: e.target.value })}
        />
      </label>
      <label>
        {t('jobs.startDate')}
        <input
          type="date"
          dir="auto"
          value={initial.start_date ?? ''}
          onChange={(e) => setExperienceForm({ ...initial, start_date: e.target.value })}
        />
      </label>
      <label>
        {t('jobs.endDate')}
        <input
          type="date"
          dir="auto"
          value={initial.end_date ?? ''}
          disabled={initial.current}
          onChange={(e) => setExperienceForm({ ...initial, end_date: e.target.value })}
        />
      </label>
      <label className="checkbox-row">
        <input
          type="checkbox"
          checked={initial.current ?? false}
          onChange={(e) => setExperienceForm({ ...initial, current: e.target.checked })}
        />
        {t('jobs.currentPosition')}
      </label>
      <label>
        {t('jobs.description')}
        <textarea
          dir="auto"
          rows={3}
          value={initial.description ?? ''}
          onChange={(e) => setExperienceForm({ ...initial, description: e.target.value })}
        />
      </label>
      <div className="form-actions">
        <button
          type="button"
          onClick={() => {
            if (index === null) {
              setExperienceItems([...experienceItems, initial])
            } else {
              const next = [...experienceItems]
              next[index] = initial
              setExperienceItems(next)
            }
            setEditingExperienceIndex(null)
            setExperienceForm({})
          }}
        >
          {t('common.save')}
        </button>
        <button type="button" className="link-button" onClick={() => { setEditingExperienceIndex(null); setExperienceForm({}) }}>
          {t('common.cancel')}
        </button>
      </div>
    </div>
  )

  const renderEducationForm = (index: number | null, initial: EducationItem): ReactNode => (
    <div className="record-form" style={{ marginTop: 12 }}>
      <label>
        {t('jobs.degree')}
        <input
          dir="auto"
          value={initial.degree ?? ''}
          onChange={(e) => setEducationForm({ ...initial, degree: e.target.value })}
        />
      </label>
      <label>
        {t('jobs.university')}
        <input
          dir="auto"
          value={initial.institution ?? ''}
          onChange={(e) => setEducationForm({ ...initial, institution: e.target.value })}
        />
      </label>
      <label>
        {t('jobs.country')}
        <input
          dir="auto"
          value={initial.country ?? ''}
          onChange={(e) => setEducationForm({ ...initial, country: e.target.value })}
        />
      </label>
      <label>
        {t('jobs.graduationYear')}
        <input
          dir="auto"
          type="number"
          min="1950"
          max="2100"
          value={initial.year ?? ''}
          onChange={(e) => setEducationForm({ ...initial, year: e.target.value ? Number(e.target.value) : undefined })}
        />
      </label>
      <div className="form-actions">
        <button
          type="button"
          onClick={() => {
            if (index === null) {
              setEducationItems([...educationItems, initial])
            } else {
              const next = [...educationItems]
              next[index] = initial
              setEducationItems(next)
            }
            setEditingEducationIndex(null)
            setEducationForm({})
          }}
        >
          {t('common.save')}
        </button>
        <button type="button" className="link-button" onClick={() => { setEditingEducationIndex(null); setEducationForm({}) }}>
          {t('common.cancel')}
        </button>
      </div>
    </div>
  )

  return (
    <>
      <PageHeader
        eyebrow={t('nav.ecosystem')}
        title={t('jobs.myApplicationTitle')}
        description={t('jobs.myApplicationDescription')}
        actions={
          <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
            <Link to="/jobs" className="link-button">{t('jobs.backToMarketplace')}</Link>
            {viewMode ? (
              <button type="button" onClick={() => setViewMode(false)}>{t('jobs.viewAndEdit')}</button>
            ) : (
              <button type="button" onClick={() => void handleSave()} disabled={saving}>
                {saving ? t('common.saving') : t('common.save')}
              </button>
            )}
          </div>
        }
      />

      {error && <ErrorBanner message={error} onRetry={reload} />}
      {message && <p className={`notice${message === t('jobs.applicationSaved') ? ' success' : ''}`}>{message}</p>}

      {loading && canAccess ? (
        <p className="loading">{t('jobs.loadingProfile')}</p>
      ) : profile ? (
        <>
          <section className="panel">
            <div className="panel-heading">
              <div>
                <p className="eyebrow">{t('jobs.profile')}</p>
                <h2>{t('jobs.myApplicationTitle')}</h2>
              </div>
            </div>
            <div className="detail-grid" style={{ gridTemplateColumns: 'auto 1fr', gap: 16, alignItems: 'start' }}>
              <div
                style={{
                  width: 72,
                  height: 72,
                  borderRadius: '50%',
                  background: '#6d28d9',
                  color: '#fff',
                  display: 'grid',
                  placeItems: 'center',
                  fontWeight: 800,
                  fontSize: 24,
                  flexShrink: 0,
                }}
              >
                {initials(profile.full_name || '?')}
              </div>
              <div>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, alignItems: 'center' }}>
                  <strong style={{ fontSize: 18 }} dir="auto">{profile.full_name || '—'}</strong>
                  <StatusBadge status={isComplete ? 'completed' : 'pending'} />
                </div>
                <p dir="auto" style={{ margin: '6px 0 0' }}>{profile.target_job_title || profile.specialization || '—'}</p>
                <p dir="auto" style={{ margin: 2, color: '#64748b', fontSize: 13 }}>{[profile.city, profile.country].filter(Boolean).join(', ') || '—'}</p>
                <div style={{ marginTop: 10 }}>
                  <div style={{ height: 8, borderRadius: 999, background: '#e2e8f0', overflow: 'hidden', maxWidth: 320 }}>
                    <div style={{ height: '100%', width: `${profile.completeness_percent ?? 0}%`, background: '#6d28d9' }} />
                  </div>
                  <span style={{ fontSize: 12, color: '#64748b' }}>{profile.completeness_percent ?? 0}% {t('jobs.completeness')}</span>
                </div>
                <div style={{ marginTop: 12, display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                  {viewMode ? (
                    <button type="button" onClick={() => setViewMode(false)}>{t('jobs.viewAndEdit')}</button>
                  ) : (
                    <button type="button" onClick={() => void handleSave()} disabled={saving || !fullName}>
                      {saving ? t('common.saving') : t('common.save')}
                    </button>
                  )}
                  {!viewMode && (
                    <button type="button" className="link-button" onClick={handleCancel}>{t('common.cancel')}</button>
                  )}
                </div>
              </div>
            </div>
          </section>

          <Panel eyebrow={t('jobs.personalInfo')} title={t('jobs.personalInfo')}>
            {viewMode ? (
              <div className="detail-grid">
                <div><span>{t('jobs.fullName')}</span><strong dir="auto">{profile.full_name || '—'}</strong></div>
                <div><span>{t('jobs.email')}</span><strong dir="auto">{profile.email || '—'}</strong></div>
                <div><span>{t('jobs.phoneNumber')}</span><strong dir="auto">{profile.phone || '—'}</strong></div>
                <div><span>{t('jobs.country')}</span><strong dir="auto">{profile.country || '—'}</strong></div>
                <div><span>{t('jobs.city')}</span><strong dir="auto">{profile.city || '—'}</strong></div>
                <div><span>{t('jobs.dateOfBirth')}</span><strong dir="auto">{profile.date_of_birth || '—'}</strong></div>
                <div><span>{t('jobs.nationality')}</span><strong dir="auto">{profile.nationality || '—'}</strong></div>
                <div><span>{t('jobs.address')}</span><strong dir="auto">{profile.address || '—'}</strong></div>
              </div>
            ) : (
              <div className="record-form">
                <label>
                  {t('jobs.fullName')}
                  <input value={fullName} onChange={(e) => setFullName(e.target.value)} required dir="auto" />
                </label>
                <label>
                  {t('jobs.email')}
                  <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.phoneNumber')}
                  <input value={phone} onChange={(e) => setPhone(e.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.country')}
                  <input value={country} onChange={(e) => setCountry(e.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.city')}
                  <input value={city} onChange={(e) => setCity(e.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.dateOfBirth')}
                  <input type="date" value={dateOfBirth} onChange={(e) => setDateOfBirth(e.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.nationality')}
                  <input value={nationality} onChange={(e) => setNationality(e.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.address')}
                  <input value={address} onChange={(e) => setAddress(e.target.value)} dir="auto" />
                </label>
                <div className="form-actions">
                  <button type="button" disabled={saving || !fullName} onClick={() => void handleSave()}>
                    {saving ? t('common.saving') : t('common.save')}
                  </button>
                  <button type="button" className="link-button" onClick={handleCancel}>{t('common.cancel')}</button>
                </div>
              </div>
            )}
          </Panel>

          <Panel eyebrow={t('jobs.professionalProfile')} title={t('jobs.professionalProfile')}>
            {viewMode ? (
              <div style={{ display: 'grid', gap: 14 }}>
                <div>
                  <p style={{ fontSize: 12, color: '#64748b', marginBottom: 4 }}>{t('jobs.professionalSummary')}</p>
                  <p dir="auto" style={{ whiteSpace: 'pre-wrap' }}>{profile.biography || '—'}</p>
                </div>
                <div className="detail-grid">
                  <div><span>{t('jobs.targetJobTitle')}</span><strong dir="auto">{profile.target_job_title || '—'}</strong></div>
                  <div><span>{t('jobs.specialization')}</span><strong dir="auto">{profile.specialization || '—'}</strong></div>
                  <div><span>{t('jobs.skills')}</span><strong dir="auto">{(profile.skills ?? []).join(', ') || '—'}</strong></div>
                  <div><span>{t('jobs.languages')}</span><strong dir="auto">{(profile.languages ?? []).join(', ') || '—'}</strong></div>
                </div>
              </div>
            ) : (
              <div className="record-form">
                <label>
                  {t('jobs.targetJobTitle')}
                  <input value={targetJobTitle} onChange={(e) => setTargetJobTitle(e.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.professionalSummary')}
                  <textarea value={biography} onChange={(e) => setBiography(e.target.value)} rows={6} dir="auto" />
                </label>
                <label>
                  {t('jobs.specialization')}
                  <input value={specialization} onChange={(e) => setSpecialization(e.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.skills')}
                  <input value={skills} onChange={(e) => setSkills(e.target.value)} placeholder={t('jobs.skillsPlaceholder')} dir="auto" />
                </label>
                <label>
                  {t('jobs.languages')}
                  <input value={languages} onChange={(e) => setLanguages(e.target.value)} placeholder={t('jobs.languages')} dir="auto" />
                </label>
                <div className="form-actions">
                  <button type="button" disabled={saving || !fullName} onClick={() => void handleSave()}>
                    {saving ? t('common.saving') : t('common.save')}
                  </button>
                  <button type="button" className="link-button" onClick={handleCancel}>{t('common.cancel')}</button>
                </div>
              </div>
            )}
          </Panel>

          <Panel eyebrow={t('jobs.education')} title={t('jobs.education')}>
            {viewMode ? (
              educationItems.length === 0 ? (
                <p className="muted">{t('jobs.emptyData')}</p>
              ) : (
                <div style={{ display: 'grid', gap: 12 }}>
                  {educationItems.map((item, index) => (
                    <div key={index} style={{ padding: 12, border: '1px solid #e5e7eb', borderRadius: 10 }}>
                      <strong dir="auto">{item.degree || '—'}</strong>
                      <p dir="auto" style={{ margin: '4px 0 0', fontSize: 13 }}>{formatEducationItem(item) || '—'}</p>
                    </div>
                  ))}
                </div>
              )
            ) : (
              <>
                <div style={{ display: 'grid', gap: 12 }}>
                  {educationItems.map((item, index) => (
                    <div key={index} style={{ padding: 12, border: '1px solid #e5e7eb', borderRadius: 10 }}>
                      <strong dir="auto">{item.degree || '—'}</strong>
                      <p dir="auto" style={{ margin: '4px 0 0', fontSize: 13 }}>{formatEducationItem(item) || '—'}</p>
                      <div style={{ marginTop: 8, display: 'flex', gap: 8 }}>
                        <button type="button" onClick={() => { setEditingEducationIndex(index); setEducationForm(item) }}>{t('common.edit')}</button>
                        <button type="button" style={{ background: '#dc2626' }} onClick={() => setEducationItems(educationItems.filter((_, i) => i !== index))}>{t('common.delete')}</button>
                      </div>
                      {editingEducationIndex === index && renderEducationForm(index, educationForm)}
                    </div>
                  ))}
                </div>
                {editingEducationIndex === null && (
                  <button type="button" style={{ marginTop: 12 }} onClick={() => { setEditingEducationIndex(-1); setEducationForm({}) }}>
                    {t('jobs.addEducation')}
                  </button>
                )}
                {editingEducationIndex === -1 && renderEducationForm(null, educationForm)}
                <div className="form-actions" style={{ marginTop: 18 }}>
                  <button type="button" disabled={saving || !fullName} onClick={() => void handleSave()}>
                    {saving ? t('common.saving') : t('common.save')}
                  </button>
                  <button type="button" className="link-button" onClick={handleCancel}>{t('common.cancel')}</button>
                </div>
              </>
            )}
          </Panel>

          <Panel eyebrow={t('jobs.workExperience')} title={t('jobs.workExperience')}>
            {viewMode ? (
              experienceItems.length === 0 ? (
                <p className="muted">{t('jobs.emptyData')}</p>
              ) : (
                <div style={{ display: 'grid', gap: 12 }}>
                  {experienceItems.map((item, index) => (
                    <div key={index} style={{ padding: 12, border: '1px solid #e5e7eb', borderRadius: 10 }}>
                      <strong dir="auto">{item.title || '—'}</strong>
                      <p dir="auto" style={{ margin: '4px 0 0', fontSize: 13 }}>{formatExperienceItem(item) || '—'}</p>
                    </div>
                  ))}
                </div>
              )
            ) : (
              <>
                <div style={{ display: 'grid', gap: 12 }}>
                  {experienceItems.map((item, index) => (
                    <div key={index} style={{ padding: 12, border: '1px solid #e5e7eb', borderRadius: 10 }}>
                      <strong dir="auto">{item.title || '—'}</strong>
                      <p dir="auto" style={{ margin: '4px 0 0', fontSize: 13 }}>{formatExperienceItem(item) || '—'}</p>
                      <div style={{ marginTop: 8, display: 'flex', gap: 8 }}>
                        <button type="button" onClick={() => { setEditingExperienceIndex(index); setExperienceForm(item) }}>{t('common.edit')}</button>
                        <button type="button" style={{ background: '#dc2626' }} onClick={() => setExperienceItems(experienceItems.filter((_, i) => i !== index))}>{t('common.delete')}</button>
                      </div>
                      {editingExperienceIndex === index && renderExperienceForm(index, experienceForm)}
                    </div>
                  ))}
                </div>
                {editingExperienceIndex === null && (
                  <button type="button" style={{ marginTop: 12 }} onClick={() => { setEditingExperienceIndex(-1); setExperienceForm({}) }}>
                    {t('jobs.addExperience')}
                  </button>
                )}
                {editingExperienceIndex === -1 && renderExperienceForm(null, experienceForm)}
                <div className="form-actions" style={{ marginTop: 18 }}>
                  <button type="button" disabled={saving || !fullName} onClick={() => void handleSave()}>
                    {saving ? t('common.saving') : t('common.save')}
                  </button>
                  <button type="button" className="link-button" onClick={handleCancel}>{t('common.cancel')}</button>
                </div>
              </>
            )}
          </Panel>

          <Panel eyebrow={t('jobs.cvSection')} title={t('jobs.cvSection')}>
            {viewMode ? (
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12, alignItems: 'center' }}>
                <span dir="auto">{profile.cv_path ? profile.cv_path.split('/').pop() : t('jobs.noCvYet')}</span>
                {profile.cv_path && (
                  <a href={profile.cv_path} target="_blank" rel="noreferrer" className="link-button">{t('jobs.viewCv')}</a>
                )}
              </div>
            ) : (
              <form className="record-form" onSubmit={handleCvUpload}>
                <label>
                  {t('jobs.selectCv')}
                  <input type="file" accept=".pdf,.doc,.docx,.txt" onChange={(e) => setCvFile(e.target.files?.[0] ?? null)} />
                </label>
                <div className="form-actions">
                  <button type="submit" disabled={!cvFile}>{t('jobs.replaceCv')}</button>
                </div>
              </form>
            )}
          </Panel>

          <Panel eyebrow={t('jobs.recruitmentStatus')} title={t('jobs.applicationStatus')}>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
              {STATUS_FLOW.map((status) => {
                const isActive = status.key === profile.recruitment_status
                const isPast = currentStatusIndex > STATUS_FLOW.findIndex((s) => s.key === status.key)
                return (
                  <div key={status.key} style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                    <div
                      style={{
                        width: 28,
                        height: 28,
                        borderRadius: '50%',
                        border: `2px solid ${isActive ? '#6d28d9' : isPast ? '#059669' : '#cbd5e1'}`,
                        background: isActive ? '#6d28d9' : isPast ? '#059669' : '#fff',
                        color: isActive || isPast ? '#fff' : '#64748b',
                        display: 'grid',
                        placeItems: 'center',
                        fontSize: 12,
                        fontWeight: 800,
                        flexShrink: 0,
                      }}
                    >
                      {isActive ? '✓' : isPast ? '✓' : ''}
                    </div>
                    <div>
                      <p style={{ fontWeight: isActive ? 800 : 400, color: isActive ? '#6d28d9' : '#334155', margin: 0 }}>
                        {t(status.labelKey)}
                      </p>
                    </div>
                  </div>
                )
              })}
            </div>
          </Panel>
        </>
      ) : null}
    </>
  )
}
