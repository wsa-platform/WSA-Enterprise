import type { ReactNode } from 'react'
import { PublicFooter } from './PublicFooter'
import { PublicHeader } from './PublicHeader'
import { PublicNewsletter } from './PublicNewsletter'
import { PublicQuickLinks } from './PublicQuickLinks'

export function PublicLayout({ children }: { children: ReactNode }) {
  return (
    <div className="public-site">
      <PublicHeader />
      <main id="main-content">{children}</main>
      <PublicNewsletter />
      <PublicQuickLinks />
      <PublicFooter />
    </div>
  )
}
