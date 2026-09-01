import {
  buildCropLibraryFileContentUrl,
  type CropLibraryFileRecord,
} from '../../api/libraryCropFiles'
import {
  getLibraryFilePreviewMode,
  isLibraryFilePreviewable,
  LIBRARY_FILE_ACTION_LABEL,
} from './libraryFilePreview'

type LibraryFilePreviewPanelProps = {
  file: CropLibraryFileRecord
  onClose: () => void
}

export function LibraryFilePreviewPanel({ file, onClose }: LibraryFilePreviewPanelProps) {
  const contentUrl = buildCropLibraryFileContentUrl(file.id)
  const previewMode = getLibraryFilePreviewMode(file.extension)
  const previewable = isLibraryFilePreviewable(file.extension)
  const displayTitle = file.title_ar || file.title

  return (
    <div className="library-page__preview" data-testid="library-file-preview">
      <div className="library-page__preview-header">
        <h2 className="library-page__preview-title">{displayTitle}</h2>
        <button type="button" className="library-page__back-button" onClick={onClose}>
          رجوع إلى الملفات
        </button>
      </div>

      <p className="library-page__file-meta">
        <span className="library-page__file-extension">{file.extension.toUpperCase()}</span>
        <span>{file.file_name}</span>
      </p>

      {previewMode === 'pdf' && (
        <iframe
          className="library-page__preview-frame"
          src={contentUrl}
          title={displayTitle}
          data-testid="library-file-preview-pdf"
        />
      )}

      {previewMode === 'image' && (
        <img
          className="library-page__preview-image"
          src={contentUrl}
          alt={displayTitle}
          data-testid="library-file-preview-image"
        />
      )}

      {previewMode === 'text' && (
        <iframe
          className="library-page__preview-frame"
          src={contentUrl}
          title={displayTitle}
          data-testid="library-file-preview-text"
        />
      )}

      {!previewable && (
        <div className="library-page__preview-unsupported" data-testid="library-file-preview-unsupported">
          <p>المعاينة غير متاحة لهذا الامتداد ({file.extension.toUpperCase()}).</p>
          <a className="library-page__file-action" href={contentUrl} download={file.file_name}>
            فتح / تنزيل الملف الأصلي
          </a>
        </div>
      )}

      {previewable && (
        <a
          className="library-page__file-action library-page__file-action--secondary"
          href={contentUrl}
          target="_blank"
          rel="noopener noreferrer"
        >
          {LIBRARY_FILE_ACTION_LABEL}
        </a>
      )}
    </div>
  )
}
