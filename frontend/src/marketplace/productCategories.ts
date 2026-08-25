export const PRODUCT_CATEGORY_SLUGS = [
  'grains',
  'vegetables',
  'fruits',
  'legumes',
  'agricultural-crops',
  'seeds',
  'seedlings',
  'plants',
  'feed',
  'fertilizers',
  'pesticides',
  'seeds-seedlings',
  'animal-products',
  'food-products',
  'foodstuffs',
  'dairy-products',
  'meat',
  'poultry',
  'meat-poultry',
  'fish-seafood',
  'honey-bee-products',
  'oils',
  'dates',
  'herbs-spices',
  'processed-food',
  'food-beverages',
  'agricultural-equipment',
  'agricultural-supplies',
  'livestock-supplies',
  'beekeeping-supplies',
  'aquaculture-supplies',
  'other',
] as const

export type ProductCategorySlug = (typeof PRODUCT_CATEGORY_SLUGS)[number]

type LocalizedLabel = { ar: string; en: string; tr: string; fr: string }

export const PRODUCT_CATEGORIES: { slug: ProductCategorySlug; labels: LocalizedLabel }[] = [
  { slug: 'grains', labels: { ar: 'الحبوب', en: 'Grains', tr: 'Tahıllar', fr: 'Céréales' } },
  { slug: 'vegetables', labels: { ar: 'الخضروات', en: 'Vegetables', tr: 'Sebzeler', fr: 'Légumes' } },
  { slug: 'fruits', labels: { ar: 'الفاكهة', en: 'Fruits', tr: 'Meyveler', fr: 'Fruits' } },
  { slug: 'legumes', labels: { ar: 'البقوليات', en: 'Legumes', tr: 'Baklagiller', fr: 'Légumineuses' } },
  { slug: 'agricultural-crops', labels: { ar: 'المحاصيل الزراعية', en: 'Agricultural crops', tr: 'Tarımsal ürünler', fr: 'Cultures agricoles' } },
  { slug: 'seeds', labels: { ar: 'البذور', en: 'Seeds', tr: 'Tohumlar', fr: 'Semences' } },
  { slug: 'seedlings', labels: { ar: 'الشتلات', en: 'Seedlings', tr: 'Fideler', fr: 'Plants' } },
  { slug: 'plants', labels: { ar: 'النباتات', en: 'Plants', tr: 'Bitkiler', fr: 'Plantes' } },
  { slug: 'feed', labels: { ar: 'الأعلاف', en: 'Feed', tr: 'Yem', fr: 'Aliments pour animaux' } },
  { slug: 'fertilizers', labels: { ar: 'الأسمدة والمخصبات', en: 'Fertilizers and nutrients', tr: 'Gübreler', fr: 'Engrais et nutriments' } },
  { slug: 'pesticides', labels: { ar: 'المبيدات والمواد الزراعية', en: 'Pesticides and agricultural materials', tr: 'Tarım ilaçları', fr: 'Pesticides et produits agricoles' } },
  { slug: 'seeds-seedlings', labels: { ar: 'البذور والشتلات', en: 'Seeds and seedlings', tr: 'Tohumlar ve fideler', fr: 'Semences et plants' } },
  { slug: 'animal-products', labels: { ar: 'المنتجات الحيوانية', en: 'Animal products', tr: 'Hayvansal ürünler', fr: 'Produits animaux' } },
  { slug: 'food-products', labels: { ar: 'المنتجات الغذائية', en: 'Food products', tr: 'Gıda ürünleri', fr: 'Produits alimentaires' } },
  { slug: 'foodstuffs', labels: { ar: 'المواد الغذائية', en: 'Foodstuffs', tr: 'Gıda maddeleri', fr: 'Denrées alimentaires' } },
  { slug: 'dairy-products', labels: { ar: 'منتجات الألبان', en: 'Dairy products', tr: 'Süt ürünleri', fr: 'Produits laitiers' } },
  { slug: 'meat', labels: { ar: 'اللحوم', en: 'Meat', tr: 'Et', fr: 'Viandes' } },
  { slug: 'poultry', labels: { ar: 'الدواجن', en: 'Poultry', tr: 'Kümes hayvanları', fr: 'Volailles' } },
  { slug: 'meat-poultry', labels: { ar: 'اللحوم والدواجن', en: 'Meat and poultry', tr: 'Et ve kümes hayvanları', fr: 'Viandes et volailles' } },
  { slug: 'fish-seafood', labels: { ar: 'الأسماك والمأكولات البحرية', en: 'Fish and seafood', tr: 'Balık ve deniz ürünleri', fr: 'Poissons et fruits de mer' } },
  { slug: 'honey-bee-products', labels: { ar: 'العسل ومنتجات النحل', en: 'Honey and bee products', tr: 'Bal ve arı ürünleri', fr: 'Miel et produits de la ruche' } },
  { slug: 'oils', labels: { ar: 'الزيوت', en: 'Oils', tr: 'Yağlar', fr: 'Huiles' } },
  { slug: 'dates', labels: { ar: 'التمور', en: 'Dates', tr: 'Hurma', fr: 'Dattes' } },
  { slug: 'herbs-spices', labels: { ar: 'الأعشاب والتوابل', en: 'Herbs and spices', tr: 'Otlar ve baharatlar', fr: 'Herbes et épices' } },
  { slug: 'processed-food', labels: { ar: 'المنتجات الزراعية المصنعة', en: 'Processed agricultural products', tr: 'İşlenmiş tarım ürünleri', fr: 'Produits agricoles transformés' } },
  { slug: 'food-beverages', labels: { ar: 'الأغذية والمشروبات', en: 'Food and beverages', tr: 'Gıda ve içecekler', fr: 'Aliments et boissons' } },
  { slug: 'agricultural-equipment', labels: { ar: 'المعدات الزراعية', en: 'Agricultural equipment', tr: 'Tarım ekipmanları', fr: 'Équipements agricoles' } },
  { slug: 'agricultural-supplies', labels: { ar: 'مستلزمات الزراعة', en: 'Agricultural supplies', tr: 'Tarım malzemeleri', fr: 'Fournitures agricoles' } },
  { slug: 'livestock-supplies', labels: { ar: 'مستلزمات الثروة الحيوانية', en: 'Livestock supplies', tr: 'Hayvancılık malzemeleri', fr: 'Fournitures d’élevage' } },
  { slug: 'beekeeping-supplies', labels: { ar: 'مستلزمات تربية النحل', en: 'Beekeeping supplies', tr: 'Arıcılık malzemeleri', fr: 'Fournitures apicoles' } },
  { slug: 'aquaculture-supplies', labels: { ar: 'مستلزمات الاستزراع السمكي', en: 'Aquaculture supplies', tr: 'Su ürünleri malzemeleri', fr: 'Fournitures d’aquaculture' } },
  { slug: 'other', labels: { ar: 'منتجات أخرى مرتبطة بالزراعة والغذاء', en: 'Other agriculture and food products', tr: 'Diğer tarım ve gıda ürünleri', fr: 'Autres produits agricoles et alimentaires' } },
]

