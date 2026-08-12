export const SUPPORTED_LANGUAGES = ['en', 'ar', 'tr', 'fr'] as const

export type SupportedLanguage = (typeof SUPPORTED_LANGUAGES)[number]

export const DEFAULT_LANGUAGE: SupportedLanguage = 'en'

export const RTL_LANGUAGES = new Set<SupportedLanguage>(['ar'])

export const LANGUAGE_STORAGE_KEY = 'wsa.language'

export function isSupportedLanguage(value: string | null | undefined): value is SupportedLanguage {
  return SUPPORTED_LANGUAGES.includes(value as SupportedLanguage)
}

export function isRtlLanguage(language: SupportedLanguage): boolean {
  return RTL_LANGUAGES.has(language)
}

export function resolveLanguage(stored?: string | null): SupportedLanguage {
  if (isSupportedLanguage(stored)) return stored
  const browser = typeof navigator !== 'undefined' ? navigator.language?.slice(0, 2) : undefined
  if (isSupportedLanguage(browser)) return browser
  return DEFAULT_LANGUAGE
}

export function applyDocumentLanguage(language: SupportedLanguage) {
  if (typeof document === 'undefined') return
  document.documentElement.lang = language
  document.documentElement.dir = isRtlLanguage(language) ? 'rtl' : 'ltr'
}

export const LANGUAGE_LABELS: Record<SupportedLanguage, string> = {
  en: 'English',
  ar: 'العربية',
  tr: 'Türkçe',
  fr: 'Français',
}
