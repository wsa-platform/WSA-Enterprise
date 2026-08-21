import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import {
  downloadMyTalentCv,
  getMyTalentProfile,
  parseTalentCv,
  uploadTalentCv,
  upsertMyTalentProfile,
} from '../../api/jobs'
import { PageHeader } from '../../components/PageHeader'
import { ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'

export function TalentProfilePage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [message, setMessage] = useState('')
  const [saving, setSaving] = useState(false)
  const [professionalName, setProfessionalName] = useState('')
  const [specialization, setSpecialization] = useState('')
  const [biography, setBiography] = useState('')
  const [country, setCountry] = useState('')
  const [city, setCity] = useState('')
  const [skills, setSkills] = useState('')
  const [contactEmail, setContactEmail] = useState('')
  const [contactPhone, setContactPhone] = useState('')
  const [isPublic, setIsPublic] = useState(true)

  const canRegister = can('jobs.talent.register')
  const canManage = can('jobs.talent.manage')

  const { data: profile, loading, error, reload } = useAsyncData(async () => {
    if (!token || !canManage) return null
    return getMyTalentProfile(token, organizationId ?? undefined)
  }, [token, organizationId, canManage])

  useEffect(() => {
    if (!profile) return
    setProfessionalName(profile.professional_name ?? '')
    setSpecialization(profile.specialization ?? '')
    setBiography(profile.biography ?? '')
    setCountry(profile.country ?? '')
    setCity(profile.city ?? '')
    setSkills((profile.skills ?? []).join(', '))
    setContactEmail(profile.contact?.email ?? '')
    setContactPhone(profile.contact?.phone ?? '')
    setIsPublic(profile.is_public ?? true)
  }, [profile])

  if (!canRegister && !canManage) {
    return <ErrorBanner message={t('jobs.noPermissionTalent')} />
  }

  const handleSave = async () => {
    if (!token) return
    setSaving(true)
    setMessage('')
    try {
      await upsertMyTalentProfile(token, {
        professional_name: professionalName,
        specialization: specialization || undefined,
        biography: biography || undefined,
        country: country || undefined,
        city: city || undefined,
        skills: skills.split(',').map((item) => item.trim()).filter(Boolean),
        is_public: isPublic,
        contact: {
          email: contactEmail || undefined,
          phone: contactPhone || undefined,
        },
      }, organizationId ?? undefined)
      setMessage(t('jobs.profileSaved'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('jobs.profileSaveFailed'))
    } finally {
      setSaving(false)
    }
  }

  const handleCvUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0]
    if (!token || !file || !canManage) return
    setMessage('')
    try {
      await uploadTalentCv(token, file, organizationId ?? undefined)
      setMessage(t('jobs.cvUploaded'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('jobs.cvUploadFailed'))
    }
  }

  const handleParseCv = async () => {
    if (!token || !canManage) return
    setMessage('')
    try {
      await parseTalentCv(token, organizationId ?? undefined)
      setMessage(t('jobs.cvParseStarted'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('jobs.cvParseFailed'))
    }
  }

  const handleDownloadCv = async () => {
    if (!token || !canManage) return
    setMessage('')
    try {
      const blob = await downloadMyTalentCv(token, organizationId ?? undefined)
      const url = URL.createObjectURL(blob)
      window.open(url, '_blank', 'noopener,noreferrer')
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('jobs.cvDownloadFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('nav.ecosystem')}
      title={t('jobs.talentProfileTitle')}
      description={t('jobs.talentProfileDescription')}
      actions={<Link to="/jobs" className="link-button">{t('jobs.backToMarketplace')}</Link>}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.profile')}</p><h2>{t('jobs.registration')}</h2></div></div>
      {loading && canManage ? <p className="loading">{t('jobs.loadingProfile')}</p> : (
        <div className="record-form">
          <label>
            {t('jobs.professionalName')}
            <input value={professionalName} onChange={(event) => setProfessionalName(event.target.value)} required dir="auto" />
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
            {t('jobs.city')}
            <input value={city} onChange={(event) => setCity(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('jobs.skills')}
            <input value={skills} onChange={(event) => setSkills(event.target.value)} placeholder={t('jobs.skillsPlaceholder')} dir="auto" />
          </label>
          <label>
            {t('jobs.contactEmail')}
            <input type="email" value={contactEmail} onChange={(event) => setContactEmail(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('jobs.contactPhone')}
            <input value={contactPhone} onChange={(event) => setContactPhone(event.target.value)} dir="auto" />
          </label>
          <label className="checkbox-row">
            <input type="checkbox" checked={isPublic} onChange={(event) => setIsPublic(event.target.checked)} />
            {t('jobs.publicProfile')}
          </label>
          <button type="button" disabled={saving || !professionalName} onClick={() => void handleSave()}>
            {saving ? t('common.saving') : t('common.save')}
          </button>
        </div>
      )}
    </section>

    {canManage && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('jobs.cvSection')}</p><h2>{t('jobs.cvUpload')}</h2></div></div>
        <p className="muted">{profile?.cv_parse_status ? t('jobs.cvStatus', { status: profile.cv_parse_status }) : t('jobs.noCvYet')}</p>
        <div className="record-form">
          <label>
            {t('jobs.selectCv')}
            <input type="file" accept=".pdf,.doc,.docx,.txt" onChange={(event) => void handleCvUpload(event)} />
          </label>
          {profile?.has_cv && (
            <>
              <button type="button" onClick={() => void handleDownloadCv()}>{t('jobs.downloadCv')}</button>
              <button type="button" onClick={() => void handleParseCv()}>{t('jobs.parseCv')}</button>
            </>
          )}
        </div>
      </section>
    )}
  </>
}
