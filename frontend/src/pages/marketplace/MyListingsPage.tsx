import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Navigate, useLocation } from 'react-router-dom'
import {
  deleteListing,
  fetchMyListings,
  submitListing,
  unpublishListing,
  type OwnerListing,
} from '../../api/marketplace'
import { ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'
import { internalPaths } from '../../navigation/paths'
import { marketplaceLoginHref } from '../../navigation/roleDestinations'
import { SellerListingsView } from './SellerListingsView'
import { SellerProductForm } from './SellerProductForm'
import {
  closedEditor,
  deleteSellerListing,
  openCreateEditor,
  openEditEditor,
  type SellerEditorState,
} from './sellerListingsActions'

export function MyListingsPage() {
  const { t, i18n } = useTranslation()
  const location = useLocation()
  const { token, organizationId, user } = useAuth()
  const { can, loading: permissionsLoading } = usePermissions()
  const [page, setPage] = useState(1)
  const [notice, setNotice] = useState('')
  const [noticeIsError, setNoticeIsError] = useState(false)
  const [editor, setEditor] = useState<SellerEditorState>(closedEditor())
  const [pendingDelete, setPendingDelete] = useState<OwnerListing | null>(null)
  const language = i18n.language ?? 'ar'

  const { data: payload, loading, error, reload } = useAsyncData(async () => {
    if (!token || !can('market.view')) return null
    return fetchMyListings(token, organizationId ?? undefined, page)
  }, [token, organizationId, page, can])

  useEffect(() => {
    const key = (location.state as { productNotice?: string } | null)?.productNotice
    if (key === 'created') {
      setNotice(t('market.created'))
      setNoticeIsError(false)
    }
    if (key === 'updated') {
      setNotice(t('market.updated'))
      setNoticeIsError(false)
    }
  }, [location.state, t])

  if (!token) {
    return <Navigate to={marketplaceLoginHref(internalPaths.products)} replace />
  }

  if (permissionsLoading) {
    return <p className="loading">{t('errors.checkingAccess')}</p>
  }

  if (!can('market.view')) {
    return <ErrorBanner message={t('market.noPermissionView')} />
  }

  const showNotice = (message: string, isError = false) => {
    setNotice(message)
    setNoticeIsError(isError)
  }

  const openCreate = () => {
    setPendingDelete(null)
    showNotice('')
    setEditor(openCreateEditor())
  }

  const openEdit = (listing: OwnerListing) => {
    setPendingDelete(null)
    showNotice('')
    setEditor(openEditEditor(listing))
  }

  const closeForm = () => {
    setEditor(closedEditor())
  }

  const runHideOrPublish = async (listing: OwnerListing, action: 'submit' | 'hide') => {
    showNotice('')
    try {
      if (action === 'hide') {
        if (!window.confirm(t('market.confirmHide'))) return
        await unpublishListing(token, listing.id, organizationId ?? undefined)
        showNotice(t('market.hidden'))
      } else {
        await submitListing(token, listing.id, organizationId ?? undefined)
        showNotice(t('market.submitted'))
      }
      await reload()
    } catch (requestError) {
      showNotice(translateApiError(requestError) || t('market.actionFailed'), true)
    }
  }

  const confirmDelete = async () => {
    const listing = pendingDelete
    if (!listing) return
    setPendingDelete(null)
    const result = await deleteSellerListing({
      confirmed: true,
      token,
      listingId: listing.id,
      organizationId: organizationId ?? undefined,
      deleteListing,
    })
    if (!result.ok) {
      showNotice(translateApiError(result.error) || t('market.actionFailed'), true)
      return
    }
    showNotice(t('market.deleted'))
    if (editor.status === 'edit' && editor.listing.id === listing.id) {
      setEditor(closedEditor())
    }
    await reload()
  }

  const listings = payload?.data ?? []

  return (
    <SellerListingsView
      listings={listings}
      loading={loading}
      error={error || undefined}
      onRetry={() => void reload()}
      page={payload?.current_page ?? page}
      lastPage={payload?.last_page ?? 1}
      total={payload?.total ?? 0}
      onPageChange={setPage}
      editor={editor}
      pendingDelete={pendingDelete}
      notice={notice}
      noticeIsError={noticeIsError}
      language={language}
      onAddProduct={openCreate}
      onEditProduct={openEdit}
      onRequestDelete={(listing) => setPendingDelete(listing)}
      onCancelDelete={() => setPendingDelete(null)}
      onConfirmDelete={() => void confirmDelete()}
      onHide={(listing) => void runHideOrPublish(listing, 'hide')}
      onPublish={(listing) => void runHideOrPublish(listing, 'submit')}
      form={(
        <SellerProductForm
          key={editor.status === 'edit' ? `edit-${editor.listing.id}` : 'create'}
          listing={editor.status === 'edit' ? editor.listing : null}
          token={token}
          organizationId={organizationId}
          sellerDisplayName={user?.name}
          saveLabel={t('common.save')}
          cancelLabel={t('common.cancel')}
          onCancel={closeForm}
          onSaved={async (_listing, kind) => {
            showNotice(kind === 'created' ? t('market.created') : t('market.updated'))
            setEditor(closedEditor())
            await reload()
          }}
        />
      )}
    />
  )
}