/** Maps older marketplace category slugs onto the current agricultural/food list. */
export const LEGACY_CATEGORY_SLUGS: Record<string, ProductCategorySlug> = {
  'grains-legumes': 'grains',
  'plants-seedlings': 'plants',
  'bee-products': 'honey-bee-products',
  'fish-products': 'fish-seafood',
  'fertilizers-agri-products': 'fertilizers',
}

export function isProductCategorySlug(value: string | null | undefined): value is ProductCategorySlug {
  return Boolean(value && (PRODUCT_CATEGORY_SLUGS as readonly string[]).includes(value))
}

function localeKey(language: string): keyof LocalizedLabel {
  const code = language.slice(0, 2).toLowerCase()
  if (code === 'ar' || code === 'tr' || code === 'fr') return code
  return 'en'
}

export function productCategoryLabel(slug: string, language: string): string {
  const canonical = isProductCategorySlug(slug) ? slug : LEGACY_CATEGORY_SLUGS[slug]
  const match = PRODUCT_CATEGORIES.find((category) => category.slug === canonical)
  if (!match) return slug
  return match.labels[localeKey(language)]
}

export function resolveCategorySlug(input: {
  category?: { slug?: string | null } | null
  product_type?: string | null
}): string {
  const candidates = [input.category?.slug, input.product_type]
  for (const candidate of candidates) {
    const slug = candidate?.trim()
    if (!slug) continue
    if (isProductCategorySlug(slug)) return slug
    if (LEGACY_CATEGORY_SLUGS[slug]) return LEGACY_CATEGORY_SLUGS[slug]
  }
  return input.category?.slug?.trim() || input.product_type?.trim() || ''
}
