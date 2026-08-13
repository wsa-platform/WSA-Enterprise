/**
 * Sync website i18n keys into en.json and locale overlay files.
 * Run: node scripts/sync-website-i18n.mjs
 */
import fs from 'node:fs'
import path from 'node:path'

const localesDir = path.join('src', 'i18n', 'locales')

const additions = {
  newsletter: {
    title: 'Subscribe to our newsletter',
    description:
      'Receive agricultural news, new articles, farming tips, platform updates, and store highlights.',
    emailLabel: 'Email address',
    emailPlaceholder: 'your@email.com',
    subscribe: 'Subscribe',
    success: 'Thank you! Your subscription request has been received.',
    hint: 'Newsletter only — not a paid subscription.',
  },
  quickLinks: {
    title: 'Quick Links',
    sections: 'Main Sections',
    info: 'Information',
  },
  featured: {
    title: 'Featured content & services',
    subtitle: 'Discover highlighted areas of the WSA Enterprise agricultural platform.',
    store: 'Green Garden Store',
    storeDesc: 'Browse agricultural products, supplies, and natural farm goods.',
    ornamental: 'Ornamental Plants & Garden',
    ornamentalDesc: 'Indoor and outdoor ornamental plants, flowers, and garden collections.',
    training: 'Training & Education',
    trainingDesc: 'Courses, lectures, and agricultural learning programs.',
  },
  footer: {
    sections: 'Main Sections',
    account: 'Account',
  },
  sections: {
    ornamentalPlants: {
      title: 'Ornamental Plants',
      description:
        'Indoor and outdoor ornamental plants, flowering species, garden shrubs, and decorative greenery.',
      imageAlt: 'Colorful ornamental flowers and garden plants',
    },
    hydroponicAquaculture: {
      title: 'Hydroponic & Aquaculture',
      description:
        'Modern hydroponic cultivation and aquaculture — sustainable water-based agricultural systems.',
      imageAlt: 'Hydroponic plants growing in a modern greenhouse with water systems',
    },
    smallProjects: {
      title: 'Small Projects',
      description:
        'Home farming, small greenhouses, beekeeping projects, and entrepreneurial agricultural ideas.',
      imageAlt: 'Small greenhouse and home agricultural project',
    },
    ornamentalPlantsFeatures: {
      indoor: { title: 'Indoor ornamental plants', body: 'Decorative houseplants and interior greenery.' },
      outdoor: { title: 'Outdoor ornamental plants', body: 'Garden beds, patios, and landscape ornamentals.' },
      flowering: { title: 'Flowering plants', body: 'Seasonal blooms and colorful garden flowers.' },
      garden: { title: 'Garden plants', body: 'Shrubs, decorative plants, and garden design collections.' },
    },
    hydroponicFeatures: {
      hydroponic: { title: 'Hydroponic cultivation', body: 'Soilless growing systems for modern crop production.' },
      aquaculture: { title: 'Aquaculture', body: 'Fish farming and integrated water-based agriculture.' },
      sustainable: { title: 'Sustainability', body: 'Efficient water use and eco-friendly production methods.' },
      modern: { title: 'Modern agriculture', body: 'Technology-driven growing for urban and commercial farms.' },
    },
    smallProjectsFeatures: {
      homeFarming: { title: 'Home farming', body: 'Start small-scale food production at home.' },
      greenhouse: { title: 'Small greenhouses', body: 'Compact protected cultivation project ideas.' },
      beekeeping: { title: 'Beekeeping projects', body: 'Small apiary and hive starter projects.' },
      business: { title: 'Small agribusiness', body: 'Feasibility guidance for micro agricultural ventures.' },
    },
  },
  highlights: {
    ornamentalPlants: {
      shrubs: { title: 'Ornamental shrubs', body: 'Decorative shrubs for gardens and landscapes.' },
      decorative: { title: 'Decorative plants', body: 'Curated ornamental collections for homes and gardens.' },
    },
    hydroponicAquaculture: {
      water: { title: 'Water-based systems', body: 'Integrated hydroponic and aquaculture approaches.' },
      greenTech: { title: 'Green technology', body: 'Modern sustainable agricultural innovation.' },
    },
    smallProjects: {
      ideas: { title: 'Project ideas', body: 'Inspiration for small agricultural and food projects.' },
      feasibility: { title: 'Feasibility guidance', body: 'Practical planning for small-scale ventures.' },
    },
  },
  aiOrnamental: {
    examples: [
      'Ornamental plant disease image inspection',
      'Flowering plant health assessment',
      'Garden shrub pest identification',
      'Decorative plant care recommendations',
    ],
  },
}

