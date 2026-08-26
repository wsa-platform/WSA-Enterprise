import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'

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
    <section className="gs-newsletter hp-newsletter hp-newsletter--design" aria-labelledby="newsletter-title">
      <div className="gs-container hp-newsletter-row">
        <div className="hp-newsletter-art" aria-hidden="true">
          <span className="hp-newsletter-envelope">✉️</span>
        </div>
        <div className="hp-newsletter-copy">
          <h2 id="newsletter-title">{t('website.newsletter.title')}</h2>
          <p className="gs-newsletter-desc">{t('website.newsletter.description')}</p>
        </div>
        <form className="gs-newsletter-form hp-newsletter-form" onSubmit={handleSubmit}>
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
        {notice && <p className="gs-newsletter-notice hp-newsletter-notice">{notice}</p>}
      </div>
    </section>
  )
}
