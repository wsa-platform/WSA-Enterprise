import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { PermissionProvider } from '../context/PermissionContext'
import { marketplaceLoginHref } from '../navigation/roleDestinations'
import { PublicHeader } from '../public/PublicHeader'

export function MarketplaceSellerShell() {
  const { token } = useAuth()
  const location = useLocation()

  if (!token) {
    return <Navigate to={marketplaceLoginHref(`${location.pathname}${location.search}`)} replace />
  }

  return (
    <PermissionProvider>
      <div className="public-site seller-shell">
        <PublicHeader />
        <main className="gs-container seller-shell-main">
          <Outlet />
        </main>
      </div>
    </PermissionProvider>
  )
}
