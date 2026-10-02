/** Pemformat angka dan label bersama untuk seluruh dashboard. */

const nf = new Intl.NumberFormat('id-ID')
const nfCompact = new Intl.NumberFormat('id-ID', {
  notation: 'compact',
  maximumFractionDigits: 1,
})

export const fmtInt = (value) => nf.format(Math.round(value ?? 0))

export const fmtCompact = (value) => nfCompact.format(value ?? 0)

export const fmtRupiah = (value) => `Rp ${nf.format(Math.round(value ?? 0))}`

export const fmtRupiahCompact = (value) => `Rp ${nfCompact.format(value ?? 0)}`

export const fmtPercent = (value, digits = 1) =>
  `${((value ?? 0) * 100).toFixed(digits)}%`

export const fmtDecimal = (value, digits = 2) =>
  (value ?? 0).toFixed(digits)

/** Kelas badge berdasarkan tingkat risiko/level. */
export function levelClass(level) {
  const normalized = String(level ?? '').toLowerCase()

  if (['tinggi', 'anomali', 'high', 'kritis'].includes(normalized)) return 'badge--high'
  if (['sedang', 'perlu ditinjau', 'mid', 'peringatan'].includes(normalized)) return 'badge--mid'

  return 'badge--low'
}

/** Warna grafik yang konsisten dengan palet CSS. */
export const chartColors = {
  amber: '#e8a33d',
  rust: '#b4472e',
  sage: '#7d8f6a',
  sky: '#5d7f96',
  paper: '#f4ede1',
  dim: '#cfc4b2',
  grid: 'rgba(244,237,225,0.12)',
}

export const seriesPalette = [
  chartColors.amber,
  chartColors.sky,
  chartColors.sage,
  chartColors.rust,
]