function deepMerge(target, source) {
  for (const [key, value] of Object.entries(source)) {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
      target[key] = deepMerge(target[key] ?? {}, value)
    } else {
      target[key] = value
    }
  }
  return target
}

const enPath = path.join(localesDir, 'en.json')
const en = JSON.parse(fs.readFileSync(enPath, 'utf8'))
const website = en.website ?? {}

website.newsletter = additions.newsletter
website.quickLinks = additions.quickLinks
website.featured = additions.featured
website.footer = { ...website.footer, ...additions.footer }

website.sections = website.sections ?? {}
Object.assign(website.sections, {
  ornamentalPlants: additions.sections.ornamentalPlants,
  hydroponicAquaculture: additions.sections.hydroponicAquaculture,
  smallProjects: additions.sections.smallProjects,
  'ornamentalPlants.features.indoor': additions.sections.ornamentalPlantsFeatures.indoor,
  'ornamentalPlants.features.outdoor': additions.sections.ornamentalPlantsFeatures.outdoor,
  'ornamentalPlants.features.flowering': additions.sections.ornamentalPlantsFeatures.flowering,
  'ornamentalPlants.features.garden': additions.sections.ornamentalPlantsFeatures.garden,
  'hydroponicAquaculture.features.hydroponic': additions.sections.hydroponicFeatures.hydroponic,
  'hydroponicAquaculture.features.aquaculture': additions.sections.hydroponicFeatures.aquaculture,
  'hydroponicAquaculture.features.sustainable': additions.sections.hydroponicFeatures.sustainable,
  'hydroponicAquaculture.features.modern': additions.sections.hydroponicFeatures.modern,
  'smallProjects.features.homeFarming': additions.sections.smallProjectsFeatures.homeFarming,
  'smallProjects.features.greenhouse': additions.sections.smallProjectsFeatures.greenhouse,
  'smallProjects.features.beekeeping': additions.sections.smallProjectsFeatures.beekeeping,
  'smallProjects.features.business': additions.sections.smallProjectsFeatures.business,
})

website.highlights = website.highlights ?? {}
deepMerge(website.highlights, additions.highlights)

website.aiPlantInspection = website.aiPlantInspection ?? {}
website.aiPlantInspection['ornamental-plants'] = { examples: additions.aiOrnamental.examples }

en.website = website
fs.writeFileSync(enPath, `${JSON.stringify(en, null, 2)}\n`)

