import { beforeEach, describe, expect, it, vi } from 'vitest'
import { composeCommunication, sendCommunication } from './communications'

describe('communications API', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('creates then sends a message', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch')
      .mockResolvedValueOnce(
        new Response(JSON.stringify({ id: 5, subject: 'Hello', body: 'Body', channel: 'email', status: 'draft' }), {
          status: 201,
          headers: { 'Content-Type': 'application/json' },
        }),
      )
      .mockResolvedValueOnce(
        new Response(
          JSON.stringify({
            id: 5,
            subject: 'Hello',
            body: 'Body',
            channel: 'email',
            status: 'sent',
            delivery_stats: { sent: 1, failed: 0, total: 1 },
          }),
          { status: 200, headers: { 'Content-Type': 'application/json' } },
        ),
      )

    const created = await composeCommunication('token-1', {
      subject: 'Hello',
      body: 'Body',
      channel: 'email',
      recipient_mode: 'individual',
      recipients: [{ email: 'buyer@wsa.test' }],
    }, 4)
    const sent = await sendCommunication('token-1', created.id, 4)

    expect(created.id).toBe(5)
    expect(sent.delivery_stats?.sent).toBe(1)
    expect(String(fetchMock.mock.calls[0][0])).toContain('/communications/messages')
    expect(fetchMock.mock.calls[0][1]?.method).toBe('POST')
    expect(String(fetchMock.mock.calls[1][0])).toContain('/communications/messages/5/send')
    expect(JSON.parse(String(fetchMock.mock.calls[0][1]?.body))).toMatchObject({
      subject: 'Hello',
      channel: 'email',
    })
  })
})
