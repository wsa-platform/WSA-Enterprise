export * from './types'
export * from './client'
export * from './auth'
export * from './dashboard'
export * from './organizations'
export * from './users'
export * from './ai'
export * from './modules'

// Backward-compatible aliases used by existing pages.
export { unwrapModuleRows, modulePaginationMeta } from './client'