// Arabic / French / Turkish overlays for new keys
const arOverlay = {
  newsletter: {
    title: 'اشترك في نشرتنا البريدية',
    description: 'أخبار زراعية ومقالات جديدة ونصائح للمزارعين وتحديثات المنصة وم highlights للمتجر.',
    emailLabel: 'البريد الإلكتروني',
    emailPlaceholder: 'your@email.com',
    subscribe: 'اشترك',
    success: 'شكراً! تم استلام طلب الاشتراك.',
    hint: 'نشرة بريدية فقط — وليست اشتراكاً مدفوعاً.',
  },
  quickLinks: { title: 'روابط سريعة', sections: 'الأقسام الرئيسية', info: 'معلومات' },
  featured: {
    title: 'محتوى وخدمات مميزة',
    subtitle: 'اكتشف أبرز مجالات منصة WSA Enterprise الزراعية.',
    store: 'متجر Green Garden',
    storeDesc: 'تصفح المنتجات الزراعية والمستلزمات والسلع الطبيعية.',
    ornamental: 'نباتات الزينة والحدائق',
    ornamentalDesc: 'نباتات زينة داخلية وخارجية وزهور ومجموعات حدائق.',
    training: 'التدريب والتعليم',
    trainingDesc: 'دورات ومحاضرات وبرامج تعليم زراعي.',
  },
  footer: { sections: 'الأقسام الرئيسية', account: 'الحساب' },
  sections: {
    ornamentalPlants: {
      title: 'نباتات الزينة',
      description: 'نباتات زينة داخلية وخارجية ونباتات مزهرة وشجيرات حدائق ونباتات decorative.',
      imageAlt: 'زهور زينة ملونة ونباتات حديقة',
    },
    hydroponicAquaculture: {
      title: 'الزراعة الهيدروبونك والزراعة المائية',
      description: 'زراعة هيدروبونik حديثة وزراعة مائية — أنظمة زراعية مستدامة قائمة على المياه.',
      imageAlt: 'نباتات هيدروبونik في بيوت محمية حديثة',
    },
    smallProjects: {
      title: 'مشاريع صغيرة',
      description: 'زراعة منزلية وبيوت محمية صغيرة ومشاريع نحل وأفكار ريادية زراعية.',
      imageAlt: 'مشروع زراعي منزلي صغير وبيوت محمية',
    },
    'ornamentalPlants.features.indoor': { title: 'نباتات زينة داخلية', body: 'نباتات منزلية decorative وخضرة داخلية.' },
    'ornamentalPlants.features.outdoor': { title: 'نباتات زينة خارجية', body: 'أسرة حدائق وشرفات ونباتات landscape.' },
    'ornamentalPlants.features.flowering': { title: 'نباتات مزهرة', body: 'أزهار موسمية وألوان حديقة زاهية.' },
    'ornamentalPlants.features.garden': { title: 'نباتات الحدائق', body: 'شجيرات زينة ومجموعات تصميم حدائق.' },
    'hydroponicAquaculture.features.hydroponic': { title: 'زراعة هيدروبونik', body: 'أنظمة زراعة بدون تربة للإنتاج الحديث.' },
    'hydroponicAquaculture.features.aquaculture': { title: 'زراعة مائية', body: 'تربية أسماك وزراعة متكاملة قائمة على المياه.' },
    'hydroponicAquaculture.features.sustainable': { title: 'استدامة', body: 'كفاءة المياه وطرق إنتاج صديقة للبيئة.' },
    'hydroponicAquaculture.features.modern': { title: 'زراعة حديثة', body: 'زراعة تقنية للمزارع الحضرية والتجارية.' },
    'smallProjects.features.homeFarming': { title: 'زراعة منزلية', body: 'ابدأ إنتاجاً غذائياً صغيراً في المنزل.' },
    'smallProjects.features.greenhouse': { title: 'بيوت محمية صغيرة', body: 'أفكار مشاريع زراعة محمية مدمجة.' },
    'smallProjects.features.beekeeping': { title: 'مشاريع نحل', body: 'مشاريع منحل وخلية للمبتدئين.' },
    'smallProjects.features.business': { title: 'مشاريع agribusiness صغيرة', body: 'إرشادات جدوى للمشاريع الزراعية المصغرة.' },
  },
  highlights: {
    ornamentalPlants: {
      shrubs: { title: 'شجيرات زينة', body: 'شجيرات decorative للحدائق والمناظر الطبيعية.' },
      decorative: { title: 'نباتات decorative', body: 'مجموعات زينة للمنازل والحدائق.' },
    },
    hydroponicAquaculture: {
      water: { title: 'أنظمة مائية', body: 'نهج متكامل للهيدروبونik والزراعة المائية.' },
      greenTech: { title: 'تقنية خضراء', body: 'ابتكار زراعي مستدام حديث.' },
    },
    smallProjects: {
      ideas: { title: 'أفكار مشاريع', body: 'إلهام لمشاريع زراعية وغذائية صغيرة.' },
      feasibility: { title: 'إرشادات الجدوى', body: 'تخطيط عملي للمشاريع الصغيرة.' },
    },
  },
  aiPlantInspection: {
    'ornamental-plants': {
      examples: [
        'فحص صورة مرض نبات زينة',
        'تقييم صحة النباتات المزهرة',
        'تحديد آفات الشجيرات',
        'توصيات العناية بالنباتات decorative',
      ],
    },
  },
}

