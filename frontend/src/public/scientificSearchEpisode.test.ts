import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  createSearchEpisode,
  findEpisodeResult,
  homeResultsPath,
  loadSearchEpisode,
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

describe('scientific search episode', () => {
  it('restores the same results after viewer navigation', () => {
    const episode = createSearchEpisode({
      query: 'generic literature question',
      language: 'en',
      episodeId: 'ep-1',
      response: {
        status: 'scientific_generated',
        answer: 'Answer A',
        citations: [
          { citation_id: 'src-a', title: 'Source A', url: 'https://example.org/a' },
          { citation_id: 'src-b', title: 'Source B', doi: '10.1000/b' },
        ],
        answer_candidates: [{ result_id: 'cand-b', answer: 'Answer B' }],
      },
    })

    const restored = loadSearchEpisode('ep-1')
    expect(restored?.query).toBe('generic literature question')
    expect(restored?.presentation.primary_answer).toBe('Answer A')
    expect(restored?.presentation.sources).toHaveLength(2)
    expect(findEpisodeResult(episode, 'src-a').source?.title).toBe('Source A')
    expect(findEpisodeResult(episode, 'src-b').source?.original_url).toBe('https://doi.org/10.1000/b')
    expect(homeResultsPath('ep-1')).toBe('/?episode=ep-1#home-research')
    expect(viewerPathForResult('src-a', 'ep-1')).toBe('/research/result/src-a?episode=ep-1')
    expect(viewerPathForResult('src-a', 'ep-1')).not.toMatch(/google/i)
    expect(resultsPath(episode)).toBe('/?episode=ep-1#home-research')
  })

  it('restores the originating crop path instead of Home', () => {
    const episode = createSearchEpisode({
      query: 'crop research',
      language: 'ar',
      episodeId: 'ep-crop',
      returnPath: '/plant-production/field-crops?episode=ep-crop',
      originState: { categoryId: 'grains', cropId: 'wheat', optionId: 'farming-needs' },
      response: {
        status: 'scientific_generated',
        answer: 'Crop answer',
        citations: [{ citation_id: 'cite-crop', title: 'Crop source', url: 'https://example.org/c' }],
      },
    })

    expect(resultsPath(episode)).toBe('/plant-production/field-crops?episode=ep-crop')
    expect(resultsPath(episode)).not.toBe(homeResultsPath('ep-crop'))
    expect(loadSearchEpisode('ep-crop')?.originState?.cropId).toBe('wheat')
  })
})
