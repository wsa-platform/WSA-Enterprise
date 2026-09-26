import { describe, expect, it } from 'vitest'
import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { I18nextProvider } from 'react-i18next'
import i18n from '../i18n/config'
import { ResearchContentViewer } from './ResearchContentViewer'
import {
  extractSafePlainText,
  resolveResearchContentCapability,
  safeExternalHttpUrl,
  safeHttpsContentUrl,
} from './researchContentCapability'

function renderContent(content: Parameters<typeof ResearchContentViewer>[0]['content']) {
  return renderToStaticMarkup(
    createElement(
      I18nextProvider,
      { i18n },
      createElement(ResearchContentViewer, { content, originalUrl: content.original_url }),
    ),
  )
}

describe('research content capability', () => {
  it('prefers structured full text over PDF, abstract, and original_url', () => {
    expect(resolveResearchContentCapability({
      structured_content: [{ heading: 'Methods', paragraphs: ['Documented method text.'] }],
      pdf_url: 'https://example.org/paper.pdf',
      abstract: 'Short abstract.',
      original_url: 'https://publisher.example/paper',
    })).toBe('full_text_structured')
  })

  it('uses PDF when no structured content exists', () => {
    expect(resolveResearchContentCapability({
      pdf_url: 'https://example.org/paper.pdf',
      abstract: 'Short abstract.',
      original_url: 'https://publisher.example/paper',
    })).toBe('pdf')
  })

  it('opens the original source when only an abstract exists and original_url is valid', () => {
    expect(resolveResearchContentCapability({
      abstract: 'Available abstract only.',
      original_url: 'https://publisher.example/paper',
    })).toBe('external_only')
  })

  it('keeps a labeled abstract-only preview when no original_url exists', () => {
    expect(resolveResearchContentCapability({
      abstract: 'Available abstract only.',
    })).toBe('abstract')
  })

  it('rejects unsafe original source URLs', () => {
    expect(safeExternalHttpUrl('javascript:alert(1)')).toBeNull()
    expect(safeExternalHttpUrl('data:text/html,hi')).toBeNull()
    expect(safeExternalHttpUrl('file:///tmp/paper.pdf')).toBeNull()
    expect(safeExternalHttpUrl('vbscript:msgbox(1)')).toBeNull()
    expect(safeExternalHttpUrl('https://user:pass@example.org/paper')).toBeNull()
    expect(resolveResearchContentCapability({
      original_url: 'javascript:alert(1)',
    })).toBe('unavailable')
  })

  it('opens original source for metadata-only and external-only results', () => {
    expect(resolveResearchContentCapability({
      original_url: 'https://publisher.example/paper',
    })).toBe('external_only')
    expect(resolveResearchContentCapability({
      original_url: 'https://doi.org/10.1000/x',
    })).toBe('external_only')
  })

  it('does not treat HTML as executable and strips scripts before internal text display', () => {
    const raw = '<h1>Safe title</h1><script>alert(1)</script><img src=x onerror="alert(2)">'
    expect(extractSafePlainText(raw)).toBe('Safe title')
    expect(extractSafePlainText(raw)).not.toContain('script')
    expect(extractSafePlainText(raw)).not.toContain('alert')
    const html = renderContent({
      html: raw,
      original_url: 'https://publisher.example/paper',
    })
    expect(html).toContain('data-testid="research-viewer-article"')
    expect(html).toContain('Safe title')
    expect(html).not.toContain('<script')
    expect(html).not.toContain('onerror')
    expect(html).not.toContain('javascript:')
  })

  it('rejects unsafe PDF and javascript URLs', () => {
    expect(safeHttpsContentUrl('javascript:alert(1)')).toBeNull()
    expect(safeHttpsContentUrl('data:application/pdf,AAAA')).toBeNull()
    expect(safeHttpsContentUrl('http://example.org/paper.pdf')).toBeNull()
    expect(safeHttpsContentUrl('https://user:pass@example.org/paper.pdf')).toBeNull()
    expect(resolveResearchContentCapability({
      pdf_url: 'javascript:alert(1)',
      original_url: 'https://publisher.example/paper',
    })).toBe('external_only')
  })

  it('falls back to original source when PDF is invalid and no abstract exists', () => {
    const html = renderContent({
      pdf_url: 'javascript:alert(1)',
      original_url: 'https://publisher.example/paper',
    })
    expect(html).toContain('data-testid="research-viewer-external-fallback"')
    expect(html).toContain('href="https://publisher.example/paper"')
    expect(html).not.toContain('data-testid="research-viewer-pdf"')
  })

  it('renders PDF, abstract, structured, and external fallback states', async () => {
    await i18n.changeLanguage('en')
    expect(renderContent({
      pdf_url: 'https://example.org/paper.pdf',
    })).toContain('data-testid="research-viewer-pdf"')
    expect(renderContent({
      abstract: 'Documented abstract.',
      original_url: 'https://publisher.example/paper',
    })).toContain('data-testid="research-viewer-external-fallback"')
    expect(renderContent({
      abstract: 'Documented abstract.',
    })).toContain('Abstract only — complete paper unavailable inside WSA.')
    expect(renderContent({
      structured_content: [{ heading: 'Results', paragraphs: ['Measured outcome.'] }],
    })).toContain('Measured outcome.')
    expect(renderContent({
      original_url: 'https://publisher.example/blocked',
    })).toContain('data-testid="research-viewer-external-fallback"')
    expect(renderContent({})).toContain('data-testid="research-viewer-unavailable"')
  })

  it('never creates an iframe workaround for a blocked publisher page', () => {
    const html = renderContent({
      original_url: 'https://publisher.example/blocked-frame',
    })
    expect(html).not.toContain('<iframe')
    expect(html).toContain('data-testid="research-viewer-external-fallback"')
  })
})
