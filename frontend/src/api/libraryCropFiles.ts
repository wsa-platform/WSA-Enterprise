export type CropLibraryFileRecord = {
  id: number
  title: string
  title_ar: string
  extension: string
  preview_mode: 'inline_browser' | 'download_only'
  file_name: string
  organization_id?: number
  owner_user_id?: number | null
  item_type?: string | null
}

type CropLibraryFilesResponse = {
  data: CropLibraryFileRecord[]
}

function publicOrgSlug(): string {
  return (import.meta.env.VITE_PUBLIC_ORG_SLUG as string | undefined) ?? 'wsa-demo'
}

/** @deprecated Public crop-file browse — do not use from authenticated Library Page. */
export async function fetchCropLibraryFiles(params: {
  plantProductionCategoryId: string
  fieldCropId: string
  libraryFileSection: string
}): Promise<CropLibraryFileRecord[]> {
  const query = new URLSearchParams({
    organization: publicOrgSlug(),
    plant_production_category_id: params.plantProductionCategoryId,
    field_crop_id: params.fieldCropId,
    library_file_section: params.libraryFileSection,
  })

  const response = await fetch(`/api/v1/public/library/crop-files?${query.toString()}`)
  if (!response.ok) {
    throw new Error('crop_library_files_unavailable')
  }

  const payload = (await response.json()) as CropLibraryFilesResponse
  return payload.data ?? []
}

/** Authenticated Library Page file browse (complete Library). */
export async function fetchAuthenticatedLibraryFiles(
  params: {
    plantProductionCategoryId: string
    fieldCropId: string
    libraryFileSection: string
  },
  token: string,
): Promise<CropLibraryFileRecord[]> {
  const query = new URLSearchParams({
    plant_production_category_id: params.plantProductionCategoryId,
    field_crop_id: params.fieldCropId,
    library_file_section: params.libraryFileSection,
  })

  const response = await fetch(`/api/v1/library/files?${query.toString()}`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  })
  if (response.status === 401) {
    throw new Error('library_files_unauthorized')
  }
  if (!response.ok) {
    throw new Error('library_files_unavailable')
  }

  const payload = (await response.json()) as CropLibraryFilesResponse
  return payload.data ?? []
}

/** @deprecated Prefer authenticated blob fetch for Library Page previews. */
export function buildCropLibraryFileContentUrl(fileId: number): string {
  const query = new URLSearchParams({
    organization: publicOrgSlug(),
  })
  return `/api/v1/public/library/crop-files/${fileId}/content?${query.toString()}`
}

export function authenticatedLibraryFileContentPath(fileId: number): string {
  return `/api/v1/library/files/${fileId}/content`
}

export async function fetchAuthenticatedLibraryFileBlob(
  fileId: number,
  token: string,
): Promise<Blob> {
  const response = await fetch(authenticatedLibraryFileContentPath(fileId), {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  })
  if (response.status === 401) {
    throw new Error('library_file_unauthorized')
  }
  if (!response.ok) {
    throw new Error('library_file_unavailable')
  }

  return response.blob()
}
