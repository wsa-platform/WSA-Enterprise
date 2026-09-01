import { useEffect, useState } from 'react'
import {
  fetchCropLibraryFiles,
  type CropLibraryFileRecord,
} from '../../api/libraryCropFiles'
import { LibraryFilePreviewPanel } from './LibraryFilePreviewPanel'
import { getLibraryCropsForCategory } from './libraryCrops'
import {
  getLibraryCropSectionsForCrop,
  type LibraryCropSectionId,
} from './libraryCropSections'
import {
  LIBRARY_PLANT_PRODUCTION_CATEGORIES,
  LIBRARY_PLANT_PRODUCTION_SECTION_TITLE,
} from './libraryPlantProductionCategories'
import {
  getFileExtensionFromName,
  isLibraryFilePreviewable,
  LIBRARY_FILE_ACTION_LABEL,
} from './libraryFilePreview'
import './libraryPage.css'

type CategorySelection = {
  id: string
  name: string
}

type CropSelection = CategorySelection

type SectionSelection = {
  id: LibraryCropSectionId
  label: string
}

type LibraryView =
  | { level: 'categories' }
  | { level: 'crops'; category: CategorySelection }
  | { level: 'crop-sections'; category: CategorySelection; crop: CropSelection }
  | {
      level: 'section-files'
      category: CategorySelection
      crop: CropSelection
      section: SectionSelection
    }
  | {
      level: 'file-preview'
      category: CategorySelection
      crop: CropSelection
      section: SectionSelection
      file: CropLibraryFileRecord
    }

function getCategoryById(categoryId: string): CategorySelection | undefined {
  return LIBRARY_PLANT_PRODUCTION_CATEGORIES.find((category) => category.id === categoryId)
}

