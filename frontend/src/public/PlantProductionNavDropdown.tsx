import { useEffect, useId, useRef, useState, type KeyboardEvent, type MouseEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useLocation } from 'react-router-dom'
import {
  PLANT_PRODUCTION_CATEGORY_ITEMS,
  cropsMenuReducer,
  isPlantProductionPath,
} from './plantProductionMenu'

const LEAVE_CLOSE_MS = 160

type PlantProductionNavDropdownProps = {
  onNavigate?: () => void
}

/** Header navigation dropdown for الإنتاج النباتي. */
export function PlantProductionNavDropdown({ onNavigate }: PlantProductionNavDropdownProps) {
  const { t } = useTranslation()
  const { pathname } = useLocation()
  const menuId = useId()
  const rootRef = useRef<HTMLDivElement>(null)
  const leaveTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null)
  const [open, setOpen] = useState(false)
  const active = isPlantProductionPath(pathname)

  const clearLeaveTimer = () => {
    if (leaveTimerRef.current) {
      clearTimeout(leaveTimerRef.current)
      leaveTimerRef.current = null
    }
  }

  const dispatch = (action: Parameters<typeof cropsMenuReducer>[1]) => {
    if (action.type === 'pointer_enter') clearLeaveTimer()
    if (action.type === 'pointer_leave_request') {
      clearLeaveTimer()
      leaveTimerRef.current = setTimeout(() => {
        setOpen((current) => cropsMenuReducer(current, { type: 'pointer_leave_commit' }))
      }, LEAVE_CLOSE_MS)
      return
    }
    setOpen((current) => cropsMenuReducer(current, action))
  }

  useEffect(() => () => clearLeaveTimer(), [])

  useEffect(() => {
    if (!open) return
    const onPointerDown = (event: PointerEvent) => {
      if (!rootRef.current?.contains(event.target as Node)) {
        dispatch({ type: 'close' })
      }
    }
    document.addEventListener('pointerdown', onPointerDown)
    return () => document.removeEventListener('pointerdown', onPointerDown)
  }, [open])

  useEffect(() => {
    dispatch({ type: 'close' })
  }, [pathname])

  const onTriggerClick = (event: MouseEvent<HTMLButtonElement>) => {
    event.preventDefault()
    const pointerType = (event.nativeEvent as PointerEvent).pointerType
    if (pointerType === 'touch' || pointerType === 'pen') {
      dispatch({ type: 'toggle' })
      return
    }
    dispatch({ type: 'open' })
  }

  const onRootKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
    if (event.key === 'Escape') {
      event.preventDefault()
      dispatch({ type: 'escape' })
      rootRef.current?.querySelector<HTMLButtonElement>('.gs-nav-dropdown-trigger')?.focus()
      return
    }
    if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
      const trigger = event.target as HTMLElement
      if (trigger.classList.contains('gs-nav-dropdown-trigger')) {
        if (event.key === 'ArrowDown' || (!open && (event.key === 'Enter' || event.key === ' '))) {
          event.preventDefault()
          dispatch({ type: 'open' })
          const firstItem = rootRef.current?.querySelector<HTMLAnchorElement>('.gs-nav-dropdown-item')
          firstItem?.focus()
        }
      }
    }
  }

  const closeAndNavigate = () => {
    dispatch({ type: 'close' })
    onNavigate?.()
  }

  return (
    <div
      ref={rootRef}
      className={`gs-nav-dropdown${open ? ' is-open' : ''}`}
      onMouseEnter={() => dispatch({ type: 'pointer_enter' })}
      onMouseLeave={() => dispatch({ type: 'pointer_leave_request' })}
      onFocusCapture={() => dispatch({ type: 'pointer_enter' })}
      onKeyDown={onRootKeyDown}
    >
      <button
        type="button"
        className={`gs-nav-dropdown-trigger${active ? ' active' : ''}`}
        aria-haspopup="menu"
        aria-expanded={open}
        aria-controls={menuId}
        aria-current={active ? 'page' : undefined}
        onClick={onTriggerClick}
      >
        {t('website.nav.plantProduction')}
      </button>

      <div
        id={menuId}
        className="gs-nav-dropdown-panel"
        role="menu"
        aria-label={t('website.nav.plantProduction')}
        hidden={!open}
      >
        <ul className="gs-nav-dropdown-list">
          {PLANT_PRODUCTION_CATEGORY_ITEMS.map((item) => (
            <li key={item.id} role="none">
              <Link
                role="menuitem"
                className="gs-nav-dropdown-item"
                to={item.to}
                onClick={closeAndNavigate}
              >
                <span className="gs-nav-dropdown-icon" aria-hidden="true">{item.icon}</span>
                <span>{t(item.labelKey)}</span>
              </Link>
            </li>
          ))}
        </ul>
      </div>
    </div>
  )
}
