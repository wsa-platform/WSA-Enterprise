import { useEffect, useState } from 'react'
import { ApiError } from '../api/client'
import {
  fetchFieldCropFarmingNeedsProfile,
  type FieldCropCultivationProfile,
} from '../api/fieldCropCultivation'

type FieldCropFarmingNeedsPanelProps = {
  categoryId: string
  categoryName: string
  cropId: string
  cropName: string
}

function resolveFetchErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    if (error.status === 404) {
      return 'تعذر العثور على خدمة المعرفة الزراعية أو مؤسسة المنصة. تحقق من تشغيل الخادم وإعدادات المؤسسة.'
    }
    if (error.status === 0 || error.status >= 500) {
      return 'تعذر الاتصال بخدمة المعرفة الزراعية. تحقق من تشغيل الخادم ثم أعد المحاولة.'
    }
    return error.message || 'تعذر تحميل معلومات المحصول من المصادر المعتمدة.'
  }

  return 'تعذر الاتصال بخدمة المعرفة الزراعية. تحقق من تشغيل الخادم ثم أعد المحاولة.'
}

/** Displays the verified agricultural profile for زراعة واحتياجات المحصول. */
export function FieldCropFarmingNeedsPanel({
  categoryId,
  categoryName,
  cropId,
  cropName,
}: FieldCropFarmingNeedsPanelProps) {
  const [profile, setProfile] = useState<FieldCropCultivationProfile | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    setError(null)
    setProfile(null)

    fetchFieldCropFarmingNeedsProfile({
      selectedCropId: cropId,
      selectedCropName: cropName,
      selectedCategoryId: categoryId,
      selectedCategoryName: categoryName,
    })
      .then((result) => {
        if (!cancelled) {
          setProfile(result)
        }
      })
      .catch((fetchError: unknown) => {
        if (!cancelled) {
          setError(resolveFetchErrorMessage(fetchError))
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false)
        }
      })

    return () => {
      cancelled = true
    }
  }, [categoryId, categoryName, cropId, cropName])

  if (loading) {
    return (
      <div className="gs-field-crop-profile" aria-live="polite">
        <p>جاري البحث في المصادر العلمية الموثوقة...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className="gs-field-crop-profile" role="alert">
        <p>{error}</p>
      </div>
    )
  }

  if (!profile) {
    return null
  }

  return (
    <article
      className="gs-field-crop-profile"
      aria-labelledby="field-crop-profile-title"
      style={{ width: '100%', marginTop: '1rem', textAlign: 'start' }}
    >
      <h2 id="field-crop-profile-title" style={{ fontSize: '1.25rem', fontWeight: 800, marginBottom: '1rem' }}>
        {profile.title}
      </h2>

      {profile.message ? (
        <p className="gs-field-crop-profile-notice" role="status">
          {profile.message}
        </p>
      ) : null}

      {profile.sections.map((section) => (
        <section
          key={section.key}
          className="gs-field-crop-profile-section"
          style={{
            marginBottom: '1rem',
            padding: '1rem',
            border: '1px solid var(--border)',
            borderRadius: '0.85rem',
            background: 'var(--card)',
          }}
        >
          <h3 style={{ margin: '0 0 0.5rem', fontSize: '1rem', fontWeight: 800 }}>{section.title}</h3>
          <div className="gs-field-crop-profile-content" dir="rtl">
            {section.content.split('\n').map((paragraph, index) => (
              <p key={`${section.key}-${index}`}>{paragraph}</p>
            ))}
          </div>
          {section.source ? (
            <p className="gs-field-crop-profile-source">
              <strong>المصدر:</strong>{' '}
              {section.source.organization ? `${section.source.organization} — ` : ''}
              {section.source.title}
              {section.source.url ? (
                <>
                  {' '}
                  <a href={section.source.url} target="_blank" rel="noreferrer noopener">
                    {section.source.url}
                  </a>
                </>
              ) : null}
            </p>
          ) : null}
        </section>
      ))}

      {profile.references.length > 0 ? (
        <section className="gs-field-crop-profile-section" aria-labelledby="field-crop-references">
          <h3 id="field-crop-references">المراجع العلمية</h3>
          <ul className="gs-field-crop-profile-references">
            {profile.references.map((reference) => (
              <li key={`${reference.organization}-${reference.url ?? reference.title}`}>
                {reference.organization ? `${reference.organization} — ` : ''}
                {reference.title}
                {reference.year ? ` (${reference.year})` : ''}
                {reference.url ? (
                  <>
                    {' '}
                    <a href={reference.url} target="_blank" rel="noreferrer noopener">
                      {reference.url}
                    </a>
                  </>
                ) : null}
              </li>
            ))}
          </ul>
        </section>
      ) : null}
    </article>
  )
}
