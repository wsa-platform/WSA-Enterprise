import { countryDisplayName } from './isoCountries'

export type CallingCode = {
  iso: string
  dial: string
}

/** Dialing prefixes only. Independent of currency country and product origin. */
export const CALLING_CODES: CallingCode[] = [
  { iso: 'SA', dial: '966' },
  { iso: 'EG', dial: '20' },
  { iso: 'TR', dial: '90' },
  { iso: 'AE', dial: '971' },
  { iso: 'KW', dial: '965' },
  { iso: 'QA', dial: '974' },
  { iso: 'BH', dial: '973' },
  { iso: 'OM', dial: '968' },
  { iso: 'JO', dial: '962' },
  { iso: 'LB', dial: '961' },
  { iso: 'IQ', dial: '964' },
  { iso: 'SY', dial: '963' },
  { iso: 'YE', dial: '967' },
  { iso: 'PS', dial: '970' },
  { iso: 'MA', dial: '212' },
  { iso: 'TN', dial: '216' },
  { iso: 'DZ', dial: '213' },
  { iso: 'LY', dial: '218' },
  { iso: 'SD', dial: '249' },
  { iso: 'SO', dial: '252' },
  { iso: 'US', dial: '1' },
  { iso: 'CA', dial: '1' },
  { iso: 'GB', dial: '44' },
  { iso: 'DE', dial: '49' },
  { iso: 'FR', dial: '33' },
  { iso: 'IT', dial: '39' },
  { iso: 'ES', dial: '34' },
  { iso: 'NL', dial: '31' },
  { iso: 'BE', dial: '32' },
  { iso: 'CH', dial: '41' },
  { iso: 'AT', dial: '43' },
  { iso: 'SE', dial: '46' },
  { iso: 'NO', dial: '47' },
  { iso: 'DK', dial: '45' },
  { iso: 'PL', dial: '48' },
  { iso: 'GR', dial: '30' },
  { iso: 'PT', dial: '351' },
  { iso: 'IE', dial: '353' },
  { iso: 'CN', dial: '86' },
  { iso: 'JP', dial: '81' },
  { iso: 'KR', dial: '82' },
  { iso: 'IN', dial: '91' },
  { iso: 'PK', dial: '92' },
  { iso: 'BD', dial: '880' },
  { iso: 'ID', dial: '62' },
  { iso: 'MY', dial: '60' },
  { iso: 'SG', dial: '65' },
  { iso: 'TH', dial: '66' },
  { iso: 'VN', dial: '84' },
  { iso: 'PH', dial: '63' },
  { iso: 'AU', dial: '61' },
  { iso: 'NZ', dial: '64' },
  { iso: 'BR', dial: '55' },
  { iso: 'AR', dial: '54' },
  { iso: 'MX', dial: '52' },
  { iso: 'ZA', dial: '27' },
  { iso: 'NG', dial: '234' },
  { iso: 'KE', dial: '254' },
  { iso: 'GH', dial: '233' },
  { iso: 'RU', dial: '7' },
  { iso: 'UA', dial: '380' },
]

const E164 = /^\+[1-9]\d{7,14}$/
const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

export function digitsOnly(value: string): string {
  return value.replace(/\D/g, '')
}

export function isE164Phone(value: string): boolean {
  return E164.test(value.trim())
}

export function isValidSellerEmail(value: string): boolean {
  return EMAIL.test(value.trim())
}

export function normalizeSellerEmail(value: string): string {
  return value.trim()
}

export function formatCallingCodeOption(entry: CallingCode, language: string): string {
  return `+${entry.dial} — ${countryDisplayName(entry.iso, language) || entry.iso}`
}

export function toE164Phone(dial: string, national: string): string | null {
  const code = digitsOnly(dial)
  const number = digitsOnly(national).replace(/^0+/, '')
  if (!code || !number) return null
  const combined = `+${code}${number}`
  return isE164Phone(combined) ? combined : null
}

export function splitE164Phone(value?: string | null): { dial: string; national: string } {
  const trimmed = (value ?? '').trim()
  if (!trimmed) return { dial: '', national: '' }
  const digits = digitsOnly(trimmed)
  const sorted = [...CALLING_CODES].sort((a, b) => b.dial.length - a.dial.length)
  for (const entry of sorted) {
    if (digits.startsWith(entry.dial)) {
      return { dial: entry.dial, national: digits.slice(entry.dial.length) }
    }
  }
  return { dial: '', national: digits }
}
