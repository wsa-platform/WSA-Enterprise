import { useEffect, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router-dom'
import {
  deleteMyJobSeekerApplication,
  downloadMyJobSeekerPrimaryQualification,
  getMyJobSeekerProfile,
  upsertMyJobSeekerProfile,
  uploadMyJobSeekerPrimaryQualification,
} from '../../api/jobs'
import { ApiError } from '../../api/client'
import { PageHeader } from '../../components/PageHeader'
import { Panel } from '../../components/AppShell'
import { ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { apiFieldErrorMessages, translateApiError } from '../../i18n/apiErrors'
import {
  additionalEducationItems,
  parseYearsOfExperience,
  primaryEducationItem,
  isLettersOnlyText,
  sanitizeInternationalPhone,
  sanitizeYearsOfExperience,
  isPdfQualificationFile,
  toCandidateSavePayload,
  toDateInputValue,
  validateCandidateProfile,
  type EducationItem,
  type ExperienceItem,
  type ProfileSectionId,
} from './candidateProfile'
import { countryLabel } from './countries'
import { CountryCombobox } from './CountryCombobox'
import { JobSeekerField } from './JobSeekerField'
import './jobSeekerProfile.css'

function firstFilled(...values: Array<string | null | undefined>): string {
  for (const value of values) {
    if (typeof value === 'string' && value.trim() !== '') return value
  }
  return ''
}

function joinList(values: string[] | null | undefined): string {
  return (values ?? []).map((item) => item.trim()).filter(Boolean).join(', ')
}

function focusProfileSection(section: ProfileSectionId) {
  const root = document.getElementById(`job-seeker-section-${section}`)
  root?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  const firstField = root?.querySelector<HTMLElement>('input:not([disabled]):not([type=hidden]):not([type=file]), textarea, select:not([disabled])')
  firstField?.focus()
}

export function MyJobApplicationPage() {
  const { t, i18n } = useTranslation()
  const navigate = useNavigate()
  const { token, organizationId } = useAuth()
  const canAccess = Boolean(token)
  const [viewMode, setViewMode] = useState(true)
  const [saving, setSaving] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState(false)
  const [message, setMessage] = useState('')
  const [primaryQualificationFile, setPrimaryQualificationFile] = useState<File | null>(null)
  const [experienceItems, setExperienceItems] = useState<ExperienceItem[]>([])
  const [educationItems, setEducationItems] = useState<EducationItem[]>([])
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
  const [yearsOfExperience, setYearsOfExperience] = useState('')
  const [skills, setSkills] = useState('')
  const [languages, setLanguages] = useState('')
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})
  const [missingProfile, setMissingProfile] = useState(false)

  const { data: profile, loading, error, reload, setData } = useAsyncData(async () => {
    if (!token || !canAccess) return null
    try {
      setMissingProfile(false)
      return await getMyJobSeekerProfile(token, organizationId ?? undefined)
    } catch (requestError) {
      if (requestError instanceof ApiError && requestError.isNotFound) {
        setMissingProfile(true)
        return null
      }
      throw requestError
    }
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
    setDateOfBirth(toDateInputValue(profile.date_of_birth))
    setNationality(profile.nationality ?? '')
    setAddress(profile.address ?? '')
    setSkills((profile.skills ?? []).join(', '))
    setLanguages((profile.languages ?? []).join(', '))
    setExperienceItems(Array.isArray(profile.experience) ? (profile.experience as ExperienceItem[]) : [])
    setEducationItems(Array.isArray(profile.education) ? (profile.education as EducationItem[]) : [])
    setYearsOfExperience(profile.years_of_experience != null ? String(profile.years_of_experience) : '')
    setPrimaryQualificationFile(null)
  }, [profile])

  useEffect(() => {
    if (missingProfile) setViewMode(false)
  }, [missingProfile])

  const formState = useMemo(() => ({
    fullName,
    email,
    phone,
    country,
    city,
    dateOfBirth,
    nationality,
    address,
    targetJobTitle,
    biography,
    yearsOfExperience,
    specialization,
    skills,
    languages,
    experienceItems,
    educationItems,
  }), [
    fullName, email, phone, country, city, dateOfBirth, nationality, address,
    targetJobTitle, biography, yearsOfExperience, specialization, skills, languages,
    experienceItems, educationItems,
  ])

  const handleSave = async () => {
    if (!token || saving || deleting) return
    const errors = validateCandidateProfile(formState, {
      hasPrimaryQualificationDocument: Boolean(profile?.has_primary_qualification_document || primaryQualificationFile),
      qualificationFile: primaryQualificationFile,
    })
    setFieldErrors(errors)
    if (Object.keys(errors).length > 0) {
      setMessage(t('jobs.applicationValidationFailed'))
      const firstSection = errors.fullName || errors.email || errors.phone || errors.country || errors.city || errors.dateOfBirth || errors.nationality || errors.address
        ? 'personal'
        : errors.primaryQualification || errors.primaryQualificationDocument
          ? 'education'
          : 'professional'
      focusProfileSection(firstSection)
      return
    }
    setSaving(true)
    setMessage('')
    try {
      const org = organizationId ?? undefined
      let saved = profile
      if (!saved) {
        saved = await upsertMyJobSeekerProfile(token, toCandidateSavePayload(formState, { hasPrimaryQualificationDocument: false }), org)
      }
      if (primaryQualificationFile) {
        saved = await uploadMyJobSeekerPrimaryQualification(token, primaryQualificationFile, org)
        setPrimaryQualificationFile(null)
      }
      const hasDocument = Boolean(saved?.has_primary_qualification_document)
      saved = await upsertMyJobSeekerProfile(token, toCandidateSavePayload(formState, { hasPrimaryQualificationDocument: hasDocument }), org)
      setData(saved)
      setMessage(t('jobs.applicationSaved'))
      setMissingProfile(false)
      setViewMode(true)
    } catch (requestError) {
      const fieldMessages = apiFieldErrorMessages(requestError)
      setMessage(fieldMessages.join(' ') || (requestError instanceof ApiError ? requestError.message : '') || translateApiError(requestError) || t('jobs.applicationSaveFailed'))
    } finally {
      setSaving(false)
    }
  }

  const enterEditMode = () => {
    setViewMode(false)
    setMessage('')
    setFieldErrors({})
    window.setTimeout(() => {
      document.getElementById('job-seeker-full-name')?.focus()
    }, 0)
  }

  const handleCancel = () => {
    setViewMode(true)
    setMessage('')
    setFieldErrors({})
    if (token) void reload()
  }

  const handleDeleteApplication = async () => {
    if (!token || deleting) return
    setDeleting(true)
    setMessage('')
    try {
      const result = await deleteMyJobSeekerApplication(token, organizationId ?? undefined)
      setConfirmDelete(false)
      navigate('/jobs/applications', {
        replace: true,
        state: { notice: result.message || t('jobs.applicationDeleted') },
      })
    } catch (requestError) {
      setConfirmDelete(false)
      const fieldMessages = apiFieldErrorMessages(requestError)
      setMessage(fieldMessages.join(' ') || (requestError instanceof ApiError ? requestError.message : '') || translateApiError(requestError) || t('jobs.applicationDeleteFailed'))
    } finally {
      setDeleting(false)
    }
  }

  const handleViewQualificationDocument = async () => {
    if (!token) return
    try {
      const blob = await downloadMyJobSeekerPrimaryQualification(token, organizationId ?? undefined)
      const url = URL.createObjectURL(blob)
      window.open(url, '_blank', 'noopener,noreferrer')
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('jobs.primaryQualificationDownloadFailed'))
    }
  }

  const patchExperience = (index: number, patch: Partial<ExperienceItem>) => {
    setExperienceItems((items) => items.map((item, i) => (i === index ? { ...item, ...patch } : item)))
  }

  const patchEducation = (index: number, patch: Partial<EducationItem>) => {
    setEducationItems((items) => {
      const next = items.length ? [...items] : [{}]
      while (next.length <= index) next.push({})
      next[index] = { ...next[index], ...patch }
      return next
    })
  }

  if (!canAccess) {
    return <ErrorBanner message={t('jobs.noPermissionTalent')} />
  }

  const presentLabel = t('jobs.present')
  const locale = i18n.language ?? 'en'
  const yearsCount = parseYearsOfExperience(firstFilled(
    yearsOfExperience,
    profile?.years_of_experience != null ? String(profile.years_of_experience) : '',
  ))
  const yearsDisplay = yearsCount == null ? '' : t('jobs.yearsOfExperienceValue', { count: yearsCount })
  const displayedJobTitle = viewMode
    ? firstFilled(profile?.target_job_title, targetJobTitle)
    : firstFilled(targetJobTitle, profile?.target_job_title)
  const displayedField = viewMode
    ? firstFilled(profile?.specialization, specialization)
    : firstFilled(specialization, profile?.specialization)
  const displayedSkills = firstFilled(skills, joinList(profile?.skills))
  const displayedLanguages = firstFilled(languages, joinList(profile?.languages))
  const primaryEducation = primaryEducationItem(educationItems)
  const editing = !viewMode

  return (
    <div className="job-seeker-profile">
      <PageHeader
        eyebrow={t('nav.ecosystem')}
        title={t('jobs.myApplicationTitle')}
        actions={viewMode ? (
          <div className="profile-mode-bar" data-profile-mode="view">
            <button type="button" className="profile-edit-button" data-testid="edit-profile" onClick={enterEditMode}>
              {t('jobs.editProfile')}
            </button>
          </div>
        ) : null}
      />

      {error && !missingProfile ? <ErrorBanner message={error} onRetry={reload} /> : null}
      {message ? (
        <p className={`notice${message === t('jobs.applicationSaved') ? ' success' : ''}`} role="status" data-testid="profile-notice">
          {message}
        </p>
      ) : null}
      {Object.keys(fieldErrors).length > 0 && (
        <ul className="field-errors">
          {Object.values(fieldErrors).map((key) => <li key={key}>{t(key)}</li>)}
        </ul>
      )}

      {loading && !profile && !missingProfile ? (
        <p className="loading">{t('jobs.loadingProfile')}</p>
      ) : profile || missingProfile ? (
        <>
          <section className="panel profile-summary-card">
            <div className="panel-heading">
              <div>
                <p className="eyebrow">{t('jobs.profile')}</p>
                <h2>{t('jobs.myApplicationTitle')}</h2>
              </div>
            </div>
            <div className="js-summary">
              <strong dir="auto">{profile?.full_name || fullName || '—'}</strong>
              <p className="js-summary-title" dir="auto">{displayedJobTitle || '—'}</p>
              <p className="js-summary-meta" dir="auto">
                {[
                  profile?.city || city,
                  (profile?.country || country) ? countryLabel(profile?.country || country, locale) : '',
                ].filter(Boolean).join(', ') || '—'}
              </p>
              <div className="profile-highlights">
                <div className="profile-highlight">
                  <span>{t('jobs.targetJobTitle')}</span>
                  <strong dir="auto">{displayedJobTitle || '—'}</strong>
                </div>
                <div className="profile-highlight profile-highlight-emphasis">
                  <span>{t('jobs.yearsOfExperience')}</span>
                  <strong dir="auto">{yearsDisplay || '—'}</strong>
                </div>
                <div className="profile-highlight">
                  <span>{t('jobs.professionalField')}</span>
                  <strong dir="auto">{displayedField || '—'}</strong>
                </div>
              </div>
            </div>
          </section>

          <div id="job-seeker-section-personal">
            <Panel eyebrow={t('jobs.personalInfo')} title={t('jobs.personalInfo')}>
              <div className="js-grid js-grid-2">
                <JobSeekerField htmlFor="job-seeker-full-name" size="full" label={t('jobs.fullName')} value={firstFilled(fullName, profile?.full_name)} dir="auto" editing={editing} error={fieldErrors.fullName ? t(fieldErrors.fullName) : undefined}>
                  <input id="job-seeker-full-name" name="full_name" autoComplete="name" value={fullName} onChange={(e) => setFullName(e.target.value)} required dir="auto" />
                </JobSeekerField>
                <JobSeekerField htmlFor="job-seeker-email" size="medium" label={t('jobs.email')} value={firstFilled(email, profile?.email)} dir="ltr" editing={editing} error={fieldErrors.email ? t(fieldErrors.email) : undefined}>
                  <input id="job-seeker-email" name="email" type="email" autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} required dir="ltr" />
                </JobSeekerField>
                <JobSeekerField htmlFor="job-seeker-phone" size="medium" label={t('jobs.phoneNumber')} value={firstFilled(phone, profile?.phone)} dir="ltr" editing={editing} error={fieldErrors.phone ? t(fieldErrors.phone) : undefined}>
                  <input id="job-seeker-phone" name="phone" type="tel" inputMode="tel" autoComplete="tel" value={phone} onChange={(e) => setPhone(sanitizeInternationalPhone(e.target.value))} required dir="ltr" />
                </JobSeekerField>
                <JobSeekerField htmlFor="job-seeker-date-of-birth" size="short" label={t('jobs.dateOfBirth')} value={firstFilled(dateOfBirth, toDateInputValue(profile?.date_of_birth))} dir="ltr" editing={editing} error={fieldErrors.dateOfBirth ? t(fieldErrors.dateOfBirth) : undefined}>
                  <input id="job-seeker-date-of-birth" name="date_of_birth" type="date" autoComplete="bday" value={dateOfBirth} onChange={(e) => setDateOfBirth(e.target.value)} required dir="ltr" />
                </JobSeekerField>
                <JobSeekerField htmlFor="job-seeker-nationality" size="medium" label={t('jobs.nationality')} value={firstFilled(nationality, profile?.nationality) ? countryLabel(firstFilled(nationality, profile?.nationality), locale) : ''} editing={editing} error={fieldErrors.nationality ? t(fieldErrors.nationality) : undefined}>
                  <CountryCombobox id="job-seeker-nationality" name="nationality" value={nationality} onChange={setNationality} required placeholder={t('jobs.selectNationality')} searchPlaceholder={t('jobs.searchCountry')} locale={locale} />
                </JobSeekerField>
                <JobSeekerField htmlFor="job-seeker-country" size="medium" label={t('jobs.residenceCountry')} value={firstFilled(country, profile?.country) ? countryLabel(firstFilled(country, profile?.country), locale) : ''} editing={editing} error={fieldErrors.country ? t(fieldErrors.country) : undefined}>
                  <CountryCombobox id="job-seeker-country" name="country" value={country} onChange={setCountry} required placeholder={t('jobs.selectResidenceCountry')} searchPlaceholder={t('jobs.searchCountry')} locale={locale} />
                </JobSeekerField>
                <JobSeekerField htmlFor="job-seeker-city" size="short" label={t('jobs.city')} value={firstFilled(city, profile?.city)} editing={editing} error={fieldErrors.city ? t(fieldErrors.city) : undefined}>
                  <input id="job-seeker-city" name="city" autoComplete="address-level2" value={city} onChange={(e) => setCity(e.target.value)} required dir="auto" />
                </JobSeekerField>
                <JobSeekerField htmlFor="job-seeker-address" size="full" label={t('jobs.address')} value={firstFilled(address, profile?.address)} editing={editing} error={fieldErrors.address ? t(fieldErrors.address) : undefined}>
                  <input id="job-seeker-address" name="address" autoComplete="street-address" value={address} onChange={(e) => setAddress(e.target.value)} required dir="auto" />
                </JobSeekerField>
              </div>
            </Panel>
          </div>

          <div id="job-seeker-section-professional">
          <div id="job-seeker-section-education">
            <Panel eyebrow={t('jobs.professionalProfile')} title={t('jobs.professionalProfile')}>
              <div className="js-grid js-grid-3">
                <JobSeekerField htmlFor="job-seeker-target-job-title" size="medium" label={t('jobs.targetJobTitle')} value={displayedJobTitle} dir="auto" editing={editing} error={fieldErrors.targetJobTitle ? t(fieldErrors.targetJobTitle) : undefined}>
                  <input id="job-seeker-target-job-title" value={targetJobTitle} onChange={(e) => setTargetJobTitle(e.target.value)} dir="auto" />
                </JobSeekerField>
                <JobSeekerField htmlFor="job-seeker-professional-field" size="short" label={t('jobs.professionalField')} value={displayedField} dir="auto" editing={editing} error={fieldErrors.specialization ? t(fieldErrors.specialization) : undefined}>
                  <input id="job-seeker-professional-field" value={specialization} onChange={(e) => setSpecialization(e.target.value)} dir="auto" />
                </JobSeekerField>
                <JobSeekerField htmlFor="job-seeker-years-of-experience" size="short" label={t('jobs.yearsOfExperience')} value={yearsDisplay} editing={editing} error={fieldErrors.yearsOfExperience ? t(fieldErrors.yearsOfExperience) : undefined}>
                  <input id="job-seeker-years-of-experience" type="text" inputMode="numeric" value={yearsOfExperience} onChange={(e) => setYearsOfExperience(sanitizeYearsOfExperience(e.target.value))} dir="ltr" />
                </JobSeekerField>
              </div>
              <div className="js-item-card">
                <div className="js-grid js-grid-2">
                  <JobSeekerField htmlFor="job-seeker-primary-qualification" size="medium" label={t('jobs.qualification')} value={primaryEducation.degree} dir="auto" editing={editing} error={fieldErrors.primaryQualification ? t(fieldErrors.primaryQualification) : undefined}>
                    <input id="job-seeker-primary-qualification" name="primary_qualification" dir="auto" value={primaryEducation.degree ?? ''} onChange={(e) => patchEducation(0, { degree: e.target.value })} />
                  </JobSeekerField>
                  <JobSeekerField htmlFor="job-seeker-primary-country" size="medium" label={t('jobs.country')} value={primaryEducation.country ? countryLabel(primaryEducation.country, locale) : ''} editing={editing}>
                    <CountryCombobox id="job-seeker-primary-country" name="primary_education_country" value={primaryEducation.country ?? ''} onChange={(value) => patchEducation(0, { country: value })} placeholder={t('jobs.selectCountry')} searchPlaceholder={t('jobs.searchCountry')} locale={locale} />
                  </JobSeekerField>
                  <JobSeekerField htmlFor="job-seeker-primary-university" size="medium" label={t('jobs.university')} value={primaryEducation.institution} dir="auto" editing={editing}>
                    <input id="job-seeker-primary-university" dir="auto" value={primaryEducation.institution ?? ''} onChange={(e) => patchEducation(0, { institution: e.target.value })} />
                  </JobSeekerField>
                  <JobSeekerField htmlFor="job-seeker-primary-year" size="short" label={t('jobs.graduationYear')} value={primaryEducation.year != null ? String(primaryEducation.year) : ''} dir="ltr" editing={editing}>
                    <input id="job-seeker-primary-year" dir="ltr" type="number" min="1950" max="2100" value={primaryEducation.year ?? ''} onChange={(e) => patchEducation(0, { year: e.target.value ? Number(e.target.value) : undefined })} />
                  </JobSeekerField>
                  <JobSeekerField htmlFor="job-seeker-primary-qualification-document" size="full" label={t('jobs.selectQualificationDocument')} value={primaryQualificationFile?.name || profile?.primary_qualification_filename || (profile?.has_primary_qualification_document ? t('jobs.viewQualificationDocument') : '')} editing={editing} error={fieldErrors.primaryQualificationDocument ? t(fieldErrors.primaryQualificationDocument) : undefined}>
                    <input
                      id="job-seeker-primary-qualification-document"
                      type="file"
                      accept="application/pdf,.pdf"
                      onChange={(e) => {
                        const file = e.target.files?.[0] ?? null
                        if (file && !isPdfQualificationFile(file)) {
                          setPrimaryQualificationFile(null)
                          setFieldErrors((current) => ({ ...current, primaryQualificationDocument: 'jobs.primaryQualificationPdfOnly' }))
                          setMessage(t('jobs.primaryQualificationPdfOnly'))
                          e.target.value = ''
                          return
                        }
                        setPrimaryQualificationFile(file)
                        setFieldErrors((current) => {
                          const next = { ...current }
                          delete next.primaryQualificationDocument
                          return next
                        })
                      }}
                    />
                  </JobSeekerField>
                </div>
                {profile?.has_primary_qualification_document ? (
                  <button type="button" className="link-button" onClick={() => void handleViewQualificationDocument()}>
                    {t('jobs.viewQualificationDocument')}
                  </button>
                ) : null}
              </div>
              {additionalEducationItems(educationItems).map((item, additionalIndex) => {
                const index = additionalIndex + 1
                return (
                  <div key={index} className="js-item-card">
                    <p className="js-item-title">{t('jobs.additionalQualifications')}</p>
                    <div className="js-grid js-grid-2">
                      <JobSeekerField size="medium" label={t('jobs.qualification')} value={item.degree} dir="auto" editing={editing} htmlFor={`edu-degree-${index}`}>
                        <input id={`edu-degree-${index}`} dir="auto" value={item.degree ?? ''} onChange={(e) => patchEducation(index, { degree: e.target.value })} />
                      </JobSeekerField>
                      <JobSeekerField size="medium" label={t('jobs.country')} value={item.country ? countryLabel(item.country, locale) : ''} editing={editing} htmlFor={`edu-country-${index}`}>
                        <CountryCombobox id={`edu-country-${index}`} name={`education_country_${index}`} value={item.country ?? ''} onChange={(value) => patchEducation(index, { country: value })} placeholder={t('jobs.selectCountry')} searchPlaceholder={t('jobs.searchCountry')} locale={locale} />
                      </JobSeekerField>
                      <JobSeekerField size="medium" label={t('jobs.university')} value={item.institution} dir="auto" editing={editing} htmlFor={`edu-uni-${index}`}>
                        <input id={`edu-uni-${index}`} dir="auto" value={item.institution ?? ''} onChange={(e) => patchEducation(index, { institution: e.target.value })} />
                      </JobSeekerField>
                      <JobSeekerField size="short" label={t('jobs.graduationYear')} value={item.year != null ? String(item.year) : ''} dir="ltr" editing={editing} htmlFor={`edu-year-${index}`}>
                        <input id={`edu-year-${index}`} dir="ltr" type="number" min="1950" max="2100" value={item.year ?? ''} onChange={(e) => patchEducation(index, { year: e.target.value ? Number(e.target.value) : undefined })} />
                      </JobSeekerField>
                    </div>
                    {editing ? (
                      <button type="button" className="js-btn js-btn-danger" onClick={() => setEducationItems(educationItems.filter((_, i) => i !== index))}>{t('common.delete')}</button>
                    ) : null}
                  </div>
                )
              })}
              {editing ? (
                <div className="form-actions">
                  <button type="button" className="js-btn js-btn-secondary" onClick={() => setEducationItems(educationItems.length === 0 ? [{}, {}] : [...educationItems, {}])}>
                    {t('jobs.addEducation')}
                  </button>
                </div>
              ) : null}
              <p className="js-item-title">{t('jobs.workExperience')}</p>
              {experienceItems.length === 0 ? (
                <p className="js-empty">{t('jobs.emptyData')}</p>
              ) : (
                <div className="js-grid js-grid-stack">
                  {experienceItems.map((item, index) => (
                    <div key={index} className="js-item-card">
                      <div className="js-grid js-grid-2">
                        <JobSeekerField size="medium" label={t('jobs.employer')} value={item.company} dir="auto" editing={editing} htmlFor={`exp-company-${index}`}>
                          <input id={`exp-company-${index}`} dir="auto" value={item.company ?? ''} onChange={(e) => patchExperience(index, { company: e.target.value })} />
                        </JobSeekerField>
                        <JobSeekerField size="medium" label={t('jobs.jobTitle')} value={item.title} dir="auto" editing={editing} htmlFor={`exp-title-${index}`} error={item.title?.trim() && !isLettersOnlyText(item.title) ? t('jobs.jobTitleLettersOnly') : undefined}>
                          <input id={`exp-title-${index}`} dir="auto" value={item.title ?? ''} onChange={(e) => patchExperience(index, { title: e.target.value })} />
                        </JobSeekerField>
                        <JobSeekerField size="short" label={t('jobs.startDate')} value={item.start_date} dir="ltr" editing={editing} htmlFor={`exp-start-${index}`}>
                          <input id={`exp-start-${index}`} type="date" dir="ltr" value={item.start_date ?? ''} onChange={(e) => patchExperience(index, { start_date: e.target.value })} />
                        </JobSeekerField>
                        <JobSeekerField size="short" label={t('jobs.endDate')} value={item.current ? presentLabel : (item.end_date || '')} dir="ltr" editing={editing} htmlFor={`exp-end-${index}`}>
                          <input id={`exp-end-${index}`} type="date" dir="ltr" value={item.end_date ?? ''} disabled={Boolean(item.current)} onChange={(e) => patchExperience(index, { end_date: e.target.value })} />
                        </JobSeekerField>
                        {editing ? (
                          <label className="checkbox-row">
                            <input type="checkbox" checked={item.current ?? false} onChange={(e) => patchExperience(index, { current: e.target.checked, end_date: e.target.checked ? '' : item.end_date })} />
                            {t('jobs.currentPosition')}
                          </label>
                        ) : null}
                        <JobSeekerField size="full" label={t('jobs.description')} value={item.description} dir="auto" editing={editing} htmlFor={`exp-desc-${index}`}>
                          <textarea id={`exp-desc-${index}`} dir="auto" rows={3} value={item.description ?? ''} onChange={(e) => patchExperience(index, { description: e.target.value })} />
                        </JobSeekerField>
                      </div>
                    </div>
                  ))}
                </div>
              )}
              {editing ? (
                <div className="form-actions">
                  <button type="button" className="js-btn js-btn-secondary" onClick={() => setExperienceItems([...experienceItems, {}])}>
                    {t('jobs.addExperience')}
                  </button>
                </div>
              ) : null}
            </Panel>
          </div>
          </div>

          <Panel eyebrow={t('jobs.professionalSummary')} title={t('jobs.professionalSummary')}>
            <JobSeekerField htmlFor="job-seeker-biography" size="full" hideLabel label={t('jobs.professionalSummary')} value={viewMode ? firstFilled(profile?.biography, biography) : biography} dir="auto" editing={editing} error={fieldErrors.biography ? t(fieldErrors.biography) : undefined}>
              <textarea id="job-seeker-biography" value={biography} onChange={(e) => setBiography(e.target.value)} rows={3} dir="auto" />
            </JobSeekerField>
          </Panel>

          <Panel eyebrow={t('jobs.skills')} title={t('jobs.skills')}>
            <JobSeekerField htmlFor="job-seeker-skills" size="full" hideLabel label={t('jobs.skills')} value={viewMode ? firstFilled(joinList(profile?.skills), skills) : displayedSkills} dir="auto" editing={editing} error={fieldErrors.skills ? t(fieldErrors.skills) : undefined}>
              <input id="job-seeker-skills" value={skills} onChange={(e) => setSkills(e.target.value)} placeholder={t('jobs.skillsPlaceholder')} dir="auto" />
            </JobSeekerField>
          </Panel>

          <Panel eyebrow={t('jobs.languages')} title={t('jobs.languages')}>
            <JobSeekerField htmlFor="job-seeker-languages" size="full" hideLabel label={t('jobs.languages')} value={viewMode ? firstFilled(joinList(profile?.languages), languages) : displayedLanguages} dir="auto" editing={editing} error={fieldErrors.languages ? t(fieldErrors.languages) : undefined}>
              <input id="job-seeker-languages" value={languages} onChange={(e) => setLanguages(e.target.value)} placeholder={t('jobs.languages')} dir="auto" />
            </JobSeekerField>
          </Panel>

          <div className="js-bottom-actions">
            {!viewMode ? (
              <div className="js-save-row">
                <button type="button" className="profile-save-button" data-testid="save-profile" disabled={saving || deleting} onClick={() => void handleSave()}>
                  {saving ? t('common.saving') : t('jobs.saveChanges')}
                </button>
                <button type="button" className="profile-cancel-button" data-testid="cancel-profile" disabled={saving || deleting} onClick={handleCancel}>{t('common.cancel')}</button>
              </div>
            ) : null}
            <div className="js-delete-row">
              <button
                type="button"
                className="js-btn js-btn-danger js-delete-button"
                data-testid="delete-application"
                disabled={saving || deleting || missingProfile}
                onClick={() => setConfirmDelete(true)}
              >
                {t('jobs.deleteApplication')}
              </button>
            </div>
          </div>
        </>
      ) : null}

      {confirmDelete ? (
        <div className="js-confirm-backdrop" role="presentation" onClick={() => !deleting && setConfirmDelete(false)}>
          <div className="js-confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="job-seeker-delete-title" onClick={(event) => event.stopPropagation()}>
            <p id="job-seeker-delete-title">{t('jobs.deleteApplicationConfirm')}</p>
            <div className="js-confirm-actions">
              <button type="button" className="js-btn js-btn-secondary" disabled={deleting} onClick={() => setConfirmDelete(false)}>
                {t('common.cancel')}
              </button>
              <button type="button" className="js-btn js-btn-danger" disabled={deleting} onClick={() => void handleDeleteApplication()}>
                {t('jobs.deleteApplicationConfirmYes')}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  )
}
