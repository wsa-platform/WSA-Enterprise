export type PublicSectionId =
  | 'field-crops'
  | 'vegetables'
  | 'fruit-trees'
  | 'medicinal-plants'
  | 'ornamental-plants'
  | 'beekeeping'
  | 'hydroponic-aquaculture'
  | 'training'
  | 'jobs'
  | 'small-projects'
  | 'store'

export type PlantInspectionContext =
  | 'field-crops'
  | 'vegetables'
  | 'fruit-trees'
  | 'medicinal-plants'
  | 'ornamental-plants'

export type PublicSectionConfig = {
  id: PublicSectionId
  titleKey: string
  descriptionKey: string
  accent: string
  /** Garden Store category icon background — oklch from garden-store/client/src/lib/data.ts */
  iconBg: string
  icon: string
  image: string
  imageAltKey: string
  featureKeys: string[]
  highlightKeys: string[]
  catalogModule?: 'training' | 'library' | 'jobs' | 'business'
}

export const PUBLIC_SECTIONS: PublicSectionConfig[] = [
  {
    id: 'field-crops',
    titleKey: 'website.sections.fieldCrops.title',
    descriptionKey: 'website.sections.fieldCrops.description',
    accent: 'oklch(0.35 0.12 145)',
    iconBg: 'oklch(0.88 0.06 90)',
    icon: '🌾',
    image: 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1400&q=80',
    imageAltKey: 'website.sections.fieldCrops.imageAlt',
    featureKeys: [
      'website.sections.fieldCrops.features.planning',
      'website.sections.fieldCrops.features.monitoring',
      'website.sections.fieldCrops.features.diseasePests',
      'website.sections.fieldCrops.features.recommendations',
    ],
    highlightKeys: [
      'website.highlights.fieldCrops.management',
      'website.highlights.fieldCrops.production',
    ],
  },
  {
    id: 'vegetables',
    titleKey: 'website.sections.vegetables.title',
    descriptionKey: 'website.sections.vegetables.description',
    accent: 'oklch(0.35 0.12 145)',
    iconBg: 'oklch(0.88 0.06 145)',
    icon: '🥬',
    image: 'https://images.unsplash.com/photo-1592419044706-3975720a8749?auto=format&fit=crop&w=1400&q=80',
    imageAltKey: 'website.sections.vegetables.imageAlt',
    featureKeys: [
      'website.sections.vegetables.features.production',
      'website.sections.vegetables.features.management',
      'website.sections.vegetables.features.diseasePests',
      'website.sections.vegetables.features.recommendations',
    ],
    highlightKeys: [
      'website.highlights.vegetables.crops',
      'website.highlights.vegetables.greenhouse',
    ],
  },
  {
    id: 'fruit-trees',
    titleKey: 'website.sections.fruitTrees.title',
    descriptionKey: 'website.sections.fruitTrees.description',
    accent: 'oklch(0.35 0.12 145)',
    iconBg: 'oklch(0.88 0.06 145)',
    icon: '🍎',
    image: 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?auto=format&fit=crop&w=1400&q=80',
    imageAltKey: 'website.sections.fruitTrees.imageAlt',
    featureKeys: [
      'website.sections.fruitTrees.features.cultivation',
      'website.sections.fruitTrees.features.orchards',
      'website.sections.fruitTrees.features.diseasePests',
      'website.sections.fruitTrees.features.nutrition',
    ],
    highlightKeys: [
      'website.highlights.fruitTrees.pruning',
      'website.highlights.fruitTrees.recommendations',
    ],
  },
  {
    id: 'medicinal-plants',
    titleKey: 'website.sections.medicinalPlants.title',
    descriptionKey: 'website.sections.medicinalPlants.description',
    accent: 'oklch(0.35 0.12 145)',
    iconBg: 'oklch(0.88 0.06 145)',
    icon: '🌿',
    image: 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=1400&q=80',
    imageAltKey: 'website.sections.medicinalPlants.imageAlt',
    featureKeys: [
      'website.sections.medicinalPlants.features.cultivation',
      'website.sections.medicinalPlants.features.identification',
      'website.sections.medicinalPlants.features.health',
      'website.sections.medicinalPlants.features.uses',
    ],
    highlightKeys: [
      'website.highlights.medicinalPlants.diseasePests',
      'website.highlights.medicinalPlants.quality',
    ],
  },
  {
    id: 'ornamental-plants',
    titleKey: 'website.sections.ornamentalPlants.title',
    descriptionKey: 'website.sections.ornamentalPlants.description',
    accent: 'oklch(0.35 0.12 145)',
    iconBg: 'oklch(0.88 0.08 350)',
    icon: '🌸',
    image: 'https://images.unsplash.com/photo-1455582916367-25f75bfc6710?auto=format&fit=crop&w=1400&q=80',
    imageAltKey: 'website.sections.ornamentalPlants.imageAlt',
    featureKeys: [
      'website.sections.ornamentalPlants.features.indoor',
      'website.sections.ornamentalPlants.features.outdoor',
      'website.sections.ornamentalPlants.features.flowering',
      'website.sections.ornamentalPlants.features.garden',
    ],
    highlightKeys: [
      'website.highlights.ornamentalPlants.shrubs',
      'website.highlights.ornamentalPlants.decorative',
    ],
    catalogModule: 'business',
  },
  {
    id: 'beekeeping',
    titleKey: 'website.sections.beekeeping.title',
    descriptionKey: 'website.sections.beekeeping.description',
    accent: 'oklch(0.35 0.12 145)',
    iconBg: 'oklch(0.88 0.08 85)',
    icon: '🐝',
    image: 'https://images.unsplash.com/photo-1587049351697-f2520d0d5687?auto=format&fit=crop&w=1400&q=80',
    imageAltKey: 'website.sections.beekeeping.imageAlt',
    featureKeys: [
      'website.sections.beekeeping.features.apiaries',
      'website.sections.beekeeping.features.hiveManagement',
      'website.sections.beekeeping.features.diseases',
      'website.sections.beekeeping.features.products',
    ],
    highlightKeys: [
      'website.highlights.beekeeping.honey',
      'website.highlights.beekeeping.training',
    ],
    catalogModule: 'library',
  },
  {
    id: 'hydroponic-aquaculture',
    titleKey: 'website.sections.hydroponicAquaculture.title',
    descriptionKey: 'website.sections.hydroponicAquaculture.description',
    accent: 'oklch(0.35 0.12 145)',
    iconBg: 'oklch(0.88 0.08 220)',
    icon: '💧',
    image: 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?auto=format&fit=crop&w=1400&q=80',
    imageAltKey: 'website.sections.hydroponicAquaculture.imageAlt',
    featureKeys: [
      'website.sections.hydroponicAquaculture.features.hydroponic',
      'website.sections.hydroponicAquaculture.features.aquaculture',
      'website.sections.hydroponicAquaculture.features.sustainable',
      'website.sections.hydroponicAquaculture.features.modern',
    ],
    highlightKeys: [
      'website.highlights.hydroponicAquaculture.water',
      'website.highlights.hydroponicAquaculture.greenTech',
    ],
    catalogModule: 'library',
  },
  {
    id: 'training',
    titleKey: 'website.sections.training.title',
    descriptionKey: 'website.sections.training.description',
    accent: 'oklch(0.35 0.12 145)',
    iconBg: 'oklch(0.88 0.05 60)',
    icon: '📚',
    image: 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=1400&q=80',
    imageAltKey: 'website.sections.training.imageAlt',
    featureKeys: [
      'website.sections.training.features.lectures',
      'website.sections.training.features.courses',
      'website.sections.training.features.programs',
      'website.sections.training.features.education',
    ],
    highlightKeys: [
      'website.highlights.training.beekeeping',
      'website.highlights.training.agriculture',
    ],
    catalogModule: 'training',
  },
  {
    id: 'jobs',
    titleKey: 'website.sections.jobs.title',
    descriptionKey: 'website.sections.jobs.description',
    accent: 'oklch(0.35 0.12 145)',
    iconBg: 'oklch(0.88 0.06 145)',
    icon: '👨‍🌾',
    image: 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?auto=format&fit=crop&w=1400&q=80',
    imageAltKey: 'website.sections.jobs.imageAlt',
    featureKeys: [],
    highlightKeys: [],
    catalogModule: 'jobs',
  },
  {
    id: 'small-projects',
    titleKey: 'website.sections.smallProjects.title',
    descriptionKey: 'website.sections.smallProjects.description',
    accent: 'oklch(0.35 0.12 145)',
    iconBg: 'oklch(0.88 0.06 30)',
    icon: '🏡',
    image: 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?auto=format&fit=crop&w=1400&q=80',
    imageAltKey: 'website.sections.smallProjects.imageAlt',
    featureKeys: [
      'website.sections.smallProjects.features.homeFarming',
      'website.sections.smallProjects.features.greenhouse',
      'website.sections.smallProjects.features.beekeeping',
      'website.sections.smallProjects.features.business',
    ],
    highlightKeys: [
      'website.highlights.smallProjects.ideas',
      'website.highlights.smallProjects.feasibility',
    ],
    catalogModule: 'library',
  },
  {
    id: 'store',
    titleKey: 'website.sections.store.title',
    descriptionKey: 'website.sections.store.description',
    accent: 'oklch(0.35 0.12 145)',
    iconBg: 'oklch(0.88 0.06 145)',
    icon: '🛒',
    image: 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1400&q=80',
    imageAltKey: 'website.sections.store.imageAlt',
    featureKeys: [
      'website.sections.store.features.products',
      'website.sections.store.features.supplies',
      'website.sections.store.features.equipment',
      'website.sections.store.features.inputs',
    ],
    highlightKeys: [
      'website.highlights.store.catalog',
      'website.highlights.store.organic',
    ],
    catalogModule: 'business',
  },
]

