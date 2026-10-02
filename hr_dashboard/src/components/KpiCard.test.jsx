import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'

import KpiCard from './KpiCard.jsx'

describe('KpiCard', () => {
  it('menampilkan label, nilai, dan hint', () => {
    render(<KpiCard label="Total Payroll" value="Rp 1,2 M" hint="2025" />)

    expect(screen.getByText('Total Payroll')).toBeInTheDocument()
    expect(screen.getByText('Rp 1,2 M')).toBeInTheDocument()
    expect(screen.getByText('2025')).toBeInTheDocument()
  })

  it('tidak menampilkan hint bila kosong', () => {
    const { container } = render(<KpiCard label="X" value="1" />)

    expect(container.querySelector('.card__hint')).toBeNull()
  })
})
