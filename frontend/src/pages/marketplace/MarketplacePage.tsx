import { PublicLayout } from '../../public/PublicLayout'
import { PublicMarketSection } from '../../public/PublicMarketSection'
import { internalPaths } from '../../navigation/paths'
import { marketplaceLoginHref, marketplaceRegisterHref } from '../../navigation/roleDestinations'

/** Existing public marketplace listings page. */
export function MarketplacePage() {
  return (
    <PublicLayout
      loginTo={marketplaceLoginHref(internalPaths.products)}
      registerTo={marketplaceRegisterHref(internalPaths.newProduct)}
    >
      <PublicMarketSection />
    </PublicLayout>
  )
}
