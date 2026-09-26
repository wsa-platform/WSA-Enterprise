import { createElement, type ReactNode } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { I18nextProvider } from 'react-i18next'
import i18n from '../i18n/config'
import { citationHref } from './citationHref'
import { AuthProvider } from '../context/AuthContext'
import { PublicLayout } from './PublicLayout'
import { ResearchSourceLink } from './ResearchSourceLink'
import { ResearchViewerPage } from './ResearchViewerPage'
import { createCropSearchEpisode, createSearchEpisode, homeResultsPath } from './scientificSearchEpisode'
import {
  activateResearchResult,
  isClickableAnchorMarkup,
  isExternalSearchRedirect,
  loadResearchViewerRecord,
  researchResultId,
  researchViewerPath,
  researchViewerRecordFromCitation,
} from './researchViewer'

const session = new Map<string, string>()

beforeEach(() => {
  session.clear()
  const local = new Map<string, string>()
  vi.stubGlobal('localStorage', {
    getItem: (key: string) => local.get(key) ?? null,
    setItem: (key: string, value: string) => {
      local.set(key, String(value))
    },
    removeItem: (key: string) => {
      local.delete(key)
    },
    clear: () => local.clear(),
  })
  vi.stubGlobal('sessionStorage', {
    getItem: (key: string) => session.get(key) ?? null,
    setItem: (key: string, value: string) => {
      session.set(key, String(value))
    },
    removeItem: (key: string) => {
      session.delete(key)
    },
    clear: () => session.clear(),
  })
})

function renderWithRouter(path: string, node: ReactNode = createElement(ResearchViewerPage)) {
  return renderToStaticMarkup(
    createElement(
      I18nextProvider,
      { i18n },
      createElement(
        MemoryRouter,
        { initialEntries: [path] },
        createElement(
          Routes,
          null,
          createElement(Route, { path: '/research/result/:resultId', element: node }),
        ),
      ),
    ),
  )
}

