import { useEffect, useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { updateAccountProfile } from '../../api/auth'
import { PageHeader } from '../../components/PageHeader'
import { ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { translateApiError } from '../../i18n/apiErrors'

export function AccountProfilePage() {
  const { t } = useTranslation()
  const { token, user, setSession } = useAuth()
  const [name, setName] = useState(user?.name ?? '')
  const [notice, setNotice] = useState('')
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    setName(user?.name ?? '')
  }, [user?.name])

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!token || !user || !name.trim()) return
    setSaving(true)
    setError('')
    setNotice('')
    try {
      const updated = await updateAccountProfile(token, { name: name.trim() })
      setSession(token, updated)
      setNotice(t('accountPage.nameUpdated'))
    } catch (requestError) {
      setError(translateApiError(requestError) || t('accountPage.updateFailed'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <>
      <PageHeader
        eyebrow={t('nav.myAccount')}
        title={t('accountPage.profile')}
        description={t('accountPage.profileDescription')}
        actions={<Link className="link-button" to="/account">{t('accountPage.backToAccount')}</Link>}
      />

      {error && <ErrorBanner message={error} />}
      {notice && <p className="notice">{notice}</p>}

      <section className="panel">
        <form className="record-form" onSubmit={(event) => void handleSubmit(event)}>
          <label>
            {t('common.name')}
            <input value={name} onChange={(event) => setName(event.target.value)} dir="auto" required />
          </label>
          <label>
            {t('common.email')}
            <input value={user?.email ?? ''} disabled readOnly />
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
