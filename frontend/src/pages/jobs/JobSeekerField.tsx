import { useRef, type ReactNode } from 'react'

export type JobSeekerFieldSize = 'short' | 'medium' | 'large' | 'full'

export function JobSeekerField({
  htmlFor,
  label,
  hint,
  value,
  error,
  className,
  size = 'medium',
  editing = false,
  dir,
  children,
}: {
  htmlFor?: string
  label: string
  hint?: string
  value?: string
  error?: string
  className?: string
  size?: JobSeekerFieldSize
  editing?: boolean
  dir?: 'auto' | 'ltr' | 'rtl'
  children?: ReactNode
}) {
  const rootRef = useRef<HTMLDivElement>(null)

  const focusControl = () => {
    const root = rootRef.current
    const control = root?.querySelector<HTMLElement>(
      'input:not([type=hidden]):not([type=search]), textarea, button.country-combobox-trigger',
    )
    control?.focus()
  }

  return (
    <div className={['js-field', `js-field-${size}`, className].filter(Boolean).join(' ')} ref={rootRef}>
      <label className="js-field-label" htmlFor={editing ? htmlFor : undefined}>
        <span className="js-field-label-text">{label}</span>
        {hint ? <span className="js-field-hint">{hint}</span> : null}
      </label>
      <div
        className={`js-field-value${editing ? ' is-editing' : ''}${size === 'full' ? ' is-multiline' : ''}`}
        onClick={(event) => {
          if (!editing) return
          const target = event.target as HTMLElement
          if (target.closest('input, textarea, button, select, a, label')) return
          focusControl()
        }}
      >
        {editing ? children : (
          <span className="js-field-text" dir={dir}>{value?.trim() ? value : '—'}</span>
        )}
      </div>
      {error ? <p className="js-field-error" role="alert">{error}</p> : null}
    </div>
  )
}
