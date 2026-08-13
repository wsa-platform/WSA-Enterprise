import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { WaveDivider } from './WaveDivider'

export function PublicNewsletter() {
  const { t } = useTranslation()
  const [email, setEmail] = useState('')
  const [notice, setNotice] = useState('')

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!email.trim()) return
    setNotice(t('website.newsletter.success'))
    setEmail('')
  }

  return (
    <>
      <WaveDivider fill="oklch(0.35 0.12 145)" />
      <section className="gs-newsletter" aria-labelledby="newsletter-title">
        <div className="gs-container gs-newsletter-inner">
          <div className="gs-newsletter-icon" aria-hidden="true">
            🌿
          </div>
          <h2 id="newsletter-title">{t('website.newsletter.title')}</h2>
          <p className="gs-newsletter-desc">{t('website.newsletter.description')}</p>
          <form className="gs-newsletter-form" onSubmit={handleSubmit}>
            <label className="visually-hidden" htmlFor="newsletter-email">
              {t('website.newsletter.emailLabel')}
            </label>
            <input
              id="newsletter-email"
              type="email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              placeholder={t('website.newsletter.emailPlaceholder')}
              required
              autoComplete="email"
            />
            <button type="submit">{t('website.newsletter.subscribe')}</button>
          </form>
          {notice && <p className="gs-newsletter-notice">{notice}</p>}
          <p className="gs-newsletter-hint">{t('website.newsletter.hint')}</p>
        </div>
      </section>
    </>
  )
}
