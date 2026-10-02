/**
 * Klien tipis untuk HR Intelligence API dan Payroll Intelligence API.
 *
 * Dua mode:
 * - live  : memanggil API sungguhan (proxy Vite di dev).
 * - demo  : memakai data contoh deterministik di `fixtures.js`, sehingga
 *           dashboard tetap dapat dibuka tanpa backend berjalan.
 *
 * Mode dipilih lewat VITE_DATA_MODE; default "demo" agar halaman tidak pernah
 * kosong saat backend belum dinyalakan.
 */

import { fixtures } from './fixtures.js'

const MODE = import.meta.env.VITE_DATA_MODE ?? 'demo'
const HR_BASE = import.meta.env.VITE_HR_API_BASE ?? '/api'
const PAYROLL_BASE = import.meta.env.VITE_PAYROLL_API_BASE ?? '/payroll-api'

export const dataMode = MODE

async function getJSON(base, path, { timeout = 8000 } = {}) {
  const controller = new AbortController()
  const timer = setTimeout(() => controller.abort(), timeout)

  try {
    const response = await fetch(`${base}${path}`, {
      signal: controller.signal,
      headers: { Accept: 'application/json' },
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status} untuk ${path}`)
    }

    return await response.json()
  } finally {
    clearTimeout(timer)
  }
}

async function safe(base, path, fallback, options) {
  if (MODE === 'demo') {
    return fallback
  }

  try {
    return await getJSON(base, path, options)
  } catch (error) {
    // Jangan gagalkan seluruh dashboard hanya karena satu endpoint mati.
    console.warn(`[hr-dashboard] gagal memuat ${path}:`, error.message)
    return { ...fallback, __degraded: true }
  }
}

export const hrApi = {
  health: () => safe(HR_BASE, '/health', fixtures.health),

  // API mengembalikan { use_cases: [...] }; dinormalkan menjadi array.
  models: async () => {
    const data = await safe(HR_BASE, '/model', {
      use_cases: fixtures.models,
    })

    return Array.isArray(data) ? data : data.use_cases ?? []
  },

  modelInfo: (useCase) =>
    safe(
      HR_BASE,
      `/model/${useCase}/info`,
      fixtures.modelInfo[useCase] ?? {},
    ),
}

export const payrollApi = {
  summary: () => safe(PAYROLL_BASE, '/summary', fixtures.payroll.summary),
  departments: () =>
    safe(PAYROLL_BASE, '/departments', fixtures.payroll.departments),
  periods: () => safe(PAYROLL_BASE, '/periods', fixtures.payroll.periods),
  anomalies: () =>
    safe(PAYROLL_BASE, '/anomalies', fixtures.payroll.anomalies),
  forecast: () =>
    safe(PAYROLL_BASE, '/forecast/payroll', fixtures.payroll.forecast),
}
