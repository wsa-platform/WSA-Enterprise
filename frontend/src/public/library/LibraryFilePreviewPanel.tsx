import { useEffect, useState } from 'react'
import {
  fetchAuthenticatedLibraryFileBlob,
  type CropLibraryFileRecord,
} from '../../api/libraryCropFiles'
import { useAuth } from '../../context/AuthContext'
import {
  getLibraryFilePreviewMode,
  isLibraryFilePreviewable,
  LIBRARY_FILE_ACTION_LABEL,
} from './libraryFilePreview'

type LibraryFilePreviewPanelProps = {
  file: CropLibraryFileRecord
  onClose: () => void
  /** Optional sync URL for tests / pre-resolved blob URLs. */
  contentUrl?: string
}

export function LibraryFilePreviewPanel({
  file,
  onClose,
  contentUrl: contentUrlProp,
}: LibraryFilePreviewPanelProps) {
  const { token } = useAuth()
  const [contentUrl, setContentUrl] = useState(contentUrlProp ?? '')
  const [loadError, setLoadError] = useState('')
  const previewMode = getLibraryFilePreviewMode(file.extension)
  const previewable = isLibraryFilePreviewable(file.extension)
  const displayTitle = file.title_ar || file.title

  useEffect(() => {
    if (contentUrlProp) {
      setContentUrl(contentUrlProp)
      return
    }
    if (!token) {
      setLoadError('authentication_required')
      return
    }

    let objectUrl = ''
    let cancelled = false
    setLoadError('')

    void fetchAuthenticatedLibraryFileBlob(file.id, token)
      .then((blob) => {
        if (cancelled) return
        objectUrl = URL.createObjectURL(blob)
        setContentUrl(objectUrl)
      })
      .catch(() => {
        if (!cancelled) {
          setContentUrl('')
          setLoadError('library_file_unavailable')
        }
      })

    return () => {
      cancelled = true
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl)
      }
    }
  }, [file.id, token, contentUrlProp])

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

      {loadError && (
        <p className="library-page__files-error" data-testid="library-file-preview-error">
          تعذر فتح الملف. يرجى تسجيل الدخول والمحاولة مرة أخرى.
        </p>
      )}

      {contentUrl && previewMode === 'pdf' && (
        <iframe
          className="library-page__preview-frame"
          src={contentUrl}
          title={displayTitle}
          data-testid="library-file-preview-pdf"
        />
      )}

      {contentUrl && previewMode === 'image' && (
        <img
          className="library-page__preview-image"
          src={contentUrl}
          alt={displayTitle}
          data-testid="library-file-preview-image"
        />
      )}

      {contentUrl && previewMode === 'text' && (
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
          {contentUrl ? (
            <a className="library-page__file-action" href={contentUrl} download={file.file_name}>
              فتح / تنزيل الملف الأصلي
            </a>
          ) : null}
        </div>
      )}

      {previewable && contentUrl && (
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