/** Homepage-only tile; opens the existing public `/market` listings page. */
export const HOME_MARKETPLACE_TILE = {
  titleKey: 'website.sections.productMarket.title',
  descriptionKey: 'website.sections.productMarket.description',
  icon: '🛒',
  iconBg: 'oklch(0.88 0.06 145)',
  to: '/market',
} as const

export const LEGACY_SECTION_REDIRECTS: Record<string, PublicSectionId> = {
  'vegetable-crops': 'vegetables',
  'honey-bees': 'beekeeping',
}

export function getSectionById(id: string | undefined): PublicSectionConfig | undefined {
  if (!id) return undefined
  const normalized = LEGACY_SECTION_REDIRECTS[id] ?? id
  return PUBLIC_SECTIONS.find((section) => section.id === normalized)
}

/** Homepage hero — approved agricultural landscape background. */
export const HERO_IMAGE = '/assets/wsa/hero-background.jpg'

/** Hero producer join card — same visual as the sidebar producer panel. */
export const PRODUCER_CARD_IMAGE =
  'https://images.unsplash.com/photo-1464226184884-fa280b87c399?auto=format&fit=crop&w=900&q=80'

/** Homepage sidebar — approved seller promo card (Arabic artwork). */
export const SELLER_PROMO_CARD_IMAGE = '/assets/wsa/seller-promo-card.png'

