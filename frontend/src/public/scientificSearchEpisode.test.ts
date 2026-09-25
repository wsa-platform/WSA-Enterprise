import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { ResearchAgentQueryResponse } from '../api/researchAgent'
import {
  CROP_EPISODE_CURRENT_KEY,
  CROP_EPISODE_STORAGE_PREFIX,
  HOME_EPISODE_CURRENT_KEY,
  HOME_EPISODE_STORAGE_PREFIX,
  RESEARCH_EPISODE_CURRENT_KEY,
  RESEARCH_EPISODE_STORAGE_PREFIX,
  createCropSearchEpisode,
  createSearchEpisode,
  findEpisodeResult,
  homeResultsPath,
  loadCurrentCropSearchEpisode,
  loadCurrentHomeSearchEpisode,
  loadCurrentSearchEpisode,
  loadCropSearchEpisode,
  loadHomeSearchEpisode,
  loadSearchEpisode,
  matchCropStoredEpisode,
  resolveHomeRestoredEpisode,
  resultsPath,
  viewerPathForResult,
} from './scientificSearchEpisode'

const session = new Map<string, string>()

beforeEach(() => {
  session.clear()
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

function homeResponse(answer: string): ResearchAgentQueryResponse {
  return {
    status: 'scientific_generated',
    answer,
    user_presentation: {
      primary_answer: answer,
      human_status: 'answered',
      user_notice_code: null,
      candidates: [{ result_id: 'cand-b', answer: 'Answer B' }],
      sources: [
        { result_id: 'src-a', title: 'Source A', original_url: 'https://example.org/a' },
        { result_id: 'src-b', title: 'Source B', original_url: 'https://doi.org/10.1000/b' },
      ],
    },
    citations: [
      { citation_id: 'src-a', title: 'Source A', url: 'https://example.org/a' },
      { citation_id: 'src-b', title: 'Source B', doi: '10.1000/b' },
    ],
    answer_candidates: [{ result_id: 'cand-b', answer: 'Answer B' }],
  }
}

function cropResponse(answer: string): ResearchAgentQueryResponse {
  return {
    status: 'scientific_generated',
    answer,
    user_presentation: {
      primary_answer: answer,
      human_status: 'answered',
      user_notice_code: null,
      candidates: [],
      sources: [],
    },
    citations: [{ citation_id: 'cite-crop', title: 'Crop source', url: 'https://example.org/c' }],
  }
}

describe('scientific search episode', () => {
  it('TEST 1 / 10: Home Search result restores only in the Home context', () => {
    const episode = createSearchEpisode({
      query: 'generic literature question',
      language: 'en',
      episodeId: 'ep-1',
      response: homeResponse('Answer A'),
    })

    const restored = loadSearchEpisode('ep-1')
    expect(episode.owner).toBe('home')
    expect(restored?.query).toBe('generic literature question')
    expect(restored?.presentation.primary_answer).toBe('Answer A')
    expect(restored?.presentation.sources).toHaveLength(2)
    expect(findEpisodeResult(episode, 'src-a').source?.title).toBe('Source A')
    expect(findEpisodeResult(episode, 'src-b').source?.original_url).toBe('https://doi.org/10.1000/b')
    expect(homeResultsPath('ep-1')).toBe('/?episode=ep-1#home-research')
    expect(viewerPathForResult('src-a', 'ep-1')).toBe('/research/result/src-a?episode=ep-1')
    expect(viewerPathForResult('src-a', 'ep-1')).not.toMatch(/google/i)
    expect(resultsPath(episode)).toBe('/?episode=ep-1#home-research')
    expect(loadHomeSearchEpisode('ep-1')?.owner).toBe('home')
    expect(loadCropSearchEpisode('ep-1')).toBeNull()
    expect(resolveHomeRestoredEpisode('ep-1')?.presentation.primary_answer).toBe('Answer A')
    expect(session.get(HOME_EPISODE_CURRENT_KEY)).toBe('"ep-1"')
    expect(session.get(CROP_EPISODE_CURRENT_KEY)).toBeUndefined()
  })

  it('TEST 2 / 11 / 12: Crop Research result stays in Crop context and Viewer returns to Crop', () => {
    const episode = createCropSearchEpisode({
      query: 'crop research',
      language: 'ar',
      episodeId: 'ep-crop',
      returnPath: '/plant-production/field-crops?episode=ep-crop',
      originState: { categoryId: 'grains', cropId: 'wheat', optionId: 'farming-needs' },
      response: cropResponse('Crop answer'),
    })

    expect(episode.owner).toBe('crop')
    expect(resultsPath(episode)).toBe('/plant-production/field-crops?episode=ep-crop')
    expect(resultsPath(episode)).not.toBe(homeResultsPath('ep-crop'))
    expect(loadSearchEpisode('ep-crop')?.originState?.cropId).toBe('wheat')
    expect(matchCropStoredEpisode('ep-crop', 'grains', 'wheat', 'farming-needs')?.presentation.primary_answer)
      .toBe('Crop answer')
    expect(session.get(CROP_EPISODE_CURRENT_KEY)).toBe('"ep-crop"')
    expect(session.get(HOME_EPISODE_CURRENT_KEY)).toBeUndefined()
  })

  it('TEST 3 / 8: Crop Research result never becomes Home current or Home restore', () => {
    createCropSearchEpisode({
      query: 'القمح scientific-research',
      episodeId: 'ep-crop-current',
      originState: { categoryId: 'grains', cropId: 'wheat', optionId: 'scientific-research' },
      response: cropResponse('Crop-only answer'),
    })

    expect(loadCurrentHomeSearchEpisode()).toBeNull()
    expect(loadCurrentSearchEpisode()).toBeNull()
    expect(resolveHomeRestoredEpisode(null)).toBeNull()
    expect(loadHomeSearchEpisode('ep-crop-current')).toBeNull()
    expect(loadCurrentCropSearchEpisode()?.presentation.primary_answer).toBe('Crop-only answer')
  })

  it('TEST 4: Home Search result never becomes Crop current or Crop restore', () => {
    createSearchEpisode({
      query: 'free text scientific question',
      episodeId: 'ep-home-only',
      response: homeResponse('Home-only answer'),
    })

    expect(loadCurrentCropSearchEpisode()).toBeNull()
    expect(loadCropSearchEpisode('ep-home-only')).toBeNull()
    expect(matchCropStoredEpisode('ep-home-only', 'grains', 'wheat', 'farming-needs')).toBeNull()
    expect(loadCurrentHomeSearchEpisode()?.presentation.primary_answer).toBe('Home-only answer')
  })

  it.each([
    ['farming-needs'],
    ['scientific-research'],
    ['industries'],
  ] as const)('TEST 5-7: Crop %s remains a Crop result', (optionId) => {
    createCropSearchEpisode({
      query: `generic-crop ${optionId}`,
      episodeId: `ep-${optionId}`,
      originState: { categoryId: 'grains', cropId: 'barley', optionId },
      response: cropResponse(`Answer for ${optionId}`),
    })

    const matched = matchCropStoredEpisode(`ep-${optionId}`, 'grains', 'barley', optionId)
    expect(matched?.owner).toBe('crop')
    expect(matched?.originState?.optionId).toBe(optionId)
    expect(resolveHomeRestoredEpisode(`ep-${optionId}`)).toBeNull()
    expect(loadHomeSearchEpisode(`ep-${optionId}`)).toBeNull()
  })

  it('TEST 9: Home with ?episode=<crop episode> ignores Crop and does not fall back to it', () => {
    createCropSearchEpisode({
      query: 'crop industries',
      episodeId: 'ep-crop-url',
      originState: { categoryId: 'grains', cropId: 'rice', optionId: 'industries' },
      response: cropResponse('Must stay off Home'),
    })

    expect(resolveHomeRestoredEpisode('ep-crop-url')).toBeNull()
    expect(loadHomeSearchEpisode('ep-crop-url')).toBeNull()
  })

  it('does not let a legacy shared current pointer restore a Crop result into Home', () => {
    const legacyCrop = createCropSearchEpisode({
      query: 'legacy crop',
      episodeId: 'legacy-crop',
      originState: { categoryId: 'grains', cropId: 'corn', optionId: 'farming-needs' },
      response: cropResponse('Legacy crop answer'),
    })
    session.set(RESEARCH_EPISODE_STORAGE_PREFIX + 'legacy-crop', JSON.stringify({
      ...legacyCrop,
      owner: undefined,
    }))
    session.set(RESEARCH_EPISODE_CURRENT_KEY, JSON.stringify('legacy-crop'))

    expect(resolveHomeRestoredEpisode(null)).toBeNull()
    expect(resolveHomeRestoredEpisode('legacy-crop')).toBeNull()
    expect(loadCropSearchEpisode('legacy-crop')?.owner).toBe('crop')
  })

  it('migrates a legacy Home episode without treating it as Crop', () => {
    session.set(RESEARCH_EPISODE_STORAGE_PREFIX + 'legacy-home', JSON.stringify({
      episodeId: 'legacy-home',
      query: 'old home question',
      language: 'en',
      presentation: {
        primary_answer: 'Legacy home answer',
        human_status: 'answered',
        user_notice_code: null,
        candidates: [],
        sources: [],
      },
      response: homeResponse('Legacy home answer'),
      selectedResultId: null,
      returnPath: '/?episode=legacy-home#home-research',
    }))

    expect(loadHomeSearchEpisode('legacy-home')?.presentation.primary_answer).toBe('Legacy home answer')
    expect(loadCropSearchEpisode('legacy-home')).toBeNull()
    expect(session.get(CROP_EPISODE_STORAGE_PREFIX + 'legacy-home')).toBeUndefined()
  })

  it('keeps Home and Crop currents isolated when both exist', () => {
    createSearchEpisode({
      query: 'home q',
      episodeId: 'home-a',
      response: homeResponse('Home A'),
    })
    createCropSearchEpisode({
      query: 'crop q',
      episodeId: 'crop-a',
      originState: { categoryId: 'grains', cropId: 'wheat', optionId: 'industries' },
      response: cropResponse('Crop A'),
    })

    expect(resolveHomeRestoredEpisode(null)?.episodeId).toBe('home-a')
    expect(matchCropStoredEpisode(null, 'grains', 'wheat', 'industries')?.episodeId).toBe('crop-a')
    expect(resolveHomeRestoredEpisode('crop-a')).toBeNull()
    expect(matchCropStoredEpisode('home-a', 'grains', 'wheat', 'industries')).toBeNull()
    expect(session.get(HOME_EPISODE_CURRENT_KEY)).toBe('"home-a"')
    expect(session.get(CROP_EPISODE_CURRENT_KEY)).toBe('"crop-a"')
  })
})
