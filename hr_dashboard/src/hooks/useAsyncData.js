import { useCallback, useEffect, useRef, useState } from 'react'

/**
 * Hook pemuatan data async dengan status loading/error dan opsi refetch.
 * Dipakai seluruh halaman agar pola loading konsisten.
 *
 * `deps` adalah daftar dependensi (array literal) yang menentukan kapan data
 * dimuat ulang — mis. `[model.use_case]` saat halaman berpindah model.
 * `loader` disimpan di ref supaya fungsi anonim dari pemanggil tidak memicu
 * pemuatan berulang setiap render.
 */
export function useAsyncData(loader, deps = []) {
  const [state, setState] = useState({ data: null, loading: true, error: null })

  const loaderRef = useRef(loader)
  const key = JSON.stringify(deps)

  // Sinkronkan loader terbaru di effect, bukan saat render (aturan react-hooks).
  useEffect(() => {
    loaderRef.current = loader
  })

  const run = useCallback(async () => {
    setState((prev) => ({ ...prev, loading: true, error: null }))

    try {
      const data = await loaderRef.current()
      setState({ data, loading: false, error: null })
    } catch (error) {
      setState({ data: null, loading: false, error: error.message })
    }
  }, [])

  useEffect(() => {
    let active = true

    ;(async () => {
      try {
        const data = await loaderRef.current()
        if (active) setState({ data, loading: false, error: null })
      } catch (error) {
        if (active) setState({ data: null, loading: false, error: error.message })
      }
    })()

    return () => {
      active = false
    }
  }, [key])

  return { ...state, refetch: run }
}
