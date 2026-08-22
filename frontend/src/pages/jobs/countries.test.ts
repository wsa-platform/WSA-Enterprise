import { describe, expect, it } from 'vitest'
import { countryLabel, filterCountries, isIsoCountryCode, listCountries } from './countries'

describe('country options', () => {
  it('exposes a selectable ISO country list for nationality and residence', () => {
    const english = listCountries('en')
    expect(english.some((item) => item.code === 'SA')).toBe(true)
    expect(english.some((item) => item.code === 'TR')).toBe(true)
    expect(english.some((item) => item.code === 'FR')).toBe(true)
    expect(english.find((item) => item.code === 'SA')?.label).toMatch(/saudi/i)
    expect(isIsoCountryCode('SA')).toBe(true)
    expect(isIsoCountryCode('Saudi')).toBe(false)
  })

  it('localizes country names for the supported languages', () => {
    expect(countryLabel('SA', 'ar')).toMatch(/سعود/)
    expect(countryLabel('TR', 'tr')).toMatch(/Türk/i)
    expect(countryLabel('FR', 'fr')).toMatch(/fran/i)
    expect(countryLabel('EG', 'en')).toMatch(/egypt/i)
  })

  it('filters the country list by localized name, English name, and ISO code', () => {
    expect(filterCountries('en', 'saudi').some((item) => item.code === 'SA')).toBe(true)
    expect(filterCountries('ar', 'مصر').some((item) => item.code === 'EG')).toBe(true)
    expect(filterCountries('ar', 'egypt').some((item) => item.code === 'EG')).toBe(true)
    expect(filterCountries('fr', 'égypte').some((item) => item.code === 'EG')).toBe(true)
    expect(filterCountries('en', 'eg').some((item) => item.code === 'EG')).toBe(true)
    expect(filterCountries('en', 'zzzz-not-a-country')).toEqual([])
  })
})
