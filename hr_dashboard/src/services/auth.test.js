import { beforeEach, describe, expect, it, vi } from 'vitest'

import { authStore } from './auth.js'

describe('authStore', () => {
  beforeEach(() => {
    window.sessionStorage.clear()
    authStore.clear()
  })

  it('mulai tanpa key sehingga dashboard terkunci', () => {
    expect(authStore.getKey()).toBe('')
    expect(authStore.isAuthenticated()).toBe(false)
  })

  it('menyimpan key dan menandai terautentikasi', () => {
    authStore.setKey('  rahasia  ')

    expect(authStore.getKey()).toBe('rahasia')
    expect(authStore.isAuthenticated()).toBe(true)
    expect(window.sessionStorage.getItem('hr_dashboard_api_key')).toBe('rahasia')
  })

  it('clear menghapus key dari store dan sessionStorage', () => {
    authStore.setKey('rahasia')
    authStore.clear()

    expect(authStore.isAuthenticated()).toBe(false)
    expect(window.sessionStorage.getItem('hr_dashboard_api_key')).toBeNull()
  })

  it('memberi tahu subscriber saat key berubah', () => {
    const listener = vi.fn()
    const unsubscribe = authStore.subscribe(listener)

    authStore.setKey('rahasia')
    authStore.clear()
    unsubscribe()
    authStore.setKey('lagi')

    expect(listener).toHaveBeenCalledTimes(2)
  })
})
