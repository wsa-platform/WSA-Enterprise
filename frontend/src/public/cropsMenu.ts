export const CROPS_CATEGORY_ROUTES = {
  field: '/crops/field',
  sugar: '/crops/sugar',
  forage: '/crops/forage',
} as const

export type CropsCategoryId = keyof typeof CROPS_CATEGORY_ROUTES

export type CropsCategoryItem = {
  id: CropsCategoryId
  to: (typeof CROPS_CATEGORY_ROUTES)[CropsCategoryId]
  icon: string
  labelKey: string
}

/** Homepage crops parent category — exactly three submenu destinations. */
export const CROPS_CATEGORY_ITEMS: readonly CropsCategoryItem[] = [
  {
    id: 'field',
    to: CROPS_CATEGORY_ROUTES.field,
    icon: '🌾',
    labelKey: 'website.cropsMenu.field',
  },
  {
    id: 'sugar',
    to: CROPS_CATEGORY_ROUTES.sugar,
    icon: '🍬',
    labelKey: 'website.cropsMenu.sugar',
  },
  {
    id: 'forage',
    to: CROPS_CATEGORY_ROUTES.forage,
    icon: '🌿',
    labelKey: 'website.cropsMenu.forage',
  },
] as const

export type CropsMenuAction =
  | { type: 'pointer_enter' }
  | { type: 'pointer_leave_request' }
  | { type: 'pointer_leave_commit' }
  | { type: 'open' }
  | { type: 'toggle' }
  | { type: 'escape' }
  | { type: 'close' }

/** Pure open/close rules for hover, click/touch, and keyboard. */
export function cropsMenuReducer(open: boolean, action: CropsMenuAction): boolean {
  switch (action.type) {
    case 'pointer_enter':
    case 'open':
      return true
    case 'pointer_leave_request':
      return open
    case 'pointer_leave_commit':
      return false
    case 'toggle':
      return !open
    case 'escape':
    case 'close':
      return false
    default:
      return open
  }
}

export function isCropsCategoryId(value: string | undefined): value is CropsCategoryId {
  return Boolean(value && value in CROPS_CATEGORY_ROUTES)
}

export function cropsCategoryItem(id: CropsCategoryId): CropsCategoryItem {
  const item = CROPS_CATEGORY_ITEMS.find((entry) => entry.id === id)
  if (!item) throw new Error(`Unknown crops category: ${id}`)
  return item
}
