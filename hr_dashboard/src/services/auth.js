/**
 * Penyimpanan API key dashboard.
 *
 * Dashboard menampilkan data payroll dan anomali gaji, jadi halaman tidak
 * boleh terbuka tanpa kredensial. Key disimpan di sessionStorage (hilang saat
 * tab ditutup) dan dikirim ke API lewat header `X-API-Key`.
 *
 * `VITE_HR_API_KEY` hanya untuk bootstrap dev supaya tidak perlu login tiap
 * kali; kosongkan di produksi.
 */

const STORAGE_KEY = 'hr_dashboard_api_key'
const listeners = new Set()

function readInitial() {
  if (typeof window === 'undefined') {
    return import.meta.env.VITE_HR_API_KEY ?? ''
  }

  return (
    window.sessionStorage.getItem(STORAGE_KEY) ??
    import.meta.env.VITE_HR_API_KEY ??
    ''
  )
}

let currentKey = readInitial()

function emit() {
  for (const listener of listeners) {
    listener()
  }
}

export const authStore = {
  getKey: () => currentKey,

  isAuthenticated: () => currentKey.length > 0,

  setKey(key) {
    currentKey = key.trim()

    if (typeof window !== 'undefined') {
      if (currentKey) {
        window.sessionStorage.setItem(STORAGE_KEY, currentKey)
      } else {
        window.sessionStorage.removeItem(STORAGE_KEY)
      }
    }

    emit()
  },

  clear() {
    authStore.setKey('')
  },

  subscribe(listener) {
    listeners.add(listener)
    return () => listeners.delete(listener)
  },
}
