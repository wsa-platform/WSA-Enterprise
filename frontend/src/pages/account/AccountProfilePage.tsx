import { useEffect, useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { updateAccountProfile } from '../../api/auth'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { apiFieldErrorMessages, translateApiError } from '../../i18n/apiErrors'
import { internalPaths } from '../../navigation/paths'

export function AccountProfilePage() {
  const { t } = useTranslation()
  const { token, user, setSession } = useAuth()
  const [name, setName] = useState(user?.name ?? '')
  const [notice, setNotice] = useState('')
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<string[]>([])
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    setName(user?.name ?? '')
  }, [user?.name])

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!token || !user || !name.trim()) return
    setSaving(true)
    setError('')
    setFieldErrors([])
    setNotice('')
    try {
      const updated = await updateAccountProfile(token, { name: name.trim() })
      setSession(token, updated)
      setNotice(t('accountPage.nameUpdated'))
    } catch (requestError) {
      setFieldErrors(apiFieldErrorMessages(requestError))
      setError(translateApiError(requestError) || t('accountPage.updateFailed'))
    } finally {
      setSaving(false)
    }
  }

  if (!token) {
    return <ErrorBanner message={t('errors.unauthorized')} />
  }

  if (!user) {
    return (
      <EmptyState
        title={t('accountPage.loadingProfile')}
        description={t('accountPage.noProfile')}
      />
    )
  }

  return (
    <>
      <PageHeader
        eyebrow={t('nav.myAccount')}
        title={t('accountPage.profile')}
        description={t('accountPage.profileDescription')}
        actions={<Link className="link-button" to={internalPaths.account}>{t('accountPage.backToAccount')}</Link>}
      />

      {error && <ErrorBanner message={error} />}
      {fieldErrors.length > 0 && (
        <ul className="field-errors">
          {fieldErrors.map((message) => <li key={message}>{message}</li>)}
        </ul>
      )}
      {notice && <p className="notice success">{notice}</p>}

      <section className="panel">
        <form className="record-form" onSubmit={(event) => void handleSubmit(event)}>
          <label>
            {t('common.name')}
            <input value={name} onChange={(event) => setName(event.target.value)} dir="auto" required />
          </label>
          <label>
            {t('common.email')}
            <input value={user.email} disabled readOnly />
          </label>
          <p className="muted">{t('accountPage.emailReadOnly')}</p>
          <div className="form-actions">
            <button type="submit" disabled={saving || !name.trim()}>
              {saving ? t('common.saving') : t('accountPage.saveProfile')}
            </button>
          </div>
        </form>
      </section>
    </>
  )
}
