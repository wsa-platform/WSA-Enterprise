import { useEffect, useId, useMemo, useRef, useState, type KeyboardEvent as ReactKeyboardEvent } from 'react'
import { countryLabel, filterCountries, isIsoCountryCode } from './countries'

export function CountryCombobox({
  id,
  name,
  value,
  onChange,
  required,
  placeholder,
  searchPlaceholder,
  locale,
}: {
  id: string
  name: string
  value: string
  onChange: (value: string) => void
  required?: boolean
  placeholder: string
  searchPlaceholder: string
  locale: string
}) {
  const listId = useId()
  const searchId = `${id}-search`
  const rootRef = useRef<HTMLDivElement>(null)
  const searchRef = useRef<HTMLInputElement>(null)
  const [open, setOpen] = useState(false)
  const [query, setQuery] = useState('')
  const [activeIndex, setActiveIndex] = useState(0)
  const [dropUp, setDropUp] = useState(false)
  const options = useMemo(() => {
    const list = filterCountries(locale, query)
    if (value && !isIsoCountryCode(value) && !query.trim()) {
      return [{ code: value, label: countryLabel(value, locale) }, ...list]
    }
    return list
  }, [locale, query, value])
  const selectedLabel = value ? countryLabel(value, locale) : ''

  useEffect(() => {
    if (!open) return
    const handlePointer = (event: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(event.target as Node)) {
        setOpen(false)
        setQuery('')
      }
    }
    const handleEscape = (event: globalThis.KeyboardEvent) => {
      if (event.key === 'Escape') {
        setOpen(false)
        setQuery('')
      }
    }
    document.addEventListener('mousedown', handlePointer)
    document.addEventListener('keydown', handleEscape)
    return () => {
      document.removeEventListener('mousedown', handlePointer)
      document.removeEventListener('keydown', handleEscape)
    }
  }, [open])

  useEffect(() => {
    if (!open) return
    window.setTimeout(() => searchRef.current?.focus(), 0)
  }, [open])

  useEffect(() => {
    if (!open) {
      setDropUp(false)
      return
    }
    const frame = window.requestAnimationFrame(() => {
      const trigger = rootRef.current?.querySelector('.country-combobox-trigger')
      const panel = rootRef.current?.querySelector('.country-combobox-panel')
      if (!trigger || !panel) return
      const triggerBox = trigger.getBoundingClientRect()
      const spaceBelow = window.innerHeight - triggerBox.bottom
      const spaceAbove = triggerBox.top
      const needed = Math.min(panel.getBoundingClientRect().height || 280, 280)
      setDropUp(spaceBelow < needed + 8 && spaceAbove > spaceBelow)
    })
    return () => window.cancelAnimationFrame(frame)
  }, [open, options])

  useEffect(() => {
    if (!open) return
    const selected = options.findIndex((item) => item.code === value)
    setActiveIndex(selected >= 0 ? selected : 0)
  }, [open, options, value])

  useEffect(() => {
    if (!open) return
    const active = rootRef.current?.querySelector<HTMLElement>(`[data-option-index="${activeIndex}"]`)
    active?.scrollIntoView({ block: 'nearest' })
  }, [activeIndex, open])

  const selectOption = (code: string) => {
    onChange(code)
    setOpen(false)
    setQuery('')
  }

  const handleTriggerKeyDown = (event: ReactKeyboardEvent<HTMLButtonElement>) => {
    if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
      event.preventDefault()
      setOpen(true)
    }
  }

  const handleSearchKeyDown = (event: ReactKeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      setActiveIndex((index) => Math.min(index + 1, Math.max(options.length - 1, 0)))
    } else if (event.key === 'ArrowUp') {
      event.preventDefault()
      setActiveIndex((index) => Math.max(index - 1, 0))
    } else if (event.key === 'Enter') {
      event.preventDefault()
      const option = options[activeIndex]
      if (option) selectOption(option.code)
    }
  }

  return (
    <div
      className="country-combobox"
      ref={rootRef}
      data-testid={`country-combobox-${name}`}
      data-open={open ? 'true' : 'false'}
    >
      <input type="hidden" name={name} value={value} required={required} />
      <button
        type="button"
        id={id}
        className={`country-combobox-trigger${value ? '' : ' is-placeholder'}`}
        aria-haspopup="listbox"
        aria-expanded={open}
        aria-controls={listId}
        aria-label={placeholder}
        onClick={() => setOpen((current) => !current)}
        onKeyDown={handleTriggerKeyDown}
      >
        <span>{selectedLabel || placeholder}</span>
        <span aria-hidden="true">▾</span>
      </button>
      {open && (
        <div
          className={`country-combobox-panel${dropUp ? ' is-drop-up' : ''}`}
          onMouseDown={(event) => event.stopPropagation()}
        >
          <label className="country-combobox-search" htmlFor={searchId}>
            <input
              ref={searchRef}
              id={searchId}
              type="search"
              value={query}
              placeholder={searchPlaceholder}
              onChange={(event) => {
                setQuery(event.target.value)
                setActiveIndex(0)
              }}
              onMouseDown={(event) => event.stopPropagation()}
              onKeyDown={handleSearchKeyDown}
              dir="auto"
              autoComplete="off"
            />
          </label>
          <ul id={listId} className="country-combobox-list" role="listbox">
            {options.map((item, index) => (
              <li key={`${item.code}-${index}`} role="presentation">
                <button
                  type="button"
                  role="option"
                  data-option-index={index}
                  className={`country-combobox-option${item.code === value ? ' is-selected' : ''}${index === activeIndex ? ' is-active' : ''}`}
                  aria-selected={item.code === value}
                  onMouseEnter={() => setActiveIndex(index)}
                  onClick={() => selectOption(item.code)}
                >
                  {item.label}
                </button>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  )
}