export const HIDDEN_PUBLIC_MODULES = new Set(['soil', 'diagnosis', 'ai'])

export const SECTION_MODULE_MAP: Record<PublicSectionId, string[]> = {
  'field-crops': ['farm', 'crop'],
  vegetables: ['crop', 'library'],
  'fruit-trees': ['farm', 'crop'],
  'medicinal-plants': ['library'],
  'ornamental-plants': ['library', 'business'],
  beekeeping: ['beekeeping', 'library'],
  'hydroponic-aquaculture': ['crop', 'library'],
  training: ['training', 'library'],
  jobs: ['jobs'],
  'small-projects': ['library', 'business'],
  store: ['business'],
}

export const MODULE_DESCRIPTION_KEYS: Record<string, string> = {
  farm: 'website.services.farmRecordsDesc',
  crop: 'website.services.cropPlanningDesc',
  training: 'website.services.trainingDesc',
  library: 'website.services.libraryGuidesDesc',
  beekeeping: 'website.services.beekeepingDesc',
  business: 'website.services.storeCatalogDesc',
  jobs: 'website.services.jobsMarketplaceDesc',
}

export const FEATURED_LINKS: Array<{ sectionId: PublicSectionId; highlightKey: string }> = [
  { sectionId: 'store', highlightKey: 'website.featured.store' },
  { sectionId: 'ornamental-plants', highlightKey: 'website.featured.ornamental' },
  { sectionId: 'training', highlightKey: 'website.featured.training' },
]
