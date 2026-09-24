import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import i18n from '../i18n/config'
import { ApiError } from '../api/client'
import {
  fetchFieldCropKnowledgeProfile,
  resolveCropAnswerRenderMode,
  type FieldCropCultivationProfile,
} from '../api/fieldCropCultivation'
import type { FieldCropOptionId } from './fieldCropCategories'
import { getFieldCropById } from './fieldCropCategories'
import { FieldCropCanonicalAnswerView } from './FieldCropCanonicalAnswerView'
import {
  createSearchEpisode,
  loadSearchEpisode,
} from './scientificSearchEpisode'
import type { ResearchAgentQueryResponse } from '../api/researchAgent'

type FieldCropFarmingNeedsPanelProps = {
  categoryId: string
  categoryName: string
  cropId: string
  cropName: string
  knowledgeOption?: FieldCropOptionId
}

function resolveFetchErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    if (error.status === 404) {
      return i18n.t('website.research.errorNotFound')
    }
    if (error.status === 0 || error.status >= 500) {
      return i18n.t('website.research.errorUnavailable')
    }
    return error.message || i18n.t('website.research.errorGeneric')
  }

  return i18n.t('website.research.errorUnavailable')
}

/** Displays Crop knowledge profile — Stage 5 canonical answer primary (P7-U2). */
export function FieldCropFarmingNeedsPanel({
  categoryId,
  categoryName,
  cropId,
  cropName,
  knowledgeOption = 'farming-needs',
}: FieldCropFarmingNeedsPanelProps) {
  const { t } = useTranslation()
  const [profile, setProfile] = useState<FieldCropCultivationProfile | null>(null)
  const [episodeId, setEpisodeId] = useState<string | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    setError(null)
    setProfile(null)
    setEpisodeId(null)

    const stored = matchingStoredCropEpisode(categoryId, cropId, knowledgeOption)
    if (stored) {
      setProfile(stored.profile)
      setEpisodeId(stored.episodeId)
      setLoading(false)
      return () => {
        cancelled = true
      }
    }

    fetchFieldCropKnowledgeProfile({
      selectedCropId: cropId,
      selectedCropName: cropName,
      selectedCategoryId: categoryId,
      selectedCategoryName: categoryName,
      knowledgeOption,
      scientificName: getFieldCropById(categoryId, cropId)?.scientificName,
    })
      .then((result) => {
        if (cancelled) {
          return
        }
        const episode = persistCropSearchEpisode({
          categoryId,
          cropId,
          knowledgeOption,
          cropName,
          profile: result,
        })
        setProfile(result)
        setEpisodeId(episode.episodeId)
      })
      .catch((fetchError: unknown) => {
        if (!cancelled) {
          setError(resolveFetchErrorMessage(fetchError))
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false)
        }
      })

    return () => {
      cancelled = true
    }
  }, [categoryId, categoryName, cropId, cropName, knowledgeOption])

  if (loading) {
    return (
      <div className="gs-field-crop-profile" aria-live="polite">
        <p>{t('website.research.loading')}</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className="gs-field-crop-profile" role="alert">
        <p>{error}</p>
      </div>
    )
  }

  if (!profile) {
    return null
  }

  return <FieldCropProfileArticle profile={profile} episodeId={episodeId} />
}

function matchingStoredCropEpisode(
  categoryId: string,
  cropId: string,
  knowledgeOption: string,
): { episodeId: string; profile: FieldCropCultivationProfile } | null {
  if (typeof window === 'undefined') {
    return null
  }
  const episode = loadSearchEpisode(new URLSearchParams(window.location.search).get('episode'))
  const origin = episode?.originState
  if (!episode || !origin) {
    return null
  }
  if (origin.categoryId !== categoryId || origin.cropId !== cropId || origin.optionId !== knowledgeOption) {
    return null
  }
  return {
    episodeId: episode.episodeId,
    profile: episode.response as FieldCropCultivationProfile,
  }
}

