import { useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import {
  extractSafePlainText,
  researchStructuredSections,
  resolveResearchContentCapability,
  safeExternalHttpUrl,
  safeHttpsContentUrl,
  type ResearchContentCapability,
  type ResearchContentInput,
} from './researchContentCapability'

export type ResearchContentViewerProps = {
  content: ResearchContentInput
  originalUrl?: string | null
}

function AbstractBody({ text }: { text: string }) {
  return (
    <div className="hp-research-abstract" data-testid="research-viewer-abstract">
      {text.split(/\n+/).map((paragraph, index) => (
        <p key={`abstract-${index}`}>{paragraph}</p>
      ))}
    </div>
  )
}

function StructuredArticle({ content }: { content: ResearchContentInput }) {
  const { t } = useTranslation()
  const sections = researchStructuredSections(content)
  return (
    <div className="hp-research-article" data-testid="research-viewer-article">
      <p className="hp-research-content-notice">{t('website.research.contentStructuredLabel')}</p>
      {sections.map((section, sectionIndex) => (
        <section key={`article-section-${sectionIndex}`}>
          {section.heading ? <h3>{section.heading}</h3> : null}
          {section.paragraphs.map((paragraph, paragraphIndex) => (
            <p key={`article-p-${sectionIndex}-${paragraphIndex}`}>{paragraph}</p>
          ))}
        </section>
      ))}
    </div>
  )
}

function PdfViewer({ url }: { url: string }) {
  const { t } = useTranslation()
  return (
    <div className="hp-research-pdf" data-testid="research-viewer-pdf">
      <p className="hp-research-content-notice">{t('website.research.contentPdfLabel')}</p>
      <object data={url} type="application/pdf" className="hp-research-pdf-frame">
        <p data-testid="research-viewer-pdf-fallback">{t('website.research.contentPdfUnavailable')}</p>
      </object>
    </div>
  )
}

function ExternalFallback({ url }: { url: string }) {
  const { t } = useTranslation()
  useEffect(() => {
    if (typeof window === 'undefined') {
      return
    }
    window.location.assign(url)
  }, [url])

  return (
    <div className="hp-research-external-fallback" data-testid="research-viewer-external-fallback">
      <p>{t('website.research.openingOriginalSource')}</p>
      <p>
        <a
          href={url}
          target="_blank"
          rel="noreferrer noopener"
          data-testid="research-viewer-original-source"
        >
          {t('website.research.originalSource')}
        </a>
      </p>
    </div>
  )
}

function UnavailableState() {
  const { t } = useTranslation()
  return (
    <p className="hp-research-status" role="status" data-testid="research-viewer-unavailable">
      {t('website.research.contentUnavailable')}
    </p>
  )
}

export function researchContentViewCapability(
  content: ResearchContentInput,
): ResearchContentCapability {
  return resolveResearchContentCapability(content)
}

/** Capability-driven scientific content. Never treats metadata as a paper. */
export function ResearchContentViewer({
  content,
  originalUrl = null,
}: ResearchContentViewerProps) {
  const { t } = useTranslation()
  const capability = resolveResearchContentCapability(content)
  const safeOriginal = safeExternalHttpUrl(originalUrl ?? content.original_url)
  const pdfUrl = safeHttpsContentUrl(content.pdf_url)
  const abstract = extractSafePlainText(content.abstract)

  if (capability === 'full_text_structured') {
    return <StructuredArticle content={content} />
  }

  if (capability === 'pdf' && pdfUrl) {
    return <PdfViewer url={pdfUrl} />
  }

  if (capability === 'abstract' && abstract !== '') {
    return (
      <div data-testid="research-viewer-abstract-block">
        <p className="hp-research-content-notice">{t('website.research.contentAbstractLabel')}</p>
        <p className="hp-research-content-notice" data-testid="research-viewer-abstract-notice">
          {t('website.research.contentAbstractNotice')}
        </p>
        <AbstractBody text={abstract} />
      </div>
    )
  }

  if (capability === 'external_only' && safeOriginal) {
    return <ExternalFallback url={safeOriginal} />
  }

  return <UnavailableState />
}
