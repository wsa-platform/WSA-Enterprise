import { countryDisplayName } from './isoCountries'

export type CurrencyCountry = {
  currency: string
  country: string
}

type LocalizedName = { ar: string; en: string; tr: string; fr: string }

const CURRENCY_NAMES: Record<string, LocalizedName> = {
  SAR: { ar: 'الريال السعودي', en: 'Saudi Riyal', tr: 'Suudi Riyali', fr: 'Riyal saoudien' },
  EGP: { ar: 'الجنيه المصري', en: 'Egyptian Pound', tr: 'Mısır Lirası', fr: 'Livre égyptienne' },
  TRY: { ar: 'الليرة التركية', en: 'Turkish Lira', tr: 'Türk Lirası', fr: 'Livre turque' },
  AED: { ar: 'الدرهم الإماراتي', en: 'UAE Dirham', tr: 'BAE Dirhemi', fr: 'Dirham des EAU' },
  USD: { ar: 'الدولار الأمريكي', en: 'US Dollar', tr: 'ABD Doları', fr: 'Dollar américain' },
  GBP: { ar: 'الجنيه الإسترليني', en: 'Pound Sterling', tr: 'Sterlin', fr: 'Livre sterling' },
  EUR: { ar: 'اليورو', en: 'Euro', tr: 'Euro', fr: 'Euro' },
}

const COUNTRY_NAME_OVERRIDES: Record<string, LocalizedName> = {
  SA: { ar: 'السعودية', en: 'Saudi Arabia', tr: 'Suudi Arabistan', fr: 'Arabie saoudite' },
  EG: { ar: 'مصر', en: 'Egypt', tr: 'Mısır', fr: 'Égypte' },
  TR: { ar: 'تركيا', en: 'Türkiye', tr: 'Türkiye', fr: 'Turquie' },
  AE: { ar: 'الإمارات العربية المتحدة', en: 'United Arab Emirates', tr: 'Birleşik Arap Emirlikleri', fr: 'Émirats arabes unis' },
  US: { ar: 'الولايات المتحدة', en: 'United States', tr: 'Amerika Birleşik Devletleri', fr: 'États-Unis' },
  GB: { ar: 'المملكة المتحدة', en: 'United Kingdom', tr: 'Birleşik Krallık', fr: 'Royaume-Uni' },
  EU: { ar: 'الاتحاد الأوروبي', en: 'European Union', tr: 'Avrupa Birliği', fr: 'Union européenne' },
}

/** One currency → one country. Shared by create, edit, and display. */
export const CURRENCY_COUNTRIES: CurrencyCountry[] = [
  { currency: 'SAR', country: 'SA' },
  { currency: 'AED', country: 'AE' },
  { currency: 'KWD', country: 'KW' },
  { currency: 'QAR', country: 'QA' },
  { currency: 'BHD', country: 'BH' },
  { currency: 'OMR', country: 'OM' },
  { currency: 'EGP', country: 'EG' },
  { currency: 'JOD', country: 'JO' },
  { currency: 'LBP', country: 'LB' },
  { currency: 'IQD', country: 'IQ' },
  { currency: 'SYP', country: 'SY' },
  { currency: 'YER', country: 'YE' },
  { currency: 'ILS', country: 'IL' },
  { currency: 'MAD', country: 'MA' },
  { currency: 'TND', country: 'TN' },
  { currency: 'DZD', country: 'DZ' },
  { currency: 'LYD', country: 'LY' },
  { currency: 'SDG', country: 'SD' },
  { currency: 'SOS', country: 'SO' },
  { currency: 'TRY', country: 'TR' },
  { currency: 'EUR', country: 'EU' },
  { currency: 'USD', country: 'US' },
  { currency: 'GBP', country: 'GB' },
  { currency: 'CHF', country: 'CH' },
  { currency: 'CNY', country: 'CN' },
  { currency: 'JPY', country: 'JP' },
  { currency: 'KRW', country: 'KR' },
  { currency: 'INR', country: 'IN' },
  { currency: 'PKR', country: 'PK' },
  { currency: 'BDT', country: 'BD' },
  { currency: 'IDR', country: 'ID' },
  { currency: 'MYR', country: 'MY' },
  { currency: 'SGD', country: 'SG' },
  { currency: 'THB', country: 'TH' },
  { currency: 'VND', country: 'VN' },
  { currency: 'PHP', country: 'PH' },
  { currency: 'AUD', country: 'AU' },
  { currency: 'CAD', country: 'CA' },
  { currency: 'NZD', country: 'NZ' },
  { currency: 'MXN', country: 'MX' },
  { currency: 'BRL', country: 'BR' },
  { currency: 'ARS', country: 'AR' },
  { currency: 'ZAR', country: 'ZA' },
  { currency: 'NGN', country: 'NG' },
  { currency: 'KES', country: 'KE' },
  { currency: 'GHS', country: 'GH' },
  { currency: 'RUB', country: 'RU' },
  { currency: 'UAH', country: 'UA' },
  { currency: 'PLN', country: 'PL' },
  { currency: 'SEK', country: 'SE' },
  { currency: 'NOK', country: 'NO' },
  { currency: 'DKK', country: 'DK' },
]

const byCurrency = new Map(CURRENCY_COUNTRIES.map((entry) => [entry.currency, entry]))
const byCountry = new Map(CURRENCY_COUNTRIES.map((entry) => [entry.country, entry]))

function localeKey(language: string): keyof LocalizedName {
  const code = language.slice(0, 2).toLowerCase()
  if (code === 'ar' || code === 'tr' || code === 'fr') return code
  return 'en'
}

export function isMappedCurrency(code: string | null | undefined): boolean {
  return Boolean(code && byCurrency.has(code.trim().toUpperCase()))
}

export function countryForCurrency(currency: string | null | undefined): string | null {
  if (!currency) return null
  return byCurrency.get(currency.trim().toUpperCase())?.country ?? null
}

export function currencyName(currency: string, language: string): string {
  const code = currency.trim().toUpperCase()
  const named = CURRENCY_NAMES[code]
  if (named) return named[localeKey(language)]
  try {
    return new Intl.DisplayNames([language, 'en'], { type: 'currency' }).of(code) ?? code
  } catch {
    return code
  }
}

export function currencyCountryName(country: string, language: string): string {
  const code = country.trim().toUpperCase()
  const named = COUNTRY_NAME_OVERRIDES[code]
  if (named) return named[localeKey(language)]
  return countryDisplayName(code, language) || code
}

export function formatCurrencyCountryOption(entry: CurrencyCountry, language: string): string {
  return `${currencyName(entry.currency, language)} — ${currencyCountryName(entry.country, language)}`
}

export function isValidCurrencyCountryPair(currency: string, country: string): boolean {
  return countryForCurrency(currency) === country.trim().toUpperCase()
}

export function resolveCurrencyCountry(
  currency?: string | null,
  country?: string | null,
): { currency: string; country: string; matched: boolean } {
  const currencyCode = currency?.trim().toUpperCase() ?? ''
  const countryCode = country?.trim().toUpperCase() ?? ''
  const fromCurrency = currencyCode ? byCurrency.get(currencyCode) : undefined
  if (fromCurrency) {
    return { currency: fromCurrency.currency, country: fromCurrency.country, matched: true }
  }
  const fromCountry = countryCode ? byCountry.get(countryCode) : undefined
  if (fromCountry) {
    return { currency: fromCountry.currency, country: fromCountry.country, matched: true }
  }
  return {
    currency: currencyCode.length === 3 ? currencyCode : '',
    country: countryCode,
    matched: false,
  }
}
