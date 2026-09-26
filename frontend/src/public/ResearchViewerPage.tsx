import { Link, useParams, useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { loadResearchViewerRecord } from './researchViewer'
import { findEpisodeResult, loadSearchEpisode, resultsPath } from './scientificSearchEpisode'

/** Internal WSA presentation of a scientific research result. Not a publisher replica. */
export function ResearchViewerPage() {
  const { t } = useTranslation()
  const { resultId: rawId } = useParams()
  const [searchParams] = useSearchParams()
  const resultId = rawId ? decodeURIComponent(rawId) : ''
  const episodeId = searchParams.get('episode')
  const episode = loadSearchEpisode(episodeId)
  const episodeHit = episode && resultId ? findEpisodeResult(episode, resultId) : { result: undefined, source: undefined, candidate: undefined }
  const stored = resultId ? loadResearchViewerRecord(resultId) : null
  const source = episodeHit.result ?? episodeHit.source
  const candidate = episodeHit.candidate
  const title = source?.title || stored?.title || (candidate ? t('website.research.answerHeading') : '')
  const answer = candidate?.answer || episode?.presentation.primary_answer || stored?.answer || null
  const alternatives = (episode?.presentation.candidates ?? stored?.alternativeAnswers ?? [])
    .filter((item) => (typeof item === 'string' ? item : item.answer).trim() !== '' && (typeof item === 'string' ? item : item.answer) !== answer)
    .map((item) => (typeof item === 'string' ? item : item.answer))
  const authors = (source?.authors ?? stored?.authors ?? []).filter((author) => author.trim() !== '')
  const originalUrl = source?.original_url ?? stored?.originalUrl ?? null
  const organization = source?.organization ?? stored?.organization ?? null
  const journal = source?.journal ?? stored?.journal ?? null
  const publicationYear = source?.publication_year ?? stored?.publicationYear ?? null
  const hasRecord = Boolean(source || candidate || stored)
  const backHref = episode ? resultsPath(episode) : '/'

  return (
    <section className="hp-research-section" data-testid="research-viewer-page">
      <p>
        <Link to={backHref} data-testid="research-viewer-back">
          {t('website.research.backToResults')}
        </Link>
      </p>
      <h1>{t('website.research.viewerHeading')}</h1>
      <p className="hp-research-support" data-testid="research-viewer-wsa-notice">
        {t('website.research.viewerNotice')}
      </p>

      {hasRecord ? (
        <article className="hp-research-result" data-testid="research-viewer-record">
          {title ? <h2 data-testid="research-viewer-title">{title}</h2> : null}
          {answer ? (
            <div className="hp-research-answer" data-testid="research-viewer-answer">
              {answer.split('\n').map((paragraph, index) => (
                <p key={`viewer-answer-${index}`}>{paragraph}</p>
              ))}
            </div>
          ) : null}
          {alternatives.length > 0 ? (
            <div data-testid="research-viewer-alternatives">
              <h3>{t('website.research.alternativeAnswersHeading')}</h3>
              <ul>
                {alternatives.map((text, index) => (
                  <li key={`viewer-alt-${index}`}>{text}</li>
                ))}
              </ul>
            </div>
          ) : null}
          {authors.length > 0 ? (
            <p data-testid="research-viewer-authors">{authors.join(', ')}</p>
          ) : null}
          {organization ? <p>{organization}</p> : null}
          {journal ? <p>{journal}</p> : null}
          {publicationYear ? <p>{publicationYear}</p> : null}
          {stored?.abstract ? (
            <p data-testid="research-viewer-abstract">{stored.abstract}</p>
          ) : null}
          {originalUrl ? (
            <p>
              <a
                href={originalUrl}
                target="_blank"
                rel="noreferrer noopener"
                data-testid="research-viewer-original-source"
              >
                {t('website.research.originalSource')}
              </a>
            </p>
          ) : (
            <p data-testid="research-viewer-no-original">
              {t('website.research.noOriginalUrl')}
            </p>
          )}
        </article>
      ) : (
        <p className="hp-research-status" role="status" data-testid="research-viewer-missing">
          {t('website.research.viewerMissing')}
        </p>
      )}
    </section>
  )
}
