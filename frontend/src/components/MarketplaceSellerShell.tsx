import { Outlet } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { PermissionProvider } from '../context/PermissionContext'
import { internalPaths } from '../navigation/paths'
import { marketplaceLoginHref, marketplaceRegisterHref } from '../navigation/roleDestinations'
import { PublicHeader } from '../public/PublicHeader'

export function MarketplaceSellerShell() {
  const { token } = useAuth()

  return (
    <PermissionProvider>
      <div className="public-site seller-shell">
        <PublicHeader
          loginTo={token ? undefined : marketplaceLoginHref(internalPaths.products)}
          registerTo={token ? undefined : marketplaceRegisterHref(internalPaths.products)}
        />
        <main className="gs-container seller-shell-main">
          <Outlet />
        </main>
      </div>
    </PermissionProvider>
  )
}
