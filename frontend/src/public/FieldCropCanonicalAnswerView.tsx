import { useTranslation } from 'react-i18next'
import type { ResearchAgentCitation } from '../api/researchAgent'
import {
  formatCropConflictText,
  type FieldCropAnswerClaim,
  type FieldCropCultivationProfile,
  resolveCanonicalCropAnswerText,
  resolveCropAnswerRenderMode,
} from '../api/fieldCropCultivation'
import { citationHref, sourcePresentationState } from './citationHref'

export type FieldCropCanonicalAnswerViewProps = {
  profile: FieldCropCultivationProfile
}

function citationLabel(citation: ResearchAgentCitation, _index: number, fallback: string): string {
  return citation.title?.trim() || fallback
}


/**
 * P7-U2 — Stage 5 canonical Crop answer presentation.
 * Answer body is server language; chrome uses UI i18n.
 */
export function FieldCropCanonicalAnswerView({ profile }: FieldCropCanonicalAnswerViewProps) {
  const { t } = useTranslation()
  const mode = resolveCropAnswerRenderMode(profile)
  const answer = resolveCanonicalCropAnswerText(profile)
  const citations = profile.citations ?? []
  const limitations = profile.limitations ?? []
  const conflicts = profile.conflicts ?? []
  const claims = profile.claims ?? []
  const keyFindings = profile.key_findings ?? []
  const additionalInformation = profile.additional_information?.trim() || null
  const uncertainty = profile.uncertainty?.trim() || null
  const showUncertainty = Boolean(uncertainty) && uncertainty !== answer
  const limited = mode === 'limited' || (mode === 'canonical' && isLimitedLabel(profile.status))

  return (
    <div
      className={`gs-field-crop-canonical${limited ? ' gs-field-crop-canonical--limited' : ''}`}
      data-answer-mode={mode}
      data-answer-language={profile.language ?? ''}
    >
      {limited ? (
        <p className="gs-field-crop-profile-notice" role="status">
          {t('website.research.insufficientNotice', {
            defaultValue: 'This response is limited by insufficient or conflicting scientific evidence.',
          })}
        </p>
      ) : null}

      {profile.status ? (
        <p className="gs-field-crop-profile-meta">
          {t('website.research.status', { status: profile.status })}
        </p>
      ) : null}

      {profile.language ? (
        <p className="gs-field-crop-profile-meta" data-testid="crop-answer-language">
          {t('website.research.answerLanguage', {
            language: profile.language,
            defaultValue: `Answer language: ${profile.language}`,
          })}
        </p>
      ) : null}

      {typeof profile.confidence === 'number' ? (
        <p className="gs-field-crop-profile-meta" data-testid="crop-confidence">
          {t('website.research.confidence', {
            confidence: Math.round(profile.confidence * 100) / 100,
            defaultValue: `Confidence: ${Math.round(profile.confidence * 100) / 100}`,
          })}
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
      ) : mode === 'limited' ? (
        <p className="gs-field-crop-profile-notice" role="status">
          {t('website.research.noAnswer')}
        </p>
      ) : null}

      {profile.detailed_explanation?.trim() &&
      profile.detailed_explanation.trim() !== answer ? (
        <section className="gs-field-crop-profile-section">
          <h3>{t('website.research.detailedExplanationHeading', { defaultValue: 'Detailed explanation' })}</h3>
          <div className="gs-field-crop-profile-content">
            {profile.detailed_explanation.split('\n').map((paragraph, index) => (
              <p key={`crop-detail-${index}`}>{paragraph}</p>
            ))}
          </div>
        </section>
      ) : null}

      {additionalInformation ? (
        <section className="gs-field-crop-profile-section" data-testid="crop-additional">
          <h3>{t('website.research.additionalInformationHeading', { defaultValue: 'Additional information' })}</h3>
          <div className="gs-field-crop-profile-content">
            {additionalInformation.split('\n').map((paragraph, index) => (
              <p key={`crop-additional-${index}`}>{paragraph}</p>
            ))}
          </div>
        </section>
      ) : null}

      {keyFindings.length > 0 ? (
        <section className="gs-field-crop-profile-section">
          <h3>{t('website.research.keyFindingsHeading', { defaultValue: 'Key findings' })}</h3>
          <ul>
            {keyFindings.map((finding, index) => (
              <li key={`finding-${index}`}>{finding}</li>
            ))}
          </ul>
        </section>
      ) : null}

      {limitations.length > 0 ? (
        <section className="gs-field-crop-profile-section" data-testid="crop-limitations">
          <h3>{t('website.research.limitationsHeading', { defaultValue: 'Limitations' })}</h3>
          <ul>
            {limitations.map((limitation, index) => (
              <li key={`limitation-${index}`}>{limitation}</li>
            ))}
          </ul>
        </section>
      ) : null}

      {showUncertainty ? (
        <section className="gs-field-crop-profile-section" data-testid="crop-uncertainty">
          <h3>{t('website.research.uncertaintyHeading', { defaultValue: 'Uncertainty' })}</h3>
          <p>{uncertainty}</p>
        </section>
      ) : null}

      {conflicts.length > 0 ? (
        <section className="gs-field-crop-profile-section" data-testid="crop-conflicts">
          <h3>{t('website.research.conflictsHeading', { defaultValue: 'Conflicts' })}</h3>
          <ul>
            {conflicts.map((conflict, index) => (
              <li key={`conflict-${index}`}>{formatCropConflictText(conflict)}</li>
            ))}
          </ul>
        </section>
      ) : null}

      {claims.length > 0 ? (
        <section className="gs-field-crop-profile-section" data-testid="crop-claims">
          <h3>{t('website.research.claimsHeading', { defaultValue: 'Claims' })}</h3>
          <ul>
            {claims.map((claim, index) => (
              <li key={claim.claim_id ?? `claim-${index}`}>{formatClaimLine(claim)}</li>
            ))}
          </ul>
        </section>
      ) : null}

      {sourcePresentationState(citations) === 'no_eligible_direct_citations' ? (
        <section className="gs-field-crop-profile-section" data-testid="crop-sources-empty">
          <h3 id="field-crop-canonical-sources">{t('website.research.sourcesHeading')}</h3>
          <p>{t('website.research.noEligibleDirectCitations', {
            defaultValue: 'No eligible direct citations are available for this answer.',
          })}</p>
        </section>
      ) : citations.length > 0 ? (
        <section className="gs-field-crop-profile-section" aria-labelledby="field-crop-canonical-sources">
          <h3 id="field-crop-canonical-sources">{t('website.research.sourcesHeading')}</h3>
          <ul className="gs-field-crop-profile-references" data-testid="crop-citations">
            {citations.map((citation, index) => {
              const label = citationLabel(
                citation,
                index,
                t('website.research.citationFallback', { index: index + 1 }),
              )
              const href = citationHref(citation)
              return (
                <li key={`${citation.citation_id ?? citation.doi ?? citation.url ?? citation.title ?? 'c'}-${index}`}>
                  {href ? (
                    <a href={href} target="_blank" rel="noreferrer noopener">
                      {label}
                    </a>
                  ) : (
                    <strong>{label}</strong>
                  )}
                  {citation.organization ? ` — ${citation.organization}` : null}
                  {citation.doi ? ` · DOI: ${citation.doi}` : null}
                  {citation.evidence_id ? (
                    <span data-evidence-id={citation.evidence_id}>{` · evidence: ${citation.evidence_id}`}</span>
                  ) : null}
                </li>
              )
            })}
          </ul>
        </section>
      ) : null}
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

function formatClaimLine(claim: FieldCropAnswerClaim): string {
  const parts = [
    claim.claim_text?.trim() || claim.claim_id || 'claim',
    claim.question_claim_id ? `question_claim_id=${claim.question_claim_id}` : null,
    claim.evidence_ids && claim.evidence_ids.length > 0
      ? `evidence_ids=${claim.evidence_ids.join(',')}`
      : null,
  ].filter(Boolean)
  return parts.join(' · ')
}