const frOverlay = {
  newsletter: {
    title: 'Abonnez-vous à notre newsletter',
    description: 'Actualités agricoles, nouveaux articles, conseils, mises à jour et nouveautés boutique.',
    emailLabel: 'Adresse e-mail',
    emailPlaceholder: 'your@email.com',
    subscribe: "S'abonner",
    success: 'Merci ! Votre demande d’abonnement a été reçue.',
    hint: 'Newsletter uniquement — pas un abonnement payant.',
  },
  quickLinks: { title: 'Liens rapides', sections: 'Sections principales', info: 'Informations' },
  featured: {
    title: 'Contenus et services en vedette',
    subtitle: 'Découvrez les points forts de la plateforme WSA Enterprise.',
    store: 'Green Garden Store',
    storeDesc: 'Produits agricoles, fournitures et biens naturels.',
    ornamental: 'Plantes ornementales',
    ornamentalDesc: 'Plantes d’intérieur et d’extérieur, fleurs et jardins.',
    training: 'Formation',
    trainingDesc: 'Cours, conférences et programmes agricoles.',
  },
  footer: { sections: 'Sections principales', account: 'Compte' },
  sections: {
    ornamentalPlants: {
      title: 'Plantes ornementales',
      description: 'Plantes d’intérieur et d’extérieur, floraisons, arbustes et végétaux décoratifs.',
      imageAlt: 'Fleurs ornementales colorées',
    },
    hydroponicAquaculture: {
      title: 'Hydroponie et aquaculture',
      description: 'Culture hydroponique et aquaculture — agriculture durable basée sur l’eau.',
      imageAlt: 'Plantes hydroponiques en serre moderne',
    },
    smallProjects: {
      title: 'Petits projets',
      description: 'Micro-fermes, petites serres, apiculture et idées entrepreneuriales agricoles.',
      imageAlt: 'Petit projet agricole et serre',
    },
    'ornamentalPlants.features.indoor': { title: 'Plantes d’intérieur', body: 'Végétaux décoratifs pour la maison.' },
    'ornamentalPlants.features.outdoor': { title: 'Plantes d’extérieur', body: 'Massifs, terrasses et ornements paysagers.' },
    'ornamentalPlants.features.flowering': { title: 'Plantes fleuries', body: 'Floraisons saisonnières et couleurs de jardin.' },
    'ornamentalPlants.features.garden': { title: 'Plantes de jardin', body: 'Arbustes et collections décoratives.' },
    'hydroponicAquaculture.features.hydroponic': { title: 'Hydroponie', body: 'Systèmes sans sol pour la production moderne.' },
    'hydroponicAquaculture.features.aquaculture': { title: 'Aquaculture', body: 'Pisciculture et agriculture intégrée.' },
    'hydroponicAquaculture.features.sustainable': { title: 'Durabilité', body: 'Eau efficiente et production écologique.' },
    'hydroponicAquaculture.features.modern': { title: 'Agriculture moderne', body: 'Culture technologique urbaine et commerciale.' },
    'smallProjects.features.homeFarming': { title: 'Micro-ferme', body: 'Production alimentaire à petite échelle.' },
    'smallProjects.features.greenhouse': { title: 'Petites serres', body: 'Idées de culture protégée compacte.' },
    'smallProjects.features.beekeeping': { title: 'Projets apicoles', body: 'Démarrer un petit rucher.' },
    'smallProjects.features.business': { title: 'Micro-agro-entreprise', body: 'Conseils de faisabilité pour petits projets.' },
  },
  highlights: {
    ornamentalPlants: {
      shrubs: { title: 'Arbustes ornementaux', body: 'Arbustes décoratifs pour jardins.' },
      decorative: { title: 'Plantes décoratives', body: 'Collections ornementales pour maisons et jardins.' },
    },
    hydroponicAquaculture: {
      water: { title: 'Systèmes aquatiques', body: 'Approches hydroponiques et aquacoles intégrées.' },
      greenTech: { title: 'Technologie verte', body: 'Innovation agricole durable.' },
    },
    smallProjects: {
      ideas: { title: 'Idées de projets', body: 'Inspiration pour petits projets agricoles.' },
      feasibility: { title: 'Faisabilité', body: 'Planification pratique à petite échelle.' },
    },
  },
  aiPlantInspection: {
    'ornamental-plants': {
      examples: [
        'Inspection d’images de maladies ornementales',
        'Évaluation de plantes fleuries',
        'Identification de ravageurs sur arbustes',
        'Recommandations pour plantes décoratives',
      ],
    },
  },
}

