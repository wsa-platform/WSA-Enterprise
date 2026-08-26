import { useEffect, useId, useRef, useState, type KeyboardEvent, type MouseEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { CROPS_CATEGORY_ITEMS, cropsMenuReducer } from './cropsMenu'

const LEAVE_CLOSE_MS = 160

type CropsCategoryMenuProps = {
  /** Test/SSR helper — when true, menu starts open. */
  defaultOpen?: boolean
}

/** Homepage category card for المحاصيل with an accessible flyout submenu. */
export function CropsCategoryMenu({ defaultOpen = false }: CropsCategoryMenuProps = {}) {
  const { t } = useTranslation()
  const menuId = useId()
  const rootRef = useRef<HTMLDivElement>(null)
  const leaveTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null)
  const [open, setOpen] = useState(defaultOpen)

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

  const onTriggerClick = (event: MouseEvent<HTMLButtonElement>) => {
    event.preventDefault()
    // Mouse: open (hover may already keep it open). Touch/pen: toggle for tap open/close.
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
      rootRef.current?.querySelector<HTMLButtonElement>('.gs-crops-menu-trigger')?.focus()
      return
    }
    if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
      const trigger = event.target as HTMLElement
      if (trigger.classList.contains('gs-crops-menu-trigger')) {
        if (event.key === 'ArrowDown' || (!open && (event.key === 'Enter' || event.key === ' '))) {
          event.preventDefault()
          dispatch({ type: 'open' })
          const firstItem = rootRef.current?.querySelector<HTMLAnchorElement>('.gs-crops-submenu-item')
          firstItem?.focus()
        }
      }
    }
  }

  return (
    <div
      ref={rootRef}
      className={`gs-crops-menu${open ? ' is-open' : ''}`}
      onMouseEnter={() => dispatch({ type: 'pointer_enter' })}
      onMouseLeave={() => dispatch({ type: 'pointer_leave_request' })}
      onFocusCapture={() => dispatch({ type: 'pointer_enter' })}
      onKeyDown={onRootKeyDown}
    >
      <button
        type="button"
        className="gs-category-card gs-crops-menu-trigger"
        style={{ ['--icon-bg' as string]: 'oklch(0.88 0.06 90)' }}
        aria-haspopup="menu"
        aria-expanded={open}
        aria-controls={menuId}
        onClick={onTriggerClick}
      >
        <div className="gs-cat-icon-wrap">
          <span aria-hidden="true">🌾</span>
        </div>
        <div className="gs-category-card-text">
          <h3>{t('website.cropsMenu.parent')}</h3>
          <p>{t('website.cropsMenu.description')}</p>
        </div>
      </button>

      <div
        id={menuId}
        className="gs-crops-submenu"
        role="menu"
        aria-label={t('website.cropsMenu.parent')}
        hidden={!open}
      >
        <ul className="gs-crops-submenu-list">
          {CROPS_CATEGORY_ITEMS.map((item) => (
            <li key={item.id} role="none">
              <Link
                role="menuitem"
                className="gs-crops-submenu-item"
                to={item.to}
                onClick={() => dispatch({ type: 'close' })}
              >
                <span className="gs-crops-submenu-icon" aria-hidden="true">{item.icon}</span>
                <span>{t(item.labelKey)}</span>
              </Link>
            </li>
          ))}
        </ul>
      </div>
    </div>
  )
}
