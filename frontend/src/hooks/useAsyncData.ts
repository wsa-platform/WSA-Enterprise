import { useCallback, useEffect, useRef, useState } from 'react'

export function useAsyncData<T>(
  loader: () => Promise<T>,
  deps: ReadonlyArray<unknown>,
) {
  const [data, setData] = useState<T | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const dataRef = useRef<T | null>(null)
  dataRef.current = data

  const reload = useCallback(async () => {
    if (dataRef.current == null) setLoading(true)
    setError('')
    try {
      const next = await loader()
      setData(next)
    } catch (requestError) {
      if (dataRef.current == null) setData(null)
      setError(requestError instanceof Error ? requestError.message : 'Unable to load data.')
    } finally {
      setLoading(false)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps -- caller supplies stable dependency list
  }, deps)

  useEffect(() => {
    void reload()
  }, [reload])

  return { data, loading, error, reload, setData }
}
