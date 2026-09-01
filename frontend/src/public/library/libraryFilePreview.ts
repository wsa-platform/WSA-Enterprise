export type LibraryFilePreviewMode = 'pdf' | 'image' | 'text' | 'unsupported'

const IMAGE_EXTENSIONS = new Set(['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])
const TEXT_EXTENSIONS = new Set(['txt', 'csv'])

export function normalizeFileExtension(extension: string): string {
  return extension.trim().replace(/^\./, '').toLowerCase()
}

export function getFileExtensionFromName(fileName: string): string {
  const parts = fileName.split('.')
  if (parts.length < 2) return ''
  return normalizeFileExtension(parts[parts.length - 1] ?? '')
}

export function getLibraryFilePreviewMode(extension: string): LibraryFilePreviewMode {
  const normalized = normalizeFileExtension(extension)
  if (normalized === 'pdf') return 'pdf'
  if (IMAGE_EXTENSIONS.has(normalized)) return 'image'
  if (TEXT_EXTENSIONS.has(normalized)) return 'text'
  return 'unsupported'
}

export function isLibraryFilePreviewable(extension: string): boolean {
  return getLibraryFilePreviewMode(extension) !== 'unsupported'
}

export const LIBRARY_FILE_ACTION_LABEL = 'قراءة الملف'
