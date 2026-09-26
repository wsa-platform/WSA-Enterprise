import type { ResearchAgentQueryResponse } from '../api/researchAgent'
import { researchViewerPath } from './researchViewer'
import type {
  PresentedCandidate,
  PresentedResearchResult,
  PresentedSource,
  ScientificUserPresentation,
} from './scientificUserPresentation'
import { toScientificUserPresentation } from './scientificUserPresentation'

export type ResearchEpisodeOwner = 'home' | 'crop'

/** Home Scientific Research persistence. Crop must never write these keys. */
export const HOME_EPISODE_STORAGE_PREFIX = 'wsa.research.home.episode.'
export const HOME_EPISODE_CURRENT_KEY = 'wsa.research.home.episode.current'

/** Crop Research persistence. Home must never write these keys. */
export const CROP_EPISODE_STORAGE_PREFIX = 'wsa.research.crop.episode.'
export const CROP_EPISODE_CURRENT_KEY = 'wsa.research.crop.episode.current'

/** Legacy shared keys — read-only compatibility. Never used as Home current. */
export const RESEARCH_EPISODE_STORAGE_PREFIX = 'wsa.research.episode.'
export const RESEARCH_EPISODE_CURRENT_KEY = 'wsa.research.episode.current'

export type ScientificSearchEpisode = {
  owner: ResearchEpisodeOwner
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

function prefixFor(owner: ResearchEpisodeOwner): string {
  return owner === 'crop' ? CROP_EPISODE_STORAGE_PREFIX : HOME_EPISODE_STORAGE_PREFIX
}

function currentKeyFor(owner: ResearchEpisodeOwner): string {
  return owner === 'crop' ? CROP_EPISODE_CURRENT_KEY : HOME_EPISODE_CURRENT_KEY
}

function hasCropOrigin(originState: Record<string, string> | undefined): boolean {
  if (!originState || typeof originState !== 'object') {
    return false
  }
  return Boolean(
    originState.cropId?.trim()
    || originState.optionId?.trim()
    || originState.categoryId?.trim(),
  )
}

function inferOwner(raw: Partial<ScientificSearchEpisode> | null): ResearchEpisodeOwner | null {
  if (!raw) {
    return null
  }
  if (raw.owner === 'home' || raw.owner === 'crop') {
    return raw.owner
  }
  return hasCropOrigin(raw.originState) ? 'crop' : 'home'
}

function normalizeEpisode(raw: ScientificSearchEpisode | null, fallbackOwner?: ResearchEpisodeOwner): ScientificSearchEpisode | null {
  if (!raw || typeof raw.query !== 'string' || !raw.presentation) {
    return null
  }
  const owner = inferOwner(raw) ?? fallbackOwner
  if (!owner) {
    return null
  }
  return {
    ...raw,
    owner,
    presentation: {
      ...raw.presentation,
      results: raw.presentation.results ?? [],
    },
  }
}

function readOwnedEpisode(owner: ResearchEpisodeOwner, episodeId: string): ScientificSearchEpisode | null {
  return normalizeEpisode(
    readJson<ScientificSearchEpisode>(prefixFor(owner) + episodeId),
    owner,
  )
}

function readLegacyEpisode(episodeId: string): ScientificSearchEpisode | null {
  return normalizeEpisode(readJson<ScientificSearchEpisode>(RESEARCH_EPISODE_STORAGE_PREFIX + episodeId))
}

export function homeResultsPath(episodeId: string): string {
  return `/?episode=${encodeURIComponent(episodeId)}#home-research`
}

export function resultsPath(episode: Pick<ScientificSearchEpisode, 'episodeId' | 'returnPath'>): string {
  return episode.returnPath || homeResultsPath(episode.episodeId)
}

export function viewerPathForResult(resultId: string, episodeId: string): string {
  return `${researchViewerPath(resultId)}?episode=${encodeURIComponent(episodeId)}`
}

export function saveOwnedSearchEpisode(episode: ScientificSearchEpisode): void {
  const owner = episode.owner
  writeJson(prefixFor(owner) + episode.episodeId, episode)
  writeJson(currentKeyFor(owner), episode.episodeId)
}

/** @deprecated Home-only write. Crop must use createCropSearchEpisode. */
export function saveSearchEpisode(episode: ScientificSearchEpisode): void {
  saveOwnedSearchEpisode({ ...episode, owner: episode.owner === 'crop' ? 'crop' : 'home' })
}

export function loadHomeSearchEpisode(episodeId: string | null | undefined): ScientificSearchEpisode | null {
  if (!episodeId) {
    return null
  }
  const owned = readOwnedEpisode('home', episodeId)
  if (owned?.owner === 'home') {
    return owned
  }
  const legacy = readLegacyEpisode(episodeId)
  return legacy?.owner === 'home' ? legacy : null
}

export function loadCropSearchEpisode(episodeId: string | null | undefined): ScientificSearchEpisode | null {
  if (!episodeId) {
    return null
  }
  const owned = readOwnedEpisode('crop', episodeId)
  if (owned?.owner === 'crop') {
    return owned
  }
  const legacy = readLegacyEpisode(episodeId)
  return legacy?.owner === 'crop' ? legacy : null
}

/** Viewer lookup across both contexts. Does not touch current pointers. */
export function loadSearchEpisode(episodeId: string | null | undefined): ScientificSearchEpisode | null {
  return loadHomeSearchEpisode(episodeId) ?? loadCropSearchEpisode(episodeId)
}

export function loadCurrentHomeSearchEpisode(): ScientificSearchEpisode | null {
  const currentId = readJson<string>(HOME_EPISODE_CURRENT_KEY)
  return loadHomeSearchEpisode(typeof currentId === 'string' ? currentId : null)
}

export function loadCurrentCropSearchEpisode(): ScientificSearchEpisode | null {
  const currentId = readJson<string>(CROP_EPISODE_CURRENT_KEY)
  return loadCropSearchEpisode(typeof currentId === 'string' ? currentId : null)
}

/** Home current only. Never reads the legacy shared current pointer. */
export function loadCurrentSearchEpisode(): ScientificSearchEpisode | null {
  return loadCurrentHomeSearchEpisode()
}

export function resolveHomeRestoredEpisode(requestedEpisodeId: string | null | undefined): ScientificSearchEpisode | null {
  if (requestedEpisodeId) {
    return loadHomeSearchEpisode(requestedEpisodeId)
  }
  return loadCurrentHomeSearchEpisode()
}

export function matchCropStoredEpisode(
  episodeId: string | null | undefined,
  categoryId: string,
  cropId: string,
  knowledgeOption: string,
): ScientificSearchEpisode | null {
  const episode = episodeId
    ? loadCropSearchEpisode(episodeId)
    : loadCurrentCropSearchEpisode()
  const origin = episode?.originState
  if (!episode || episode.owner !== 'crop' || !origin) {
    return null
  }
  if (origin.categoryId !== categoryId || origin.cropId !== cropId || origin.optionId !== knowledgeOption) {
    return null
  }
  return episode
}

export function originStateFromLocation(): Record<string, string> | null {
  if (typeof window === 'undefined') {
    return null
  }
  const episodeId = new URLSearchParams(window.location.search).get('episode')
  const episode = loadCropSearchEpisode(episodeId) ?? loadCurrentCropSearchEpisode()
  const origin = episode?.originState
  if (!origin || typeof origin !== 'object') {
    return null
  }
  return origin
}

function buildEpisode(input: {
  owner: ResearchEpisodeOwner
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
  const defaultReturn = input.owner === 'crop'
    ? `/plant-production/field-crops?episode=${encodeURIComponent(episodeId)}`
    : homeResultsPath(episodeId)
  return {
    owner: input.owner,
    episodeId,
    query: input.query,
    language: input.language ?? presentation.answer_language ?? null,
    presentation,
    response: input.response,
    selectedResultId: null,
    returnPath: input.returnPath?.trim() || defaultReturn,
    originState: input.originState,
  }
}

/** Home Scientific Research episode. Writes only the Home store. */
export function createSearchEpisode(input: {
  query: string
  language?: string | null
  response: ResearchAgentQueryResponse
  episodeId?: string
  returnPath?: string | null
  originState?: Record<string, string>
}): ScientificSearchEpisode {
  const episode = buildEpisode({ ...input, owner: 'home' })
  saveOwnedSearchEpisode(episode)
  return episode
}

/** Crop Research episode. Writes only the Crop store. */
export function createCropSearchEpisode(input: {
  query: string
  language?: string | null
  response: ResearchAgentQueryResponse
  episodeId?: string
  returnPath?: string | null
  originState?: Record<string, string>
}): ScientificSearchEpisode {
  const episode = buildEpisode({ ...input, owner: 'crop' })
  saveOwnedSearchEpisode(episode)
  return episode
}

export function findEpisodeResult(
  episode: ScientificSearchEpisode,
  resultId: string,
): { result?: PresentedResearchResult; source?: PresentedSource; candidate?: PresentedCandidate } {
  const result = episode.presentation.results.find((item) => item.result_id === resultId)
  const source = result ?? episode.presentation.sources.find((item) => item.result_id === resultId)
  const candidate = episode.presentation.candidates.find((item) => item.result_id === resultId)
  return { result, source, candidate }
}
