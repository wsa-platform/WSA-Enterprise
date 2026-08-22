/** ISO 3166-1 alpha-2 codes stored as the stable country/nationality value. */
export const ISO_COUNTRY_CODES = [
  'AD', 'AE', 'AF', 'AG', 'AL', 'AM', 'AO', 'AR', 'AT', 'AU', 'AZ',
  'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BN', 'BO', 'BR', 'BS', 'BT', 'BW', 'BY', 'BZ',
  'CA', 'CD', 'CF', 'CG', 'CH', 'CI', 'CL', 'CM', 'CN', 'CO', 'CR', 'CU', 'CV', 'CY', 'CZ',
  'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ',
  'EC', 'EE', 'EG', 'ER', 'ES', 'ET',
  'FI', 'FJ', 'FM', 'FR',
  'GA', 'GB', 'GD', 'GE', 'GH', 'GM', 'GN', 'GQ', 'GR', 'GT', 'GW', 'GY',
  'HN', 'HR', 'HT', 'HU',
  'ID', 'IE', 'IL', 'IN', 'IQ', 'IR', 'IS', 'IT',
  'JM', 'JO', 'JP',
  'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KZ',
  'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY',
  'MA', 'MC', 'MD', 'ME', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MR', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
  'NA', 'NE', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NZ',
  'OM',
  'PA', 'PE', 'PG', 'PH', 'PK', 'PL', 'PS', 'PT', 'PW', 'PY',
  'QA',
  'RO', 'RS', 'RU', 'RW',
  'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SI', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SY', 'SZ',
  'TD', 'TG', 'TH', 'TJ', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ',
  'UA', 'UG', 'US', 'UY', 'UZ',
  'VA', 'VC', 'VE', 'VN', 'VU',
  'WS',
  'YE',
  'ZA', 'ZM', 'ZW',
] as const

export type CountryOption = {
  code: string
  label: string
}

function localeTag(locale: string): string {
  const language = locale.slice(0, 2).toLowerCase()
  if (language === 'ar') return 'ar'
  if (language === 'tr') return 'tr'
  if (language === 'fr') return 'fr'
  return 'en'
}

export function countryLabel(code: string, locale: string): string {
  const normalized = code.trim().toUpperCase()
  if (!normalized) return code
  try {
    return new Intl.DisplayNames([localeTag(locale)], { type: 'region' }).of(normalized) ?? code
  } catch {
    return code
  }
}

export function isIsoCountryCode(value: string): boolean {
  return ISO_COUNTRY_CODES.includes(value.trim().toUpperCase() as (typeof ISO_COUNTRY_CODES)[number])
}

export function listCountries(locale: string): CountryOption[] {
  return ISO_COUNTRY_CODES
    .map((code) => ({ code, label: countryLabel(code, locale) }))
    .sort((a, b) => a.label.localeCompare(b.label, localeTag(locale)))
}

export function filterCountries(locale: string, query: string): CountryOption[] {
  const list = listCountries(locale)
  const needle = query.trim().toLowerCase()
  if (!needle) return list
  return list.filter((item) => {
    const english = countryLabel(item.code, 'en').toLowerCase()
    return item.label.toLowerCase().includes(needle)
      || item.code.toLowerCase().includes(needle)
      || english.includes(needle)
  })
}
