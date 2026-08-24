import { Outlet } from 'react-router-dom'
import { PermissionProvider } from '../context/PermissionContext'
import { internalPaths } from '../navigation/paths'
import { marketplaceLoginHref, marketplaceRegisterHref } from '../navigation/roleDestinations'
import { PublicHeader } from '../public/PublicHeader'

export function MarketplaceSellerShell() {
  return (
    <PermissionProvider>
      <div className="public-site seller-shell">
        <PublicHeader
          loginTo={marketplaceLoginHref(internalPaths.products)}
          registerTo={marketplaceRegisterHref(internalPaths.products)}
        />
        <main className="gs-container seller-shell-main">
          <Outlet />
        </main>
      </div>
    </PermissionProvider>
  )
}
