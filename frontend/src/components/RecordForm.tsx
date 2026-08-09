import { useState, type FormEvent } from 'react'

type Field = {
  name: string
  label: string
  type?: string
  required?: boolean
  placeholder?: string
}

type RecordFormProps = {
  title: string
  fields: Field[]
  initial?: Record<string, string>
  submitLabel?: string
  onSubmit: (values: Record<string, string>) => Promise<void>
}

export function RecordForm({ title, fields, initial = {}, submitLabel = 'Save', onSubmit }: RecordFormProps) {
  const [values, setValues] = useState<Record<string, string>>(initial)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setLoading(true)
    setError('')
    try {
      await onSubmit(values)
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Unable to save record.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <form className="record-form" onSubmit={handleSubmit}>
      <h3>{title}</h3>
      {fields.map((field) => (
        <label key={field.name}>
          {field.label}
          <input
            type={field.type ?? 'text'}
            required={field.required}
            placeholder={field.placeholder}
            value={values[field.name] ?? ''}
            onChange={(event) => setValues({ ...values, [field.name]: event.target.value })}
          />
        </label>
      ))}
      {error && <p className="error">{error}</p>}
      <button type="submit" disabled={loading}>{loading ? 'Saving…' : submitLabel}</button>
    </form>
  )
}