describe('research viewer navigation', () => {
  it('builds an internal WSA viewer path from a real citation id', () => {
    expect(researchViewerPath('cite-1')).toBe('/research/result/cite-1')
    expect(researchResultId({ citation_id: 'cite-1', title: 'Wheat Study' })).toBe('cite-1')
  })

  it('unknown result ids render the existing missing state without crashing', () => {
    const html = renderWithRouter('/research/result/missing-result')
    expect(html).toContain('data-testid="research-viewer-page"')
    expect(html).toContain('data-testid="research-viewer-missing"')
  })

  it('does not derive a new identity from title and keeps identity stable if title changes', () => {
    const first = researchResultId({ source_id: 'https://openalex.org/W9', title: 'Original title' })
    const renamed = researchResultId({ source_id: 'https://openalex.org/W9', title: 'Changed title' })
    expect(first).toBe('https://openalex.org/W9')
    expect(renamed).toBe(first)
    expect(researchResultId({ title: 'Title only paper' })).toBe('src-0')
    expect(researchResultId({ title: 'Title only paper' })).not.toContain('Title only')
    expect(researchResultId({ source_id: '10.1000/doi-as-id', title: 'DOI paper' })).toBe('src-0')
  })

  it('stores a real original URL without fabricating one', () => {
    const record = researchViewerRecordFromCitation({
      citation_id: 'cite-1',
      title: 'Wheat Study',
      url: 'https://example.org/wheat-direct',
      doi: '10.1000/wheat',
    })
    expect(record.originalUrl).toBe('https://example.org/wheat-direct')
    expect(citationHref({ url: record.originalUrl })).toBe('https://example.org/wheat-direct')
  })

  it('does not fabricate a URL when none exists', () => {
    const record = researchViewerRecordFromCitation({
      title: 'Title only',
    })
    expect(record.originalUrl).toBeNull()
    expect(citationHref({ title: 'Title only' } as { url?: string })).toBeNull()
  })

  it('rejects Google search redirects as original sources', () => {
    expect(isExternalSearchRedirect('https://www.google.com/search?q=wheat')).toBe(true)
    expect(isExternalSearchRedirect('https://example.org/wheat-direct')).toBe(false)
  })

  it('first click activates the internal WSA viewer, not Google or a publisher page', () => {
    const { viewerPath, record } = activateResearchResult({
      citation_id: 'cite-nav',
      title: 'Agronomy evidence',
      url: 'https://doi.org/10.1000/agronomy',
      doi: '10.1000/agronomy',
    }, 'Selected scientific answer.')

    expect(viewerPath).toBe('/research/result/cite-nav')
    expect(viewerPath).not.toMatch(/google/i)
    expect(viewerPath.startsWith('/research/result/')).toBe(true)
    expect(record.originalUrl).toBe('https://doi.org/10.1000/agronomy')
    expect(loadResearchViewerRecord('cite-nav')?.title).toBe('Agronomy evidence')
  })

  it('search result title is a clickable internal Viewer anchor', () => {
    const html = renderToStaticMarkup(
      createElement(
        MemoryRouter,
        null,
        createElement(ResearchSourceLink, {
          citation: {
            citation_id: 'cite-1',
            title: 'Agronomy evidence',
            url: 'https://example.org/paper',
          },
          label: 'Agronomy evidence',
        }),
      ),
    )

    expect(isClickableAnchorMarkup(html, '/research/result/cite-1')).toBe(true)
    expect(html).toContain('data-testid="research-source-viewer-link"')
    expect(html).not.toContain('https://example.org/paper')
    expect(html).not.toMatch(/google/i)
    expect(isClickableAnchorMarkup(html, 'https://example.org/paper')).toBe(false)
  })

  it('rejects a plain-text URL as a clickable source', () => {
    expect(isClickableAnchorMarkup('https://example.org/paper', 'https://example.org/paper')).toBe(false)
    expect(isClickableAnchorMarkup('<span>https://example.org/paper</span>', 'https://example.org/paper')).toBe(false)
    expect(isClickableAnchorMarkup('<button>Original source</button>', 'https://example.org/paper')).toBe(false)
  })

  it('viewer click navigates inside WSA and original source is a separate clickable link', () => {
    const { viewerPath } = activateResearchResult({
      citation_id: 'cite-click',
      title: 'Dryland cultivation study',
      authors: ['Dr Researcher'],
      publication_year: 2023,
      abstract: 'Documented cultivation practices under limited rainfall.',
      pdf_url: 'https://example.org/click.pdf',
      url: 'https://doi.org/10.1000/click',
      doi: '10.1000/click',
    }, 'Selected scientific answer.', 0, [{ answer: 'Alternative genuine answer.' }])

    const html = renderWithRouter(viewerPath)

    expect(html).toContain('data-testid="research-viewer-page"')
    expect(html).toContain('This is a WSA research presentation page')
    expect(html).toContain('Selected scientific answer.')
    expect(html).toContain('Alternative genuine answer.')
    expect(html).toContain('Dr Researcher')
    expect(html).toContain('data-testid="research-viewer-pdf"')
    expect(isClickableAnchorMarkup(html, 'https://doi.org/10.1000/click')).toBe(true)
    expect(html).toContain('data-testid="research-viewer-original-source"')
    expect(html).not.toMatch(/confidence/i)
    expect(html).not.toMatch(/>\s*Direct\s*</)
    expect(html).not.toMatch(/>\s*Supporting\s*</)
    expect(html).not.toMatch(/google/i)
  })

  it('valid DOI is a clickable original source and invalid URL is not fabricated', () => {
    const doiRecord = activateResearchResult({
      citation_id: 'cite-doi',
      title: 'DOI source',
      doi: '10.1000/valid-doi',
    })
    expect(doiRecord.record.originalUrl).toBe('https://doi.org/10.1000/valid-doi')
    const doiHtml = renderWithRouter(doiRecord.viewerPath)
    expect(isClickableAnchorMarkup(doiHtml, 'https://doi.org/10.1000/valid-doi')).toBe(true)
    expect(doiHtml).toContain('data-testid="research-viewer-external-fallback"')

    const missing = activateResearchResult({
      citation_id: 'cite-none',
      title: 'Title only',
    })
    expect(missing.record.originalUrl).toBeNull()
    const missingHtml = renderWithRouter(missing.viewerPath)
    expect(missingHtml).toContain('data-testid="research-viewer-unavailable"')
    expect(missingHtml).not.toContain('href="https://')
  })

  it('viewer uses PublicLayout chrome and back-to-results restores the episode', async () => {
    await i18n.changeLanguage('en')
    const episode = createSearchEpisode({
      query: 'generic scientific question',
      language: 'en',
      episodeId: 'episode-restore',
      response: {
        status: 'scientific_generated',
        answer: 'Primary documented answer.',
        citations: [{
          citation_id: 'cite-layout',
          title: 'Layout source',
          url: 'https://example.org/layout',
        }],
        user_presentation: {
          primary_answer: 'Primary documented answer.',
          human_status: 'answered',
          user_notice_code: null,
          candidates: [{ result_id: 'cand-2', answer: 'Second documented answer.' }],
          sources: [{
            result_id: 'cite-layout',
            title: 'Layout source',
            original_url: 'https://example.org/layout',
          }],
        },
      },
    })

    const html = renderToStaticMarkup(
      createElement(
        AuthProvider,
        null,
        createElement(
          I18nextProvider,
          { i18n },
          createElement(
            MemoryRouter,
            { initialEntries: [`/research/result/cite-layout?episode=${episode.episodeId}`] },
            createElement(
              Routes,
              null,
              createElement(Route, {
                path: '/research/result/:resultId',
                element: createElement(PublicLayout, null, createElement(ResearchViewerPage)),
              }),
            ),
          ),
        ),
      ),
    )

    expect(html).toContain('class="public-site"')
    expect(html).toContain('id="public-primary-nav"')
    expect(html).toContain('data-testid="research-viewer-page"')
    expect(html).toContain('Primary documented answer.')
    expect(html).toContain('data-testid="research-viewer-original-source"')
    expect(html).toContain('href="https://example.org/layout"')
    expect(html).toContain('data-testid="research-viewer-back"')
    expect(html).toContain(homeResultsPath(episode.episodeId))
    expect(html).not.toMatch(/google/i)
  })

  it('opens an episode scientific result inside WSA and keeps the original source separate', () => {
    const episode = createSearchEpisode({
      query: 'wheat irrigation',
      language: 'en',
      episodeId: 'episode-results',
      response: {
        status: 'insufficient_evidence',
        user_presentation: {
          primary_answer: null,
          human_status: 'insufficient',
          user_notice_code: 'insufficient_direct_evidence',
          candidates: [],
          sources: [],
          results: [{
            result_id: 'https://openalex.org/Wnav',
            title: 'Internal topic paper',
            original_url: 'https://doi.org/10.1000/nav',
            doi: '10.1000/nav',
            authors: ['Researcher'],
            abstract: 'Documented abstract for internal display.',
            pdf_url: 'https://example.org/nav.pdf',
            confidence: 0.50,
          }],
        },
      },
    })

    const html = renderWithRouter(
      `/research/result/${encodeURIComponent('https://openalex.org/Wnav')}?episode=${episode.episodeId}`,
    )

    expect(html).toContain('data-testid="research-viewer-page"')
    expect(html).toContain('data-testid="research-viewer-title"')
    expect(html).toContain('Internal topic paper')
    expect(html).not.toMatch(/<h2[^>]*href=/)
    expect(html).toContain('data-testid="research-viewer-doi"')
    expect(html).toContain('10.1000/nav')
    expect(isClickableAnchorMarkup(html, 'https://doi.org/10.1000/nav')).toBe(true)
    expect(html).toContain('data-testid="research-viewer-original-source"')
    expect(html).toContain('target="_blank"')
    expect(html).toContain('data-testid="research-viewer-pdf"')
    expect(html).not.toContain('data-testid="research-viewer-abstract-notice"')
    expect(html).toContain(homeResultsPath(episode.episodeId))
  })

  it('opens the original source when only an abstract exists and original_url is valid', () => {
    const episode = createSearchEpisode({
      query: 'wheat irrigation',
      language: 'en',
      episodeId: 'episode-abstract-only',
      response: {
        status: 'insufficient_evidence',
        user_presentation: {
          primary_answer: null,
          human_status: 'insufficient',
          user_notice_code: 'insufficient_direct_evidence',
          candidates: [],
          sources: [],
          results: [{
            result_id: 'srcid-abstract-only',
            title: 'Abstract only paper',
            original_url: 'https://publisher.example/abstract-paper',
            abstract: 'This abstract is not the paper.',
            confidence: 0.50,
          }],
        },
      },
    })

    const html = renderWithRouter(`/research/result/srcid-abstract-only?episode=${episode.episodeId}`)
    expect(html).toContain('data-testid="research-viewer-external-fallback"')
    expect(html).toContain('href="https://publisher.example/abstract-paper"')
    expect(html).not.toContain('data-testid="research-viewer-title"')
    expect(html).not.toContain('data-testid="research-viewer-abstract-notice"')
  })

  it('still opens the internal viewer when original_url is missing', () => {
    const episode = createSearchEpisode({
      query: 'wheat irrigation',
      language: 'en',
      episodeId: 'episode-no-url',
      response: {
        status: 'insufficient_evidence',
        user_presentation: {
          primary_answer: null,
          human_status: 'insufficient',
          user_notice_code: 'insufficient_direct_evidence',
          candidates: [],
          sources: [],
          results: [{
            result_id: 'srcid-no-url',
            title: 'No original URL paper',
            abstract: 'Abstract remains available without original_url.',
            confidence: 0.50,
          }],
        },
      },
    })

    const html = renderWithRouter(`/research/result/srcid-no-url?episode=${episode.episodeId}`)
    expect(html).toContain('data-testid="research-viewer-page"')
    expect(html).toContain('No original URL paper')
    expect(html).toContain('data-testid="research-viewer-no-original"')
    expect(html).not.toContain('data-testid="research-viewer-original-source"')
    expect(html).not.toContain('data-testid="research-viewer-missing"')
    expect(html).toContain('data-testid="research-viewer-abstract"')
    expect(html).toContain('Abstract only — complete paper unavailable inside WSA.')
  })

  it('metadata-only results open the original source instead of a fake article page', () => {
    const episode = createSearchEpisode({
      query: 'wheat irrigation',
      language: 'en',
      episodeId: 'episode-meta',
      response: {
        status: 'insufficient_evidence',
        user_presentation: {
          primary_answer: null,
          human_status: 'insufficient',
          user_notice_code: 'insufficient_direct_evidence',
          candidates: [],
          sources: [],
          results: [{
            result_id: 'srcid-meta',
            title: 'Metadata only paper',
            original_url: 'https://publisher.example/meta-paper',
            confidence: 0.50,
          }],
        },
      },
    })

    const html = renderWithRouter(`/research/result/srcid-meta?episode=${episode.episodeId}`)
    expect(html).toContain('data-testid="research-viewer-external-fallback"')
    expect(html).toContain('href="https://publisher.example/meta-paper"')
    expect(html).not.toContain('data-testid="research-viewer-title"')
    expect(html).not.toContain('<iframe')
  })

  it('known result without content or original_url is unavailable, not missing', () => {
    const episode = createSearchEpisode({
      query: 'wheat irrigation',
      language: 'en',
      episodeId: 'episode-empty',
      response: {
        status: 'insufficient_evidence',
        user_presentation: {
          primary_answer: null,
          human_status: 'insufficient',
          user_notice_code: 'insufficient_direct_evidence',
          candidates: [],
          sources: [],
          results: [{
            result_id: 'srcid-empty',
            title: 'Empty content paper',
            confidence: 0.50,
          }],
        },
      },
    })

    const html = renderWithRouter(`/research/result/srcid-empty?episode=${episode.episodeId}`)
    expect(html).toContain('data-testid="research-viewer-unavailable"')
    expect(html).not.toContain('data-testid="research-viewer-missing"')
  })

  it('renders a PDF result inside WSA and keeps the original source separate', () => {
    const episode = createSearchEpisode({
      query: 'wheat irrigation',
      language: 'en',
      episodeId: 'episode-pdf',
      response: {
        status: 'insufficient_evidence',
        user_presentation: {
          primary_answer: null,
          human_status: 'insufficient',
          user_notice_code: 'insufficient_direct_evidence',
          candidates: [],
          sources: [],
          results: [{
            result_id: 'srcid-pdf',
            title: 'Open PDF paper',
            original_url: 'https://publisher.example/pdf-paper',
            pdf_url: 'https://example.org/open.pdf',
            confidence: 0.50,
          }],
        },
      },
    })

    const html = renderWithRouter(`/research/result/srcid-pdf?episode=${episode.episodeId}`)
    expect(html).toContain('data-testid="research-viewer-pdf"')
    expect(html).toContain('data="https://example.org/open.pdf"')
    expect(html).toContain('data-testid="research-viewer-original-source"')
    expect(html).not.toContain('<script')
  })

  it('keeps Home and Crop episode results isolated in the content viewer', () => {
    const home = createSearchEpisode({
      query: 'home query',
      language: 'en',
      episodeId: 'ep-home-content',
      response: {
        user_presentation: {
          primary_answer: null,
          human_status: 'insufficient',
          user_notice_code: 'insufficient_direct_evidence',
          candidates: [],
          sources: [],
          results: [{
            result_id: 'srcid-home-content',
            title: 'Home content paper',
            abstract: 'Home abstract.',
            confidence: 0.50,
          }],
        },
      },
    })
    const crop = createCropSearchEpisode({
      query: 'crop query',
      language: 'en',
      episodeId: 'ep-crop-content',
      returnPath: '/plant-production/field-crops?episode=ep-crop-content',
      response: {
        user_presentation: {
          primary_answer: null,
          human_status: 'insufficient',
          user_notice_code: 'insufficient_direct_evidence',
          candidates: [],
          sources: [],
          results: [{
            result_id: 'srcid-crop-content',
            title: 'Crop content paper',
            abstract: 'Crop abstract.',
            confidence: 0.50,
          }],
        },
      },
    })

    const homeHtml = renderWithRouter(`/research/result/srcid-home-content?episode=${home.episodeId}`)
    const cropHtml = renderWithRouter(`/research/result/srcid-crop-content?episode=${crop.episodeId}`)
    expect(homeHtml).toContain('Home content paper')
    expect(homeHtml).toContain('Home abstract.')
    expect(homeHtml).not.toContain('Crop content paper')
    expect(cropHtml).toContain('Crop content paper')
    expect(cropHtml).toContain('Crop abstract.')
    expect(cropHtml).not.toContain('Home content paper')
    expect(cropHtml).toContain('/plant-production/field-crops?episode=ep-crop-content')
  })
})
