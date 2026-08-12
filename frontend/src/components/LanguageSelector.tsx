import { useTranslation } from 'react-i18next'
import { changeLanguage } from '../i18n/config'
import { LANGUAGE_LABELS, SUPPORTED_LANGUAGES, type SupportedLanguage } from '../i18n/languages'

export function LanguageSelector() {
  const { i18n, t } = useTranslation()
  const current = (i18n.language?.slice(0, 2) ?? 'en') as SupportedLanguage

  return (
    <label className="language-selector">
      <span className="sr-only">{t('language.label')}</span>
      <select
        value={current}
        onChange={(event) => void changeLanguage(event.target.value as SupportedLanguage)}
        aria-label={t('language.label')}
      >
        {SUPPORTED_LANGUAGES.map((language) => (
          <option key={language} value={language}>
            {LANGUAGE_LABELS[language]}
          </option>
        ))}
      </select>
    </label>
  )
}
