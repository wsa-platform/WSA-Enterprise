import { defineConfig } from '@playwright/test'

const baseURL = process.env.WSA_E2E_BASE_URL ?? 'http://127.0.0.1:8079'

export default defineConfig({
  testDir: './tests',
  timeout: 180_000,
  expect: { timeout: 30_000 },
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [['list'], ['json', { outputFile: 'artifacts/playwright-report.json' }]],
  use: {
    baseURL,
    trace: 'retain-on-failure',
    screenshot: 'off',
    video: 'off',
    channel: process.env.WSA_E2E_CHANNEL ?? 'msedge',
    headless: process.env.WSA_E2E_HEADED === '1' ? false : true,
    actionTimeout: 30_000,
    navigationTimeout: 60_000,
  },
  outputDir: 'artifacts/test-output',
})
