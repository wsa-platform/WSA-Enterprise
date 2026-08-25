import { Link } from 'react-router-dom'
import type { PublicListing } from '../api/marketplace'
import { publicPaths } from '../navigation/paths'
import { productNameFromListing } from './units'

export function ProductCard({ listing }: { listing: PublicListing }) {
  const name = productNameFromListing(listing)

  return (
    <Link to={publicPaths.listing(listing.id)} className="gs-product-card gs-market-name-card">
      <h3 dir="auto">{name}</h3>
    </Link>
  )
}
