import { useTranslation } from 'react-i18next'
import type { ResearchAgentCitation } from '../api/researchAgent'
import {
  type FieldCropCultivationProfile,
  resolveCropAnswerRenderMode,
} from '../api/fieldCropCultivation'
import { ResearchSourceLink } from './ResearchSourceLink'
import { toScientificUserPresentation, userNoticeTranslationKey } from './scientificUserPresentation'

export type FieldCropCanonicalAnswerViewProps = {
  profile: FieldCropCultivationProfile
  episodeId?: string | null
}

function citationLabel(citation: ResearchAgentCitation, _index: number, fallback: string): string {
  return citation.title?.trim() || fallback
}


/**
 * P7-U2 — Stage 5 canonical Crop answer presentation.
 * Answer body is server language; chrome uses UI i18n.
 */
export function FieldCropCanonicalAnswerView({ profile, episodeId = null }: FieldCropCanonicalAnswerViewProps) {
  const { t } = useTranslation()
  const mode = resolveCropAnswerRenderMode(profile)
  const presentation = toScientificUserPresentation(profile)
  const answer = presentation?.primary_answer ?? null
  const alternativeAnswers = presentation?.candidates ?? []
  const sources = presentation?.sources ?? []
  const noticeKey = userNoticeTranslationKey(presentation?.user_notice_code)
  const limited = presentation?.human_status === 'insufficient'
    || mode === 'limited'
    || (mode === 'canonical' && isLimitedLabel(profile.status))

  return (
    <div
      className={`gs-field-crop-canonical${limited ? ' gs-field-crop-canonical--limited' : ''}`}
      data-answer-mode={mode}
      data-answer-language={profile.language ?? ''}
    >
      {limited ? (
        <p className="gs-field-crop-profile-notice" role="status">
          {t(noticeKey || 'website.research.noAnswer')}
        </p>
      ) : noticeKey ? (
        <p className="gs-field-crop-profile-notice" role="status" data-testid="crop-research-notice">
          {t(noticeKey)}
        </p>
      ) : null}

      {answer ? (
        <section className="gs-field-crop-profile-section" aria-labelledby="field-crop-canonical-answer">
          <h3 id="field-crop-canonical-answer">{t('website.research.answerHeading')}</h3>
          <div className="gs-field-crop-profile-content" data-testid="crop-canonical-answer">
            {answer.split('\n').map((paragraph, index) => (
              <p key={`crop-answer-${index}`}>{paragraph}</p>
            ))}
          </div>
        </section>
      ) : limited ? (
        <p className="gs-field-crop-profile-notice" role="status">
          {t('website.research.noAnswer')}
        </p>
      ) : null}

      {alternativeAnswers.length > 0 ? (
        <section className="gs-field-crop-profile-section" data-testid="crop-research-alternatives">
          <h3>{t('website.research.alternativeAnswersHeading')}</h3>
          <ul>
            {alternativeAnswers.map((candidate, index) => (
              <li key={candidate.result_id ?? `crop-alt-${index}`}>{candidate.answer}</li>
            ))}
          </ul>
        </section>
      ) : null}

      {sources.length === 0 ? (
        <section className="gs-field-crop-profile-section" data-testid="crop-sources-empty">
          <h3 id="field-crop-canonical-sources">{t('website.research.sourcesHeading')}</h3>
          <p>{t('website.research.noEligibleDirectCitations')}</p>
        </section>
      ) : (
        <section className="gs-field-crop-profile-section" aria-labelledby="field-crop-canonical-sources">
          <h3 id="field-crop-canonical-sources">{t('website.research.sourcesHeading')}</h3>
          <ul className="gs-field-crop-profile-references" data-testid="crop-citations">
            {sources.map((source, index) => {
              const citation: ResearchAgentCitation = {
                citation_id: source.result_id,
                title: source.title,
                url: source.original_url,
                authors: source.authors,
                organization: source.organization,
                journal: source.journal,
                publication_year: source.publication_year,
              }
              const label = citationLabel(
                citation,
                index,
                t('website.research.citationFallback', { index: index + 1 }),
              )
              return (
                <li key={`${source.result_id}-${index}`}>
                  <ResearchSourceLink
                    citation={citation}
                    label={label}
                    answer={answer}
                    index={index}
                    alternatives={alternativeAnswers}
                    episodeId={episodeId}
                    resultId={source.result_id}
                  />
                  {source.organization ? ` — ${source.organization}` : null}
                </li>
              )
            })}
          </ul>
        </section>
      )}
    </div>
  )
}

function isLimitedLabel(status: string | undefined): boolean {
  if (!status) return false
  const normalized = status.toLowerCase()
  return (
    normalized.includes('insufficient') ||
    normalized === 'conflicted' ||
    normalized.includes('conflict')
  )
}