export function LibraryPlantProductionBody() {
  const [view, setView] = useState<LibraryView>({ level: 'categories' })
  const [files, setFiles] = useState<CropLibraryFileRecord[]>([])
  const [filesLoading, setFilesLoading] = useState(false)
  const [filesError, setFilesError] = useState('')

  useEffect(() => {
    if (view.level !== 'section-files') {
      return
    }

    let cancelled = false
    setFilesLoading(true)
    setFilesError('')

    void fetchCropLibraryFiles({
      plantProductionCategoryId: view.category.id,
      fieldCropId: view.crop.id,
      libraryFileSection: view.section.id,
    })
      .then((records) => {
        if (!cancelled) {
          setFiles(records)
        }
      })
      .catch(() => {
        if (!cancelled) {
          setFiles([])
          setFilesError('تعذر تحميل ملفات هذا القسم حاليًا.')
        }
      })
      .finally(() => {
        if (!cancelled) {
          setFilesLoading(false)
        }
      })

    return () => {
      cancelled = true
    }
  }, [view])

  const openCategory = (categoryId: string) => {
    const category = getCategoryById(categoryId)
    if (!category) return
    setView({ level: 'crops', category })
  }

  const openCrop = (crop: CropSelection) => {
    if (view.level !== 'crops') return
    setView({ level: 'crop-sections', category: view.category, crop })
  }

  const openSection = (section: SectionSelection) => {
    if (view.level !== 'crop-sections') return
    setView({
      level: 'section-files',
      category: view.category,
      crop: view.crop,
      section,
    })
  }

  const openFile = (file: CropLibraryFileRecord) => {
    if (view.level !== 'section-files') return
    setView({
      level: 'file-preview',
      category: view.category,
      crop: view.crop,
      section: view.section,
      file,
    })
  }

  const goToCategories = () => setView({ level: 'categories' })

  const goToCrops = () => {
    if (view.level === 'categories') return
    const category =
      view.level === 'crops'
        ? view.category
        : 'category' in view
          ? view.category
          : undefined
    if (!category) return
    setView({ level: 'crops', category })
  }

  const goToCropSections = () => {
    if (view.level === 'categories' || view.level === 'crops') return
    const category = 'category' in view ? view.category : undefined
    const crop = 'crop' in view ? view.crop : undefined
    if (!category || !crop) return
    setView({ level: 'crop-sections', category, crop })
  }

  const goToSectionFiles = () => {
    if (view.level !== 'file-preview') return
    setView({
      level: 'section-files',
      category: view.category,
      crop: view.crop,
      section: view.section,
    })
  }

  const renderBreadcrumb = () => {
    if (view.level === 'categories') return null

    const category = 'category' in view ? view.category : undefined
    const crop = 'crop' in view ? view.crop : undefined
    const section = 'section' in view ? view.section : undefined

    return (
      <nav className="library-page__breadcrumb" aria-label="مسار التصفح">
        <button type="button" className="library-page__breadcrumb-link" onClick={goToCategories}>
          {LIBRARY_PLANT_PRODUCTION_SECTION_TITLE}
        </button>
        {category && (
          <>
            <span className="library-page__breadcrumb-separator">/</span>
            <button type="button" className="library-page__breadcrumb-link" onClick={goToCrops}>
              {category.name}
            </button>
          </>
        )}
        {crop && (
          <>
            <span className="library-page__breadcrumb-separator">/</span>
            <button type="button" className="library-page__breadcrumb-link" onClick={goToCropSections}>
              {crop.name}
            </button>
          </>
        )}
        {section && (
          <>
            <span className="library-page__breadcrumb-separator">/</span>
            {view.level === 'file-preview' ? (
              <button type="button" className="library-page__breadcrumb-link" onClick={goToSectionFiles}>
                {section.label}
              </button>
            ) : (
              <span className="library-page__breadcrumb-current">{section.label}</span>
            )}
          </>
        )}
        {view.level === 'file-preview' && (
          <>
            <span className="library-page__breadcrumb-separator">/</span>
            <span className="library-page__breadcrumb-current">
              {view.file.title_ar || view.file.title}
            </span>
          </>
        )}
      </nav>
    )
  }

  const renderTitle = () => {
    switch (view.level) {
      case 'categories':
        return LIBRARY_PLANT_PRODUCTION_SECTION_TITLE
      case 'crops':
        return view.category.name
      case 'crop-sections':
        return view.crop.name
      case 'section-files':
        return view.section.label
      case 'file-preview':
        return view.file.title_ar || view.file.title
    }
  }

  const renderContent = () => {
    if (view.level === 'categories') {
      return (
        <div className="library-page__category-grid" role="list">
          {LIBRARY_PLANT_PRODUCTION_CATEGORIES.map((category) => (
            <button
              key={category.id}
              type="button"
              className="library-page__category-card"
              data-category-id={category.id}
              role="listitem"
              onClick={() => openCategory(category.id)}
            >
              <span className="library-page__category-name">{category.name}</span>
            </button>
          ))}
        </div>
      )
    }

    if (view.level === 'crops') {
      const crops = getLibraryCropsForCategory(view.category.id)
      if (crops.length === 0) {
        return <p className="library-page__empty">لا توجد محاصيل في هذا التصنيف حاليًا.</p>
      }

      return (
        <div className="library-page__category-grid" role="list">
          {crops.map((crop) => (
            <button
              key={crop.id}
              type="button"
              className="library-page__category-card"
              data-crop-id={crop.id}
              data-category-id={view.category.id}
              role="listitem"
              onClick={() => openCrop(crop)}
            >
              <span className="library-page__category-name">{crop.name}</span>
            </button>
          ))}
        </div>
      )
    }

    if (view.level === 'crop-sections') {
      const sections = getLibraryCropSectionsForCrop(view.crop.name)
      return (
        <div className="library-page__category-grid" role="list">
          {sections.map((section) => (
            <button
              key={section.id}
              type="button"
              className="library-page__category-card"
              data-section-id={section.id}
              data-crop-id={view.crop.id}
              data-category-id={view.category.id}
              role="listitem"
              onClick={() => openSection(section)}
            >
              <span className="library-page__category-name">{section.label}</span>
            </button>
          ))}
        </div>
      )
    }

    if (view.level === 'file-preview') {
      return <LibraryFilePreviewPanel file={view.file} onClose={goToSectionFiles} />
    }

    return (
      <div className="library-page__files">
        {filesLoading && <p className="library-page__status">جاري تحميل الملفات...</p>}
        {filesError && <p className="library-page__error">{filesError}</p>}
        {!filesLoading && !filesError && files.length === 0 && (
          <p className="library-page__empty">لا توجد ملفات في هذا القسم حاليًا.</p>
        )}
        {!filesLoading && files.length > 0 && (
          <ul className="library-page__file-list">
            {files.map((file) => {
              const extension = file.extension || getFileExtensionFromName(file.file_name)
              const previewable = isLibraryFilePreviewable(extension)
              return (
                <li key={file.id} className="library-page__file-item">
                  <div className="library-page__file-details">
                    <strong>{file.title_ar || file.title}</strong>
                    <span className="library-page__file-extension">{extension.toUpperCase()}</span>
                    <span className="library-page__file-name">{file.file_name}</span>
                  </div>
                  <button
                    type="button"
                    className="library-page__file-action"
                    data-file-id={file.id}
                    data-previewable={previewable ? 'true' : 'false'}
                    onClick={() => openFile(file)}
                  >
                    {LIBRARY_FILE_ACTION_LABEL}
                  </button>
                </li>
              )
            })}
          </ul>
        )}
      </div>
    )
  }

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
            {renderBreadcrumb()}
            <h1 id="library-plant-production-title" className="library-page__title">
              {renderTitle()}
            </h1>
            {renderContent()}
          </section>
        </div>
      </div>
    </div>
  )
}
