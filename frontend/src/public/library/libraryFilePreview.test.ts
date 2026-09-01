import { describe, expect, it } from 'vitest'
import {
  getFileExtensionFromName,
  getLibraryFilePreviewMode,
  isLibraryFilePreviewable,
} from './libraryFilePreview'

describe('library file preview', () => {
  it('preserves pdf extension for preview', () => {
    expect(getFileExtensionFromName('guide.PDF')).toBe('pdf')
    expect(getLibraryFilePreviewMode('pdf')).toBe('pdf')
    expect(isLibraryFilePreviewable('pdf')).toBe(true)
  })

  it('preserves non-pdf extensions without converting them', () => {
    expect(getFileExtensionFromName('report.docx')).toBe('docx')
    expect(getFileExtensionFromName('slides.pptx')).toBe('pptx')
    expect(getFileExtensionFromName('sheet.xls')).toBe('xls')
    expect(getLibraryFilePreviewMode('docx')).toBe('unsupported')
    expect(isLibraryFilePreviewable('docx')).toBe(false)
  })

  it('supports image preview modes', () => {
    expect(getLibraryFilePreviewMode('jpg')).toBe('image')
    expect(getLibraryFilePreviewMode('png')).toBe('image')
    expect(isLibraryFilePreviewable('png')).toBe(true)
  })
})
