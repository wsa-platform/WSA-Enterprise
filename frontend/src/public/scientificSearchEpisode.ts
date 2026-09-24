import type { ResearchAgentQueryResponse } from '../api/researchAgent'
import { researchViewerPath } from './researchViewer'
import type { PresentedCandidate, PresentedSource, ScientificUserPresentation } from './scientificUserPresentation'
import { toScientificUserPresentation } from './scientificUserPresentation'

export const RESEARCH_EPISODE_STORAGE_PREFIX = 'wsa.research.episode.'
export const RESEARCH_EPISODE_CURRENT_KEY = 'wsa.research.episode.current'

export type ScientificSearchEpisode = {
  episodeId: string
  query: string
  language: string | null
  presentation: ScientificUserPresentation
  response: ResearchAgentQueryResponse
  selectedResultId: string | null
  returnPath: string
  originState?: Record<string, string>
}

function randomEpisodeId(): string {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }
  return `episode-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`
}

function writeJson(key: string, value: unknown): void {
  if (typeof sessionStorage === 'undefined') {
    return
  }
  sessionStorage.setItem(key, JSON.stringify(value))
}

function readJson<T>(key: string): T | null {
  if (typeof sessionStorage === 'undefined') {
    return null
  }
  const raw = sessionStorage.getItem(key)
  if (!raw) {
    return null
  }
  try {
    return JSON.parse(raw) as T
  } catch {
    return null
  }
}

export function homeResultsPath(episodeId: string): string {
  return `/?episode=${encodeURIComponent(episodeId)}#home-research`
}

export function resultsPath(episode: Pick<ScientificSearchEpisode, 'episodeId' | 'returnPath'>): string {
  return episode.returnPath || homeResultsPath(episode.episodeId)
}

export function originStateFromLocation(): Record<string, string> | null {
  if (typeof window === 'undefined') {
    return null
  }
  const episode = loadSearchEpisode(new URLSearchParams(window.location.search).get('episode'))
  const origin = episode?.originState
  if (!origin || typeof origin !== 'object') {
    return null
  }
  return origin
}

export function viewerPathForResult(resultId: string, episodeId: string): string {
  return `${researchViewerPath(resultId)}?episode=${encodeURIComponent(episodeId)}`
}

export function saveSearchEpisode(episode: ScientificSearchEpisode): void {
  writeJson(RESEARCH_EPISODE_STORAGE_PREFIX + episode.episodeId, episode)
  writeJson(RESEARCH_EPISODE_CURRENT_KEY, episode.episodeId)
}

export function loadSearchEpisode(episodeId: string | null | undefined): ScientificSearchEpisode | null {
  if (!episodeId) {
    return null
  }
  const episode = readJson<ScientificSearchEpisode>(RESEARCH_EPISODE_STORAGE_PREFIX + episodeId)
  if (!episode || typeof episode.query !== 'string' || !episode.presentation) {
    return null
  }
  return episode
}

export function loadCurrentSearchEpisode(): ScientificSearchEpisode | null {
  const currentId = readJson<string>(RESEARCH_EPISODE_CURRENT_KEY)
  return loadSearchEpisode(typeof currentId === 'string' ? currentId : null)
}

export function createSearchEpisode(input: {
  query: string
  language?: string | null
  response: ResearchAgentQueryResponse
  episodeId?: string
  returnPath?: string | null
  originState?: Record<string, string>
}): ScientificSearchEpisode {
  const presentation = toScientificUserPresentation(input.response)
  if (!presentation) {
    throw new Error('scientific_presentation_unavailable')
  }
  const episodeId = input.episodeId?.trim() || randomEpisodeId()
  const episode: ScientificSearchEpisode = {
    episodeId,
    query: input.query,
    language: input.language ?? presentation.answer_language ?? null,
    presentation,
    response: input.response,
    selectedResultId: null,
    returnPath: input.returnPath?.trim() || homeResultsPath(episodeId),
    originState: input.originState,
  }
  saveSearchEpisode(episode)
  return episode
}

export function findEpisodeResult(
  episode: ScientificSearchEpisode,
  resultId: string,
): { source?: PresentedSource; candidate?: PresentedCandidate } {
  const source = episode.presentation.sources.find((item) => item.result_id === resultId)
  const candidate = episode.presentation.candidates.find((item) => item.result_id === resultId)
  return { source, candidate }
}
