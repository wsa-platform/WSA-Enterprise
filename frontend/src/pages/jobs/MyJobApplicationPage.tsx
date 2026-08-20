import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import {
  getMyJobSeekerProfile,
  upsertMyJobSeekerProfile,
} from '../../api/jobs'
import { PageHeader } from '../../components/PageHeader'
import { Panel } from '../../components/AppShell'
import { ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'

function parseJsonField(value: string): unknown[] | undefined {
  const trimmed = value.trim()
  if (!trimmed) return undefined
  try {
    const parsed = JSON.parse(trimmed)
    return Array.isArray(parsed) ? parsed : undefined
  } catch {
    return undefined
  }
}

function formatJsonField(value: unknown[] | null | undefined): string {
  if (!value || value.length === 0) return '[]'
  return JSON.stringify(value, null, 2)
}

export function MyJobApplicationPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [viewMode, setViewMode] = useState(true)
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState('')

  const [fullName, setFullName] = useState('')
  const [email, setEmail] = useState('')
  const [phone, setPhone] = useState('')
  const [specialization, setSpecialization] = useState('')
  const [biography, setBiography] = useState('')
  const [country, setCountry] = useState('')
  const [region, setRegion] = useState('')
  const [city, setCity] = useState('')
  const [skills, setSkills] = useState('')
  const [languages, setLanguages] = useState('')
  const [experience, setExperience] = useState('')
  const [education, setEducation] = useState('')
  const [certifications, setCertifications] = useState('')
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
    setBiography(profile.biography ?? '')
    setCountry(profile.country ?? '')
    setRegion(profile.region ?? '')
    setCity(profile.city ?? '')
    setSkills((profile.skills ?? []).join(', '))
    setLanguages((profile.languages ?? []).join(', '))
    setExperience(formatJsonField(profile.experience))
    setEducation(formatJsonField(profile.education))
    setCertifications(formatJsonField(profile.certifications))
    setDesiredSalary(profile.desired_salary ?? '')
    setSalaryCurrency(profile.salary_currency ?? '')
    setAvailabilityDate(profile.availability_date ?? '')
    setViewMode(true)
    setMessage('')
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
        biography: biography || undefined,
        country: country || undefined,
        region: region || undefined,
        city: city || undefined,
        skills: skills.split(',').map((item) => item.trim()).filter(Boolean),
        languages: languages.split(',').map((item) => item.trim()).filter(Boolean),
        experience: parseJsonField(experience),
        education: parseJsonField(education),
        certifications: parseJsonField(certifications),
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
      setBiography(profile.biography ?? '')
      setCountry(profile.country ?? '')
      setRegion(profile.region ?? '')
      setCity(profile.city ?? '')
      setSkills((profile.skills ?? []).join(', '))
      setLanguages((profile.languages ?? []).join(', '))
      setExperience(formatJsonField(profile.experience))
      setEducation(formatJsonField(profile.education))
      setCertifications(formatJsonField(profile.certifications))
      setDesiredSalary(profile.desired_salary ?? '')
      setSalaryCurrency(profile.salary_currency ?? '')
      setAvailabilityDate(profile.availability_date ?? '')
    }
  }

  const statusLabel = profile?.recruitment_status
    ? t(`jobs.status.${profile.recruitment_status}`)
    : '—'

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
              <button type="button" onClick={() => void handleSave()} disabled={saving || !fullName}>
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
          <Panel eyebrow={t('common.profile')} title={t('jobs.registration')}>
            {viewMode ? (
              <div className="detail-grid">
                <div><span>{t('jobs.fullName')}</span><strong dir="auto">{profile.full_name || '—'}</strong></div>
                <div><span>{t('jobs.email')}</span><strong dir="auto">{profile.email || '—'}</strong></div>
                <div><span>{t('jobs.phoneNumber')}</span><strong dir="auto">{profile.phone || '—'}</strong></div>
                <div><span>{t('jobs.specialization')}</span><strong dir="auto">{profile.specialization || '—'}</strong></div>
                <div><span>{t('jobs.country')}</span><strong dir="auto">{profile.country || '—'}</strong></div>
                <div><span>{t('jobs.region')}</span><strong dir="auto">{profile.region || '—'}</strong></div>
                <div><span>{t('jobs.city')}</span><strong dir="auto">{profile.city || '—'}</strong></div>
                <div><span>{t('jobs.recruitmentStatus')}</span><strong dir="auto">{statusLabel}</strong></div>
                <div><span>{t('jobs.completeness')}</span><strong>{profile.completeness_percent ?? 0}%</strong></div>
                <div><span>{t('jobs.desiredSalary')}</span><strong dir="auto">{profile.desired_salary ? `${profile.desired_salary} ${profile.salary_currency ?? ''}` : '—'}</strong></div>
                <div><span>{t('jobs.availabilityDate')}</span><strong dir="auto">{profile.availability_date || '—'}</strong></div>
                <div><span>{t('jobs.cvPath')}</span><strong dir="auto">{profile.cv_path ? <a href={profile.cv_path} target="_blank" rel="noreferrer">{profile.cv_path.split('/').pop()}</a> : '—'}</strong></div>
              </div>
            ) : (
              <div className="record-form">
                <label>
                  {t('jobs.fullName')}
                  <input value={fullName} onChange={(event) => setFullName(event.target.value)} required dir="auto" />
                </label>
                <label>
                  {t('jobs.email')}
                  <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.phoneNumber')}
                  <input value={phone} onChange={(event) => setPhone(event.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.specialization')}
                  <input value={specialization} onChange={(event) => setSpecialization(event.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.biography')}
                  <textarea value={biography} onChange={(event) => setBiography(event.target.value)} rows={4} dir="auto" />
                </label>
                <label>
                  {t('jobs.country')}
                  <input value={country} onChange={(event) => setCountry(event.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.region')}
                  <input value={region} onChange={(event) => setRegion(event.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.city')}
                  <input value={city} onChange={(event) => setCity(event.target.value)} dir="auto" />
                </label>
                <label>
                  {t('jobs.skills')}
                  <input value={skills} onChange={(event) => setSkills(event.target.value)} placeholder={t('jobs.skillsPlaceholder')} dir="auto" />
                </label>
                <label>
                  {t('jobs.languages')}
                  <input value={languages} onChange={(event) => setLanguages(event.target.value)} placeholder={t('jobs.languages')} dir="auto" />
                </label>
                <label>
                  {t('jobs.experience')}
                  <textarea value={experience} onChange={(event) => setExperience(event.target.value)} rows={5} dir="auto" placeholder={t('jobs.jsonPlaceholder')} />
                </label>
                <label>
                  {t('jobs.education')}
                  <textarea value={education} onChange={(event) => setEducation(event.target.value)} rows={5} dir="auto" placeholder={t('jobs.jsonPlaceholder')} />
                </label>
                <label>
                  {t('jobs.certifications')}
                  <textarea value={certifications} onChange={(event) => setCertifications(event.target.value)} rows={5} dir="auto" placeholder={t('jobs.jsonPlaceholder')} />
                </label>
                <label>
                  {t('jobs.desiredSalary')}
                  <input type="number" value={desiredSalary} onChange={(event) => setDesiredSalary(event.target.value)} dir="auto" min="0" step="0.01" />
                </label>
                <label>
                  {t('jobs.salaryCurrency')}
                  <input value={salaryCurrency} onChange={(event) => setSalaryCurrency(event.target.value)} dir="auto" maxLength={3} placeholder="SAR" />
                </label>
                <label>
                  {t('jobs.availabilityDate')}
                  <input type="date" value={availabilityDate} onChange={(event) => setAvailabilityDate(event.target.value)} dir="auto" />
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

          {viewMode && (
            <Panel eyebrow={t('jobs.recruitmentStatus')} title={statusLabel}>
              <div className="detail-grid">
                <div><span>{t('jobs.completeness')}</span><strong>{profile.completeness_percent ?? 0}%</strong></div>
                <div><span>{t('jobs.availabilityDate')}</span><strong dir="auto">{profile.availability_date || '—'}</strong></div>
                <div><span>{t('jobs.cvPath')}</span><strong dir="auto">{profile.cv_path ? <a href={profile.cv_path} target="_blank" rel="noreferrer">{profile.cv_path.split('/').pop()}</a> : '—'}</strong></div>
                <div><span>{t('jobs.skills')}</span><strong dir="auto">{(profile.skills ?? []).join(', ') || '—'}</strong></div>
                <div><span>{t('jobs.languages')}</span><strong dir="auto">{(profile.languages ?? []).join(', ') || '—'}</strong></div>
              </div>
            </Panel>
          )}
        </>
      ) : null}
    </>
  )
}
