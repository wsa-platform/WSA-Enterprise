import { describe, expect, it } from 'vitest'
import {
  CURRENCY_COUNTRIES,
  countryForCurrency,
  formatCurrencyCountryOption,
  isMappedCurrency,
  isValidCurrencyCountryPair,
  resolveCurrencyCountry,
} from './currencyCountries'

describe('currency-country mapping', () => {
  it('renders currency options as Currency name — Country', () => {
    const sar = CURRENCY_COUNTRIES.find((entry) => entry.currency === 'SAR')
    expect(sar).toBeTruthy()
    expect(formatCurrencyCountryOption(sar!, 'ar')).toBe('الريال السعودي — السعودية')
    expect(formatCurrencyCountryOption(sar!, 'en')).toBe('Saudi Riyal — Saudi Arabia')
    const egp = CURRENCY_COUNTRIES.find((entry) => entry.currency === 'EGP')
    const tryCurrency = CURRENCY_COUNTRIES.find((entry) => entry.currency === 'TRY')
    const eur = CURRENCY_COUNTRIES.find((entry) => entry.currency === 'EUR')
    expect(formatCurrencyCountryOption(egp!, 'en')).toBe('Egyptian Pound — Egypt')
    expect(formatCurrencyCountryOption(tryCurrency!, 'en')).toBe('Turkish Lira — Türkiye')
    expect(formatCurrencyCountryOption(eur!, 'en')).toBe('Euro — European Union')
  })

  it('derives country from the selected currency', () => {
    expect(countryForCurrency('SAR')).toBe('SA')
    expect(countryForCurrency('EGP')).toBe('EG')
    expect(countryForCurrency('TRY')).toBe('TR')
    expect(countryForCurrency('sar')).toBe('SA')
  })

  it('keeps currency and country consistent and rejects free-text currencies', () => {
    expect(resolveCurrencyCountry('SAR', 'EG')).toEqual({ currency: 'SAR', country: 'SA', matched: true })
    expect(resolveCurrencyCountry('EGP', 'SA')).toEqual({ currency: 'EGP', country: 'EG', matched: true })
    expect(resolveCurrencyCountry('TRY')).toEqual({ currency: 'TRY', country: 'TR', matched: true })
    expect(isMappedCurrency('USD')).toBe(true)
    expect(isMappedCurrency('ريال')).toBe(false)
    expect(isValidCurrencyCountryPair('EGP', 'EG')).toBe(true)
    expect(isValidCurrencyCountryPair('EGP', 'TR')).toBe(false)
    expect(resolveCurrencyCountry('XXX', 'QQ').matched).toBe(false)
  })

  it('resolves incomplete legacy values without throwing', () => {
    expect(() => resolveCurrencyCountry(undefined, undefined)).not.toThrow()
    expect(resolveCurrencyCountry(null, 'SA')).toEqual({ currency: 'SAR', country: 'SA', matched: true })
    expect(resolveCurrencyCountry('not-a-code', null).matched).toBe(false)
  })
})
