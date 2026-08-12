import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import {
  applyDocumentLanguage,
  DEFAULT_LANGUAGE,
  LANGUAGE_STORAGE_KEY,
  resolveLanguage,
  type SupportedLanguage,
} from './languages'
import ar from './locales/ar.json'
import en from './locales/en.json'
import fr from './locales/fr.json'
import tr from './locales/tr.json'

function readStoredLanguage(): string | null {
  try {
    return typeof localStorage !== 'undefined' ? localStorage.getItem(LANGUAGE_STORAGE_KEY) : null
  } catch {
    return null
  }
}

const initialLanguage = resolveLanguage(readStoredLanguage())

applyDocumentLanguage(initialLanguage)

void i18n.use(initReactI18next).init({
  resources: {
    en: { translation: en },
    ar: { translation: ar },
    tr: { translation: tr },
    fr: { translation: fr },
  },
  lng: initialLanguage,
  fallbackLng: DEFAULT_LANGUAGE,
  interpolation: { escapeValue: false },
  returnEmptyString: false,
})

export function getCurrentLanguage(): SupportedLanguage {
  const language = i18n.language?.slice(0, 2)
  return resolveLanguage(language)
}

export function changeLanguage(language: SupportedLanguage) {
  try {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem(LANGUAGE_STORAGE_KEY, language)
    }
  } catch {
    // ignore storage failures (SSR/tests)
  }
  applyDocumentLanguage(language)
  return i18n.changeLanguage(language)
}

export default i18n
