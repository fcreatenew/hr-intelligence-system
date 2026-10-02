import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'

import Badge from './Badge.jsx'

describe('Badge', () => {
  it('menampilkan teks level dan kelas warnanya', () => {
    render(<Badge level="Tinggi" />)

    const el = screen.getByText('Tinggi')
    expect(el).toHaveClass('badge', 'badge--high')
  })

  it('memakai children bila diberikan', () => {
    render(<Badge level="Rendah">Aman</Badge>)

    expect(screen.getByText('Aman')).toHaveClass('badge--low')
  })
})