const trOverlay = {
  newsletter: {
    title: 'Bültenimize abone olun',
    description: 'Tarım haberleri, yeni makaleler, ipuçları, platform güncellemeleri ve mağaza yenilikleri.',
    emailLabel: 'E-posta adresi',
    emailPlaceholder: 'your@email.com',
    subscribe: 'Abone ol',
    success: 'Teşekkürler! Abonelik talebiniz alındı.',
    hint: 'Yalnızca bülten — ücretli abonelik değildir.',
  },
  quickLinks: { title: 'Hızlı bağlantılar', sections: 'Ana bölümler', info: 'Bilgi' },
  featured: {
    title: 'Öne çıkan içerik ve hizmetler',
    subtitle: 'WSA Enterprise tarımsal platformunun öne çıkan alanlarını keşfedin.',
    store: 'Green Garden Store',
    storeDesc: 'Tarımsal ürünler, malzemeler ve doğal ürünler.',
    ornamental: 'Süs bitkileri',
    ornamentalDesc: 'İç ve dış mekan süs bitkileri, çiçekler ve bahçe koleksiyonları.',
    training: 'Eğitim',
    trainingDesc: 'Kurslar, dersler ve tarımsal öğrenme programları.',
  },
  footer: { sections: 'Ana bölümler', account: 'Hesap' },
  sections: {
    ornamentalPlants: {
      title: 'Süs bitkileri',
      description: 'İç ve dış mekan süs bitkileri, çiçekli türler, çalılar ve dekoratif bitkiler.',
      imageAlt: 'Renkli süs çiçekleri ve bahçe bitkileri',
    },
    hydroponicAquaculture: {
      title: 'Hidroponik ve su ürünleri',
      description: 'Modern hidroponik yetiştirme ve aquaculture — sürdürülebilir su tabanlı tarım.',
      imageAlt: 'Modern serada hidroponik bitkiler',
    },
    smallProjects: {
      title: 'Küçük projeler',
      description: 'Ev tarımı, küçük seralar, arıcılık projeleri ve girişim fikirleri.',
      imageAlt: 'Küçük tarım projesi ve sera',
    },
    'ornamentalPlants.features.indoor': { title: 'İç mekan süs bitkileri', body: 'Ev bitkileri ve iç mekan yeşillik.' },
    'ornamentalPlants.features.outdoor': { title: 'Dış mekan süs bitkileri', body: 'Bahçe yatakları ve peyzaj bitkileri.' },
    'ornamentalPlants.features.flowering': { title: 'Çiçekli bitkiler', body: 'Mevsimlik çiçekler ve renkli bahçeler.' },
    'ornamentalPlants.features.garden': { title: 'Bahçe bitkileri', body: 'Çalılar ve dekoratif koleksiyonlar.' },
    'hydroponicAquaculture.features.hydroponic': { title: 'Hidroponik', body: 'Topraksız modern üretim sistemleri.' },
    'hydroponicAquaculture.features.aquaculture': { title: 'Su ürünleri', body: 'Balık yetiştiriciliği ve entegre tarım.' },
    'hydroponicAquaculture.features.sustainable': { title: 'Sürdürülebilirlik', body: 'Verimli su kullanımı ve ekolojik üretim.' },
    'hydroponicAquaculture.features.modern': { title: 'Modern tarım', body: 'Kentsel ve ticari teknoloji tabanlı üretim.' },
    'smallProjects.features.homeFarming': { title: 'Ev tarımı', body: 'Evde küçük ölçekli gıda üretimi.' },
    'smallProjects.features.greenhouse': { title: 'Küçük seralar', body: 'Kompakt korunmuş yetiştirme fikirleri.' },
    'smallProjects.features.beekeeping': { title: 'Arıcılık projeleri', body: 'Küçük arılık başlangıç projeleri.' },
    'smallProjects.features.business': { title: 'Küçük agribusiness', body: 'Mikro tarım girişimleri için fizibilite.' },
  },
  highlights: {
    ornamentalPlants: {
      shrubs: { title: 'Süs çalıları', body: 'Bahçeler için dekoratif çalılar.' },
      decorative: { title: 'Dekoratif bitkiler', body: 'Ev ve bahçe süs koleksiyonları.' },
    },
    hydroponicAquaculture: {
      water: { title: 'Su tabanlı sistemler', body: 'Entegre hidroponik ve aquaculture.' },
      greenTech: { title: 'Yeşil teknoloji', body: 'Modern sürdürülebilir tarım inovasyonu.' },
    },
    smallProjects: {
      ideas: { title: 'Proje fikirleri', body: 'Küçük tarım projeleri için ilham.' },
      feasibility: { title: 'Fizibilite', body: 'Küçük ölçekli planlama rehberi.' },
    },
  },
  aiPlantInspection: {
    'ornamental-plants': {
      examples: [
        'Süs bitkisi hastalık görüntü incelemesi',
        'Çiçekli bitki sağlık değerlendirmesi',
        'Çalı zararlısı tespiti',
        'Dekoratif bitki bakım önerileri',
      ],
    },
  },
}

function applyOverlay(base, overlay) {
  const result = structuredClone(base)
  deepMerge(result, overlay)
  return result
}

const enWebsite = en.website

function loadWebsiteOverlay(filename) {
  const filePath = path.join(localesDir, filename)
  if (!fs.existsSync(filePath)) return {}
  return JSON.parse(fs.readFileSync(filePath, 'utf8'))
}

const arWebsite = applyOverlay(applyOverlay(enWebsite, loadWebsiteOverlay('website-ar.json')), arOverlay)
const frWebsite = applyOverlay(applyOverlay(enWebsite, loadWebsiteOverlay('website-fr.json')), frOverlay)
const trWebsite = applyOverlay(applyOverlay(enWebsite, loadWebsiteOverlay('website-tr.json')), trOverlay)

fs.writeFileSync(path.join(localesDir, 'website-ar.json'), `${JSON.stringify(arWebsite, null, 2)}\n`)
fs.writeFileSync(path.join(localesDir, 'website-fr.json'), `${JSON.stringify(frWebsite, null, 2)}\n`)
fs.writeFileSync(path.join(localesDir, 'website-tr.json'), `${JSON.stringify(trWebsite, null, 2)}\n`)

console.log('Synced website i18n')
