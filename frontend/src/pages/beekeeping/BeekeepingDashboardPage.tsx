import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  createApiary,
  createCalendarTask,
  createPollinationPlant,
  getBeekeeperProfile,
  listApiaries,
  listCalendarTasks,
  listPollinationPlants,
  upsertBeekeeperProfile,
  type Apiary,
  type BeeCalendarTask,
  type PollinationPlant,
} from '../../api/beekeeping'
import { ModuleTabs } from '../../components/AppShell'
import { DataTable, PaginationBar } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'
import i18n from '../../i18n/config'

type BeekeepingTab = 'profile' | 'apiaries' | 'calendar' | 'plants'

export function BeekeepingDashboardPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [activeTab, setActiveTab] = useState<BeekeepingTab>('profile')
  const [message, setMessage] = useState('')
  const [apiaryPage, setApiaryPage] = useState(1)
  const [calendarPage, setCalendarPage] = useState(1)
  const [plantsPage, setPlantsPage] = useState(1)

  const [displayName, setDisplayName] = useState('')
  const [country, setCountry] = useState('')
  const [location, setLocation] = useState('')
  const [hiveCount, setHiveCount] = useState('')
  const [notes, setNotes] = useState('')

  const [apiaryName, setApiaryName] = useState('')
  const [taskTitle, setTaskTitle] = useState('')
  const [taskType, setTaskType] = useState('inspection')
  const [plantSpecies, setPlantSpecies] = useState('')

  const canManage = can('beekeeping.manage')

  const tabs = [
    { label: t('beekeeping.tabProfile'), path: 'profile' },
    { label: t('beekeeping.tabApiaries'), path: 'apiaries' },
    { label: t('beekeeping.tabCalendar'), path: 'calendar' },
    { label: t('beekeeping.tabPlants'), path: 'plants' },
  ]

  const { data: profile, reload: reloadProfile } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getBeekeeperProfile(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: apiariesPayload, loading: apiariesLoading, error: apiariesError, reload: reloadApiaries } = useAsyncData(async () => {
    if (!token || activeTab !== 'apiaries') return null
    return listApiaries(token, organizationId ?? undefined, apiaryPage)
  }, [token, organizationId, activeTab, apiaryPage])

  const { data: calendarPayload, loading: calendarLoading, error: calendarError, reload: reloadCalendar } = useAsyncData(async () => {
    if (!token || activeTab !== 'calendar') return null
    return listCalendarTasks(token, organizationId ?? undefined, calendarPage)
  }, [token, organizationId, activeTab, calendarPage])

  const { data: plantsPayload, loading: plantsLoading, error: plantsError, reload: reloadPlants } = useAsyncData(async () => {
    if (!token || activeTab !== 'plants') return null
    return listPollinationPlants(token, organizationId ?? undefined, plantsPage)
  }, [token, organizationId, activeTab, plantsPage])

  useEffect(() => {
    if (!profile) return
    setDisplayName(profile.display_name ?? '')
    setCountry(profile.country ?? '')
    setLocation(profile.location ?? '')
    setHiveCount(profile.hive_count != null ? String(profile.hive_count) : '')
    setNotes(profile.notes ?? '')
  }, [profile])

  if (!can('beekeeping.view')) {
    return <ErrorBanner message={t('beekeeping.noPermissionView')} />
  }

  const saveProfile = async () => {
    if (!token || !canManage) return
    setMessage('')
    try {
      await upsertBeekeeperProfile(token, {
        display_name: displayName,
        country: country || undefined,
        location: location || undefined,
        hive_count: hiveCount ? Number(hiveCount) : undefined,
        notes: notes || undefined,
      }, organizationId ?? undefined)
      setMessage(t('beekeeping.profileSaved'))
      await reloadProfile()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('beekeeping.profileSaveFailed'))
    }
  }

  const addApiary = async () => {
    if (!token || !canManage || !profile?.id || !apiaryName) return
    setMessage('')
    try {
      await createApiary(token, {
        beekeeper_profile_id: profile.id,
        name: apiaryName,
        country: country || undefined,
        location: location || undefined,
      }, organizationId ?? undefined)
      setApiaryName('')
      setMessage(t('beekeeping.apiaryCreated'))
      await reloadApiaries()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('beekeeping.apiaryCreateFailed'))
    }
  }

  const addTask = async () => {
    if (!token || !canManage || !taskTitle) return
    setMessage('')
    try {
      await createCalendarTask(token, {
        task_type: taskType,
        title: taskTitle,
      }, organizationId ?? undefined)
      setTaskTitle('')
      setMessage(t('beekeeping.taskCreated'))
      await reloadCalendar()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('beekeeping.taskCreateFailed'))
    }
  }

  const addPlant = async () => {
    if (!token || !canManage || !plantSpecies) return
    setMessage('')
    try {
      await createPollinationPlant(token, { species_name: plantSpecies }, organizationId ?? undefined)
      setPlantSpecies('')
      setMessage(t('beekeeping.plantCreated'))
      await reloadPlants()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('beekeeping.plantCreateFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('nav.ecosystem')}
      title={t('beekeeping.dashboardTitle')}
      description={t('beekeeping.dashboardDescription')}
    />

    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <ModuleTabs
        tabs={tabs.map((tab) => ({ label: tab.label, path: tab.path }))}
        activePath={activeTab}
        onSelect={(path) => setActiveTab(path as BeekeepingTab)}
      />

      {activeTab === 'profile' && (
        <div className="record-form">
          <label>
            {t('beekeeping.displayName')}
            <input value={displayName} onChange={(event) => setDisplayName(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('beekeeping.country')}
            <input value={country} onChange={(event) => setCountry(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('beekeeping.location')}
            <input value={location} onChange={(event) => setLocation(event.target.value)} dir="auto" />
          </label>
          <label>
            {t('beekeeping.hiveCount')}
            <input type="number" min={0} value={hiveCount} onChange={(event) => setHiveCount(event.target.value)} />
          </label>
          <label>
            {t('beekeeping.notes')}
            <textarea value={notes} onChange={(event) => setNotes(event.target.value)} rows={3} dir="auto" />
          </label>
          {canManage && (
            <button type="button" disabled={!displayName} onClick={() => void saveProfile()}>{t('common.save')}</button>
          )}
        </div>
      )}

      {activeTab === 'apiaries' && (
        <>
          {apiariesError && <ErrorBanner message={apiariesError} onRetry={reloadApiaries} />}
          {canManage && (
            <div className="record-form">
              <label>
                {t('beekeeping.apiaryName')}
                <input value={apiaryName} onChange={(event) => setApiaryName(event.target.value)} dir="auto" />
              </label>
              <button type="button" disabled={!apiaryName || !profile?.id} onClick={() => void addApiary()}>{t('common.create')}</button>
            </div>
          )}
          {apiariesLoading ? <p className="loading">{t('beekeeping.loadingApiaries')}</p> : (
            <>
              <DataTable<Apiary>
                rows={apiariesPayload?.data ?? []}
                rowKey={(row) => row.id}
                emptyLabel={t('beekeeping.noApiaries')}
                columns={[
                  { key: 'name', header: t('common.name'), render: (row) => row.name },
                  { key: 'location', header: t('beekeeping.location'), render: (row) => row.location ?? '—' },
                  { key: 'hives', header: t('beekeeping.hives'), render: (row) => row.hives_count ?? 0 },
                ]}
              />
              {apiariesPayload && (
                <PaginationBar page={apiariesPayload.current_page} lastPage={apiariesPayload.last_page} total={apiariesPayload.total} onPageChange={setApiaryPage} />
              )}
            </>
          )}
        </>
      )}

      {activeTab === 'calendar' && (
        <>
          {calendarError && <ErrorBanner message={calendarError} onRetry={reloadCalendar} />}
          {canManage && (
            <div className="record-form">
              <label>
                {t('beekeeping.taskType')}
                <input value={taskType} onChange={(event) => setTaskType(event.target.value)} dir="auto" />
              </label>
              <label>
                {t('common.title')}
                <input value={taskTitle} onChange={(event) => setTaskTitle(event.target.value)} dir="auto" />
              </label>
              <button type="button" disabled={!taskTitle} onClick={() => void addTask()}>{t('common.create')}</button>
            </div>
          )}
          {calendarLoading ? <p className="loading">{t('beekeeping.loadingTasks')}</p> : (calendarPayload?.data.length ?? 0) === 0 ? (
            <EmptyState title={t('beekeeping.noTasks')} description={t('beekeeping.noTasksDescription')} />
          ) : (
            <>
              <DataTable<BeeCalendarTask>
                rows={calendarPayload?.data ?? []}
                rowKey={(row) => row.id}
                columns={[
                  { key: 'title', header: t('common.title'), render: (row) => row.title },
                  { key: 'type', header: t('beekeeping.taskType'), render: (row) => row.task_type },
                  { key: 'scheduled', header: t('beekeeping.scheduledFor'), render: (row) => row.scheduled_for ?? '—' },
                ]}
              />
              {calendarPayload && (
                <PaginationBar page={calendarPayload.current_page} lastPage={calendarPayload.last_page} total={calendarPayload.total} onPageChange={setCalendarPage} />
              )}
            </>
          )}
        </>
      )}

      {activeTab === 'plants' && (
        <>
          {plantsError && <ErrorBanner message={plantsError} onRetry={reloadPlants} />}
          {canManage && (
            <div className="record-form">
              <label>
                {t('beekeeping.speciesName')}
                <input value={plantSpecies} onChange={(event) => setPlantSpecies(event.target.value)} dir="auto" />
              </label>
              <button type="button" disabled={!plantSpecies} onClick={() => void addPlant()}>{t('common.create')}</button>
            </div>
          )}
          {plantsLoading ? <p className="loading">{t('beekeeping.loadingPlants')}</p> : (
            <>
              <DataTable<PollinationPlant>
                rows={plantsPayload?.data ?? []}
                rowKey={(row) => row.id}
                emptyLabel={t('beekeeping.noPlants')}
                columns={[
                  { key: 'species', header: t('beekeeping.speciesName'), render: (row) => row.species_name },
                  { key: 'common', header: t('beekeeping.commonName'), render: (row) => row.common_name ?? '—' },
                  { key: 'relevance', header: t('beekeeping.relevance'), render: (row) => row.pollination_relevance ?? '—' },
                ]}
              />
              {plantsPayload && (
                <PaginationBar page={plantsPayload.current_page} lastPage={plantsPayload.last_page} total={plantsPayload.total} onPageChange={setPlantsPage} />
              )}
            </>
          )}
        </>
      )}
    </section>
  </>
}
