import { useEffect, useState } from 'react'
import { fetchEmployerSeekerPhoto } from '../../api/jobs'
import { useAuth } from '../../context/AuthContext'

export function EmployerSeekerPhoto({
  seekerId,
  hasPhoto,
  className = 'employer-photo',
}: {
  seekerId: number
  hasPhoto: boolean
  className?: string
}) {
  const { token, organizationId } = useAuth()
  const [url, setUrl] = useState<string | null>(null)

  useEffect(() => {
    if (!token || !hasPhoto) {
      setUrl(null)
      return
    }

    let active = true
    let createdUrl: string | null = null
    void fetchEmployerSeekerPhoto(token, seekerId, organizationId ?? undefined).then((next) => {
      createdUrl = next
      if (active) setUrl(next)
    })

    return () => {
      active = false
      if (createdUrl) URL.revokeObjectURL(createdUrl)
    }
  }, [token, hasPhoto, seekerId, organizationId])

  if (!url) return null
  return <img className={className} src={url} alt="" />
}
