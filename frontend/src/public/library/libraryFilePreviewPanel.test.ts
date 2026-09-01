import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { describe, expect, it } from 'vitest'
import { LibraryFilePreviewPanel } from './LibraryFilePreviewPanel'

describe('LibraryFilePreviewPanel', () => {
  it('renders pdf iframe preview for pdf files', () => {
    const html = renderToStaticMarkup(
      createElement(LibraryFilePreviewPanel, {
        file: {
          id: 1,
          title: 'Wheat guide',
          title_ar: 'دليل القمح',
          extension: 'pdf',
          preview_mode: 'inline_browser',
          file_name: 'wheat-guide.pdf',
        },
        onClose: () => undefined,
      }),
    )

    expect(html).toContain('library-file-preview-pdf')
    expect(html).toContain('/api/v1/public/library/crop-files/1/content')
    expect(html).not.toContain('library-file-preview-unsupported')
  })

  it('shows unsupported message for docx without fake preview', () => {
    const html = renderToStaticMarkup(
      createElement(LibraryFilePreviewPanel, {
        file: {
          id: 2,
          title: 'Corn report',
          title_ar: 'تقرير الذرة',
          extension: 'docx',
          preview_mode: 'download_only',
          file_name: 'corn-report.docx',
        },
        onClose: () => undefined,
      }),
    )

    expect(html).toContain('library-file-preview-unsupported')
    expect(html).toContain('المعاينة غير متاحة')
    expect(html).toContain('corn-report.docx')
    expect(html).not.toContain('library-file-preview-pdf')
  })
})
