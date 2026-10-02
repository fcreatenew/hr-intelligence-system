import { useSyncExternalStore } from 'react'

import { authStore } from '../services/auth.js'

/**
 * Membaca status auth dari store agar komponen ikut render ulang saat
 * key di-set atau dihapus.
 */
export function useAuth() {
  const apiKey = useSyncExternalStore(
    authStore.subscribe,
    authStore.getKey,
    authStore.getKey,
  )

  return {
    apiKey,
    isAuthenticated: apiKey.length > 0,
    setKey: (key) => authStore.setKey(key),
    logout: () => authStore.clear(),
  }
}
