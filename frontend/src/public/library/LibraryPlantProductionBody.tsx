import {
  LIBRARY_PLANT_PRODUCTION_CATEGORIES,
  LIBRARY_PLANT_PRODUCTION_SECTION_TITLE,
} from './libraryPlantProductionCategories'
import './libraryPage.css'

export function LibraryPlantProductionBody() {
  return (
    <div className="library-page" dir="rtl" lang="ar">
      <div className="library-page__container gs-container">
        <div className="library-page__layout">
          <aside className="library-page__sidebar" aria-label="أقسام المكتبة">
            <nav className="library-page__sidebar-nav">
              <button
                type="button"
                className="library-page__sidebar-item is-active"
                aria-current="page"
              >
                {LIBRARY_PLANT_PRODUCTION_SECTION_TITLE}
              </button>
            </nav>
          </aside>

          <section className="library-page__content" aria-labelledby="library-plant-production-title">
            <h1 id="library-plant-production-title" className="library-page__title">
              {LIBRARY_PLANT_PRODUCTION_SECTION_TITLE}
            </h1>

            <div className="library-page__category-grid" role="list">
              {LIBRARY_PLANT_PRODUCTION_CATEGORIES.map((category) => (
                <button
                  key={category.id}
                  type="button"
                  className="library-page__category-card"
                  data-category-id={category.id}
                  role="listitem"
                >
                  <span className="library-page__category-name">{category.name}</span>
                </button>
              ))}
            </div>
          </section>
        </div>
      </div>
    </div>
  )
}
