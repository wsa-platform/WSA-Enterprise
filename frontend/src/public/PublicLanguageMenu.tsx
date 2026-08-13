import { useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { changeLanguage } from '../i18n/config'
import { LANGUAGE_LABELS, SUPPORTED_LANGUAGES, type SupportedLanguage } from '../i18n/languages'

export function PublicLanguageMenu() {
  const { t, i18n } = useTranslation()
  const [open, setOpen] = useState(false)
  const rootRef = useRef<HTMLDivElement>(null)
  const current = (i18n.language?.slice(0, 2) ?? 'en') as SupportedLanguage
  const currentLabel = LANGUAGE_LABELS[current] ?? LANGUAGE_LABELS.en

  useEffect(() => {
    const handleClick = (event: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(event.target as Node)) {
        setOpen(false)
      }
    }
    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setOpen(false)
    }
    document.addEventListener('mousedown', handleClick)
    document.addEventListener('keydown', handleEscape)
    return () => {
      document.removeEventListener('mousedown', handleClick)
      document.removeEventListener('keydown', handleEscape)
    }
  }, [])

  return (
    <div className="gs-lang-menu" ref={rootRef}>
      <button
        type="button"
        className="gs-btn gs-btn-ghost gs-lang-trigger"
        aria-expanded={open}
        aria-haspopup="listbox"
        aria-label={t('language.label')}
        onClick={() => setOpen((value) => !value)}
      >
        <span aria-hidden="true">🌐</span>
        <span>{currentLabel}</span>
        <span aria-hidden="true">▾</span>
      </button>
      {open && (
        <ul className="gs-lang-dropdown" role="listbox" aria-label={t('language.label')}>
          {SUPPORTED_LANGUAGES.map((language) => (
            <li key={language} role="presentation">
              <button
                type="button"
                role="option"
                aria-selected={language === current}
                className={language === current ? 'active' : undefined}
                onClick={() => {
                  void changeLanguage(language)
                  setOpen(false)
                }}
              >
                {LANGUAGE_LABELS[language]}
                {language === current && <span aria-hidden="true"> ✓</span>}
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
