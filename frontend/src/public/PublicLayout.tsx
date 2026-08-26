import type { ReactNode } from 'react'
import { PublicFooter } from './PublicFooter'
import { PublicHeader } from './PublicHeader'
import { PublicNewsletter } from './PublicNewsletter'

export function PublicLayout({
  children,
  loginTo,
  registerTo,
}: {
  children: ReactNode
  loginTo?: string
  registerTo?: string
}) {
  return (
    <div className="public-site">
      <PublicHeader loginTo={loginTo} registerTo={registerTo} />
      <main id="main-content">{children}</main>
      <PublicNewsletter />
      <PublicFooter />
    </div>
  )
}
