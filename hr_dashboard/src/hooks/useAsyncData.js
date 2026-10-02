import { useCallback, useEffect, useState } from 'react'

/**
 * Hook pemuatan data async dengan status loading/error dan opsi refetch.
 * Dipakai seluruh halaman agar pola loading konsisten.
 */
export function useAsyncData(loader, deps = []) {
  const [state, setState] = useState({ data: null, loading: true, error: null })

  const run = useCallback(async () => {
    setState((prev) => ({ ...prev, loading: true, error: null }))

    try {
      const data = await loader()
      setState({ data, loading: false, error: null })
    } catch (error) {
      setState({ data: null, loading: false, error: error.message })
    }
    // deps sengaja dinamis (dilewatkan pemanggil); rule compiler tidak bisa
    // memverifikasi array literal. Warning/error ini disengaja.
    // eslint-disable-next-line react-hooks/exhaustive-deps, react-hooks/use-memo
  }, deps)

  useEffect(() => {
    let active = true

    ;(async () => {
      try {
        const data = await loader()
        if (active) setState({ data, loading: false, error: null })
      } catch (error) {
        if (active) setState({ data: null, loading: false, error: error.message })
      }
    })()

    return () => {
      active = false
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps)

  return { ...state, refetch: run }
}
