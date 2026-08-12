import { describe, expect, it } from 'vitest'
import en from './locales/en.json'
import ar from './locales/ar.json'
import tr from './locales/tr.json'
import fr from './locales/fr.json'
import {
  DEFAULT_LANGUAGE,
  isRtlLanguage,
  isSupportedLanguage,
  LANGUAGE_STORAGE_KEY,
  resolveLanguage,
  SUPPORTED_LANGUAGES,
} from './languages'

function flattenKeys(value: unknown, prefix = ''): string[] {
  if (value === null || typeof value !== 'object' || Array.isArray(value)) {
    return prefix ? [prefix] : []
  }

  return Object.entries(value as Record<string, unknown>).flatMap(([key, nested]) => {
    const next = prefix ? `${prefix}.${key}` : key
    if (nested !== null && typeof nested === 'object' && !Array.isArray(nested)) {
      return flattenKeys(nested, next)
    }
    return [next]
  })
}

describe('i18n languages', () => {
  it('supports exactly four locales', () => {
    expect(SUPPORTED_LANGUAGES).toEqual(['en', 'ar', 'tr', 'fr'])
  })

  it('treats Arabic as RTL', () => {
    expect(isRtlLanguage('ar')).toBe(true)
    expect(isRtlLanguage('en')).toBe(false)
    expect(isRtlLanguage('tr')).toBe(false)
    expect(isRtlLanguage('fr')).toBe(false)
  })

  it('falls back to English for unknown languages', () => {
    expect(resolveLanguage('de')).toBe(DEFAULT_LANGUAGE)
    expect(resolveLanguage(null)).toBe(DEFAULT_LANGUAGE)
  })

  it('persists selected language key when storage is available', () => {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem(LANGUAGE_STORAGE_KEY, 'fr')
    expect(resolveLanguage(localStorage.getItem(LANGUAGE_STORAGE_KEY))).toBe('fr')
    expect(isSupportedLanguage('fr')).toBe(true)
    localStorage.removeItem(LANGUAGE_STORAGE_KEY)
  })
})

describe('locale key parity', () => {
  const enKeys = flattenKeys(en).sort()
  const arKeys = flattenKeys(ar).sort()
  const trKeys = flattenKeys(tr).sort()
  const frKeys = flattenKeys(fr).sort()

  it('keeps Arabic keys aligned with English', () => {
    expect(arKeys).toEqual(enKeys)
  })

  it('keeps Turkish keys aligned with English', () => {
    expect(trKeys).toEqual(enKeys)
  })

  it('keeps French keys aligned with English', () => {
    expect(frKeys).toEqual(enKeys)
  })
})