function persistCropSearchEpisode(input: {
  categoryId: string
  cropId: string
  knowledgeOption: string
  cropName: string
  profile: FieldCropCultivationProfile
}) {
  const episodeId = typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
    ? crypto.randomUUID()
    : `episode-${Date.now()}`
  const path = typeof window !== 'undefined' ? window.location.pathname : '/plant-production/field-crops'
  const returnPath = `${path}?episode=${encodeURIComponent(episodeId)}`
  const episode = createSearchEpisode({
    episodeId,
    query: `${input.cropName} ${input.knowledgeOption}`.trim(),
    language: input.profile.language ?? null,
    response: input.profile as ResearchAgentQueryResponse,
    returnPath,
    originState: {
      categoryId: input.categoryId,
      cropId: input.cropId,
      optionId: input.knowledgeOption,
    },
  })
  if (typeof window !== 'undefined') {
    const url = new URL(window.location.href)
    url.searchParams.set('episode', episode.episodeId)
    window.history.replaceState(null, '', url.pathname + url.search)
  }
  return episode
}

/** Sync presentational Crop profile (used by panel + P7-U2 tests). */
export function FieldCropProfileArticle({
  profile,
  episodeId = null,
}: {
  profile: FieldCropCultivationProfile
  episodeId?: string | null
}) {
  const { t } = useTranslation()
  const mode = resolveCropAnswerRenderMode(profile)
  const showLegacySections = mode === 'legacy'
  const showLegacyReferences =
    showLegacySections && profile.references.length > 0 && (profile.citations?.length ?? 0) === 0

  return (
    <article
      className="gs-field-crop-profile"
      aria-labelledby="field-crop-profile-title"
      data-crop-answer-mode={mode}
      style={{ width: '100%', marginTop: '1rem', textAlign: 'start' }}
    >
      <h2 id="field-crop-profile-title" style={{ fontSize: '1.25rem', fontWeight: 800, marginBottom: '1rem' }}>
        {profile.title}
      </h2>

      {profile.message ? (
        <p className="gs-field-crop-profile-notice" role="status">
          {profile.message}
        </p>
      ) : profile.load_state === 'retrieval_error' ? (
        <p className="gs-field-crop-profile-notice" role="alert">
          {t('website.research.errorGeneric')}
        </p>
      ) : null}

      {mode === 'canonical' || mode === 'limited' ? (
        <FieldCropCanonicalAnswerView profile={profile} episodeId={episodeId} />
      ) : null}

      {showLegacySections ? (
        <>
          {profile.sections.map((section) => (
            <section
              key={section.key}
              className="gs-field-crop-profile-section"
              data-legacy-section="true"
              style={{
                marginBottom: '1rem',
                padding: '1rem',
                border: '1px solid var(--border)',
                borderRadius: '0.85rem',
                background: 'var(--card)',
              }}
            >
              <h3 style={{ margin: '0 0 0.5rem', fontSize: '1rem', fontWeight: 800 }}>{section.title}</h3>
              <div className="gs-field-crop-profile-content">
                {section.content.split('\n').map((paragraph, index) => (
                  <p key={`${section.key}-${index}`}>{paragraph}</p>
                ))}
              </div>
              {section.source ? (
                <p className="gs-field-crop-profile-source">
                  <strong>{t('website.research.sourcesHeading')}:</strong>{' '}
                  {section.source.organization ? `${section.source.organization} — ` : ''}
                  {section.source.title}
                  {section.source.url ? (
                    <>
                      {' '}
                      <a href={section.source.url} target="_blank" rel="noreferrer noopener">
                        {section.source.url}
                      </a>
                    </>
                  ) : null}
                </p>
              ) : null}
            </section>
          ))}

          {showLegacyReferences ? (
            <section className="gs-field-crop-profile-section" aria-labelledby="field-crop-references">
              <h3 id="field-crop-references">{t('website.research.sourcesHeading')}</h3>
              <ul className="gs-field-crop-profile-references">
                {profile.references.map((reference) => (
                  <li key={`${reference.organization}-${reference.url ?? reference.title}`}>
                    {reference.organization ? `${reference.organization} — ` : ''}
                    {reference.title}
                    {reference.year ? ` (${reference.year})` : ''}
                    {reference.url ? (
                      <>
                        {' '}
                        <a href={reference.url} target="_blank" rel="noreferrer noopener">
                          {reference.url}
                        </a>
                      </>
                    ) : null}
                  </li>
                ))}
              </ul>
            </section>
          ) : null}
        </>
      ) : null}
    </article>
  )
}
