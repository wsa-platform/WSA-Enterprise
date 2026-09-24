import { Link, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { loadResearchViewerRecord } from './researchViewer'

/** Internal WSA presentation of a scientific research result. Not a publisher replica. */
export function ResearchViewerPage() {
  const { t } = useTranslation()
  const { resultId: rawId } = useParams()
  const resultId = rawId ? decodeURIComponent(rawId) : ''
  const record = resultId ? loadResearchViewerRecord(resultId) : null
  const authors = record?.authors?.filter((author) => author.trim() !== '') ?? []
  const alternatives = record?.alternativeAnswers?.filter((text) => text.trim() !== '') ?? []

  return (
    <main className="hp-research-section" data-testid="research-viewer-page">
      <p>
        <Link to="/" data-testid="research-viewer-home">
          {t('website.research.title')}
        </Link>
      </p>
      <h1>{t('website.research.viewerHeading', { defaultValue: 'Research result' })}</h1>
      <p className="hp-research-support" data-testid="research-viewer-wsa-notice">
        {t('website.research.viewerNotice', {
          defaultValue: 'This is a WSA research presentation page. It is not the original publisher website.',
        })}
      </p>

      {record ? (
        <article className="hp-research-result" data-testid="research-viewer-record">
          <h2 data-testid="research-viewer-title">{record.title}</h2>
          {record.answer ? (
            <div className="hp-research-answer" data-testid="research-viewer-answer">
              {record.answer.split('\n').map((paragraph, index) => (
                <p key={`viewer-answer-${index}`}>{paragraph}</p>
              ))}
            </div>
          ) : null}
          {alternatives.length > 0 ? (
            <div data-testid="research-viewer-alternatives">
              <h3>{t('website.research.alternativeAnswersHeading', { defaultValue: 'Alternative answers' })}</h3>
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
          {record.organization ? <p>{record.organization}</p> : null}
          {record.journal ? <p>{record.journal}</p> : null}
          {record.publicationYear ? <p>{record.publicationYear}</p> : null}
          {record.abstract ? (
            <p data-testid="research-viewer-abstract">{record.abstract}</p>
          ) : null}
          {record.originalUrl ? (
            <p>
              <a
                href={record.originalUrl}
                target="_blank"
                rel="noreferrer noopener"
                data-testid="research-viewer-original-source"
              >
                {t('website.research.originalSource', { defaultValue: 'Original source' })}
              </a>
            </p>
          ) : (
            <p data-testid="research-viewer-no-original">
              {t('website.research.noOriginalUrl', {
                defaultValue: 'No trustworthy original source URL is available.',
              })}
            </p>
          )}
        </article>
      ) : (
        <p className="hp-research-status" role="status" data-testid="research-viewer-missing">
          {t('website.research.viewerMissing', {
            defaultValue: 'This research result is no longer available in this session.',
          })}
        </p>
      )}
    </main>
  )
}
