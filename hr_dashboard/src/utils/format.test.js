import { describe, expect, it } from 'vitest'

import {
  fmtDecimal,
  fmtInt,
  fmtPercent,
  fmtRupiah,
  levelClass,
} from './format.js'

describe('format angka', () => {
  it('memformat integer dengan pemisah ribuan Indonesia', () => {
    expect(fmtInt(1200)).toBe('1.200')
    expect(fmtInt(null)).toBe('0')
  })

  it('memformat rupiah', () => {
    expect(fmtRupiah(1500000)).toBe('Rp 1.500.000')
  })

  it('memformat persen dan desimal', () => {
    expect(fmtPercent(0.5123)).toBe('51.2%')
    expect(fmtDecimal(0.5)).toBe('0.50')
  })
})

describe('levelClass', () => {
  it('memetakan level risiko ke kelas badge', () => {
    expect(levelClass('Tinggi')).toBe('badge--high')
    expect(levelClass('Sedang')).toBe('badge--mid')
    expect(levelClass('Rendah')).toBe('badge--low')
    expect(levelClass(undefined)).toBe('badge--low')
  })
})
