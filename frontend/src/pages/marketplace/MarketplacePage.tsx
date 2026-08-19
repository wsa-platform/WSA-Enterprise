import { PublicLayout } from '../../public/PublicLayout'
import { PublicMarketSection } from '../../public/PublicMarketSection'

/** Standalone browse page kept for reuse; public `/market` redirects to the home section. */
export function MarketplacePage() {
  return (
    <PublicLayout>
      <PublicMarketSection />
    </PublicLayout>
  )
}
