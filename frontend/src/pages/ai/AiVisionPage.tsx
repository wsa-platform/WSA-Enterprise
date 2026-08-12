import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { createAiRequest, pollAiRequest, type AiRequestRecord } from '../../api'
import { uploadVisionImage } from '../../api/assistant'
import { PageHeader } from '../../components/PageHeader'
import { ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { translateApiError } from '../../i18n/apiErrors'

function readFileAsDataUrl(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(String(reader.result))
    reader.onerror = () => reject(new Error('Unable to read image.'))
    reader.readAsDataURL(file)
  })
}

export function AiVisionPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [preview, setPreview] = useState('')
  const [prompt, setPrompt] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [notice, setNotice] = useState('')
  const [result, setResult] = useState<AiRequestRecord | null>(null)

  const hasAccess = can('ai.vision') || can('ai.use')

  if (!hasAccess) {
    return <ErrorBanner message={t('aiVision.noPermission')} />
  }

  const handleFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0]
    if (!file) return
    setSelectedFile(file)
    setPreview(await readFileAsDataUrl(file))
    setResult(null)
  }

  const analyzeImage = async () => {
    if (!token || !selectedFile) return
    setSubmitting(true)
    setNotice('')
    setResult(null)
    try {
      setNotice(t('aiVision.uploading'))
      const upload = await uploadVisionImage(token, selectedFile, organizationId ?? undefined)

      const created = await createAiRequest(token, {
        request_type: 'vision_analysis',
        input: {
          image_path: upload.storage_path,
          prompt: prompt || undefined,
        },
      }, organizationId ?? undefined)

      let final = created
      if (created.status === 'pending' || created.status === 'processing') {
        setNotice(t('aiVision.analyzing'))
        final = await pollAiRequest(token, created.id, organizationId ?? undefined)
      }

      setResult(final)
      setNotice(t('aiVision.analysisComplete', { status: final.status }))
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('aiVision.analysisFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  return <>
    <PageHeader
      eyebrow={t('nav.ai')}
      title={t('aiVision.title')}
      description={t('aiVision.description')}
    />

    {notice && <p className="notice">{notice}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('aiVision.upload')}</p><h2>{t('aiVision.analyzeImage')}</h2></div></div>
      <div className="record-form">
        <label>
          {t('aiVision.selectImage')}
          <input type="file" accept="image/*" onChange={(event) => void handleFileChange(event)} />
        </label>
        <label>
          {t('aiVision.prompt')}
          <textarea value={prompt} onChange={(event) => setPrompt(event.target.value)} rows={3} placeholder={t('aiVision.promptPlaceholder')} dir="auto" />
        </label>
        <button type="button" disabled={submitting || !selectedFile} onClick={() => void analyzeImage()}>
          {submitting ? t('aiVision.submitting') : t('aiVision.submit')}
        </button>
      </div>
      {preview && (
        <div className="vision-preview">
          <img src={preview} alt={t('aiVision.previewAlt')} />
        </div>
      )}
    </section>

    {result && (
      <section className="panel">
        <div className="panel-heading">
          <div><p className="eyebrow">{t('aiVision.result')}</p><h2>{t('aiVision.analysisResult')}</h2></div>
          <StatusBadge status={result.status} />
        </div>
        {result.error_message ? (
          <ErrorBanner message={result.error_message} />
        ) : (
          <pre className="code-block">{JSON.stringify(result.output ?? {}, null, 2)}</pre>
        )}
      </section>
    )}
  </>
}
