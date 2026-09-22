import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { describe, expect, it, vi } from 'vitest'
import { AuthProvider } from '../../context/AuthContext'
import { LibraryFilePreviewPanel } from './LibraryFilePreviewPanel'

function withAuth(node: React.ReactElement) {
  const store = new Map<string, string>([
    ['wsa_token', 'test-token'],
    ['wsa_user', JSON.stringify({ id: 1, name: 'U', email: 'u@wsa.test' })],
  ])
  vi.stubGlobal('localStorage', {
    getItem: (key: string) => store.get(key) ?? null,
    setItem: (key: string, value: string) => {
      store.set(key, String(value))
    },
    removeItem: (key: string) => {
      store.delete(key)
    },
    clear: () => store.clear(),
  })

  return createElement(AuthProvider, null, node)
}

describe('LibraryFilePreviewPanel', () => {
  it('renders pdf iframe preview for pdf files', () => {
    const html = renderToStaticMarkup(
      withAuth(
        createElement(LibraryFilePreviewPanel, {
          file: {
            id: 1,
            title: 'Wheat guide',
            title_ar: 'دليل القمح',
            extension: 'pdf',
            preview_mode: 'inline_browser',
            file_name: 'wheat-guide.pdf',
          },
          contentUrl: '/api/v1/library/files/1/content',
          onClose: () => undefined,
        }),
      ),
    )

    expect(html).toContain('library-file-preview-pdf')
    expect(html).toContain('/api/v1/library/files/1/content')
    expect(html).not.toContain('/api/v1/public/library/crop-files')
    expect(html).not.toContain('library-file-preview-unsupported')
  })

  it('shows unsupported message for docx without fake preview', () => {
    const html = renderToStaticMarkup(
      withAuth(
        createElement(LibraryFilePreviewPanel, {
          file: {
            id: 2,
            title: 'Corn report',
            title_ar: 'تقرير الذرة',
            extension: 'docx',
            preview_mode: 'download_only',
            file_name: 'corn-report.docx',
          },
          contentUrl: '/api/v1/library/files/2/content',
          onClose: () => undefined,
        }),
      ),
    )

    expect(html).toContain('library-file-preview-unsupported')
    expect(html).toContain('المعاينة غير متاحة')
    expect(html).toContain('corn-report.docx')
    expect(html).not.toContain('library-file-preview-pdf')
  })
})
