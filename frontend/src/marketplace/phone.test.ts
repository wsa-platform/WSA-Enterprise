import { describe, expect, it } from 'vitest'
import {
  CALLING_CODES,
  digitsOnly,
  formatCallingCodeOption,
  isE164Phone,
  isValidSellerEmail,
  normalizeSellerEmail,
  splitE164Phone,
  toE164Phone,
} from './phone'

describe('seller phone and email', () => {
  it('keeps the numeric phone field digits-only', () => {
    expect(digitsOnly('101-234 5678')).toBe('1012345678')
    expect(digitsOnly('abc12')).toBe('12')
    expect(digitsOnly('(010) 123-4567')).toBe('0101234567')
  })

  it('requires a calling code and rejects local-only or decorated numbers', () => {
    expect(toE164Phone('', '1012345678')).toBeNull()
    expect(toE164Phone('20', 'abcdefgh')).toBeNull()
    expect(toE164Phone('20', '12')).toBeNull()
    expect(isE164Phone('01012345678')).toBe(false)
    expect(isE164Phone('+20 1012345678')).toBe(false)
    expect(isE164Phone('+201012345678')).toBe(true)
    expect(isE164Phone('+905551234567')).toBe(true)
    expect(isE164Phone('+966501234567')).toBe(true)
  })

  it('normalizes international numbers to E.164', () => {
    expect(toE164Phone('20', '01012345678')).toBe('+201012345678')
    expect(toE164Phone('90', '5551234567')).toBe('+905551234567')
    expect(toE164Phone('966', '50-123-4567')).toBe('+966501234567')
    expect(splitE164Phone('+201012345678')).toEqual({ dial: '20', national: '1012345678' })
    expect(splitE164Phone('+905551234567').dial).toBe('90')
  })

  it('renders calling codes independently from currency country', () => {
    const egypt = CALLING_CODES.find((entry) => entry.iso === 'EG')
    expect(egypt?.dial).toBe('20')
    expect(formatCallingCodeOption(egypt!, 'en')).toMatch(/^\+20 — /)
  })

  it('requires and trims a valid seller email', () => {
    expect(isValidSellerEmail('')).toBe(false)
    expect(isValidSellerEmail('not-an-email')).toBe(false)
    expect(isValidSellerEmail('seller@example.com')).toBe(true)
    expect(normalizeSellerEmail('  seller@example.com  ')).toBe('seller@example.com')
  })
})
