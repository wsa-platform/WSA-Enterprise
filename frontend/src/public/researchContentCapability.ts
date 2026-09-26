export type ResearchContentCapability =
  | 'full_text_structured'
  | 'pdf'
  | 'abstract'
  | 'external_only'
  | 'unavailable'

export type ResearchStructuredSection = {
  heading?: string | null
  paragraphs: string[]
}

export type ResearchContentInput = {
  abstract?: string | null
  pdf_url?: string | null
  original_url?: string | null
  structured_content?: ResearchStructuredSection[] | null
  full_text?: string | null
  html?: string | null
}

/** https-only content URL. Rejects javascript:, data:, credentials, and malformed values. */
export function safeHttpsContentUrl(raw: string | null | undefined): string | null {
  const value = raw?.trim() ?? ''
  if (value === '') {
    return null
  }
  try {
    const url = new URL(value)
    if (url.protocol !== 'https:') {
      return null
    }
    if (url.username !== '' || url.password !== '') {
      return null
    }
    if (url.hostname === '') {
      return null
    }
    return url.toString()
  } catch {
    return null
  }
}

export function safeExternalHttpUrl(raw: string | null | undefined): string | null {
  const value = raw?.trim() ?? ''
  if (value === '') {
    return null
  }
  try {
    const url = new URL(value)
    if (url.protocol !== 'https:' && url.protocol !== 'http:') {
      return null
    }
    if (url.username !== '' || url.password !== '') {
      return null
    }
    if (url.hostname === '') {
      return null
    }
    return url.toString()
  } catch {
    return null
  }
}

/** Strip scripts/markup. Article HTML is data, never application code. */
export function extractSafePlainText(raw: string | null | undefined): string {
  const value = raw?.trim() ?? ''
  if (value === '') {
    return ''
  }
  const withoutBlocks = value
    .replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, ' ')
    .replace(/<iframe\b[^>]*>[\s\S]*?<\/iframe>/gi, ' ')
    .replace(/on\w+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi, ' ')
    .replace(/javascript:/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
  return withoutBlocks.replace(/\s+/g, ' ').trim()
}

function normalizedSections(
  rows: ResearchStructuredSection[] | null | undefined,
): ResearchStructuredSection[] {
  if (!Array.isArray(rows)) {
    return []
  }
  return rows
    .map((row) => {
      const paragraphs = (row?.paragraphs ?? [])
        .map((paragraph) => extractSafePlainText(paragraph))
        .filter((paragraph) => paragraph !== '')
      const heading = extractSafePlainText(row?.heading ?? '')
      return {
        heading: heading !== '' ? heading : null,
        paragraphs,
      }
    })
    .filter((row) => row.heading || row.paragraphs.length > 0)
}

export function resolveResearchContentCapability(
  input: ResearchContentInput | null | undefined,
): ResearchContentCapability {
  if (!input) {
    return 'unavailable'
  }

  const sections = normalizedSections(input.structured_content)
  if (sections.length > 0) {
    return 'full_text_structured'
  }

  const fullText = extractSafePlainText(input.full_text ?? input.html)
  if (fullText !== '') {
    return 'full_text_structured'
  }

  const pdfUrl = safeHttpsContentUrl(input.pdf_url)
  if (pdfUrl) {
    return 'pdf'
  }

  if (safeExternalHttpUrl(input.original_url)) {
    return 'external_only'
  }

  const abstract = extractSafePlainText(input.abstract)
  if (abstract !== '') {
    return 'abstract'
  }

  return 'unavailable'
}

export function researchStructuredSections(
  input: ResearchContentInput | null | undefined,
): ResearchStructuredSection[] {
  const fromPayload = normalizedSections(input?.structured_content)
  if (fromPayload.length > 0) {
    return fromPayload
  }
  const fullText = extractSafePlainText(input?.full_text ?? input?.html)
  if (fullText === '') {
    return []
  }
  return [{ heading: null, paragraphs: [fullText] }]
}
