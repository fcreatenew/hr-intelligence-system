import { beforeEach, describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'

import App from './App.jsx'
import { authStore } from './services/auth.js'

function renderApp() {
  return render(
    <MemoryRouter>
      <App />
    </MemoryRouter>,
  )
}

describe('App gate', () => {
  beforeEach(() => {
    window.sessionStorage.clear()
    authStore.clear()
  })

  it('menampilkan halaman masuk saat belum ada API key', () => {
    renderApp()

    expect(screen.getByLabelText(/api key/i)).toBeInTheDocument()
    expect(screen.queryByText('Ringkasan')).not.toBeInTheDocument()
  })

  it('menampilkan konsol setelah API key dimasukkan', async () => {
    const user = userEvent.setup()
    renderApp()

    await user.type(screen.getByLabelText(/api key/i), 'rahasia')
    await user.click(screen.getByRole('button', { name: /masuk/i }))

    expect(await screen.findByText('Ringkasan')).toBeInTheDocument()
    expect(screen.queryByLabelText(/api key/i)).not.toBeInTheDocument()
  })

  it('menolak submit dengan key kosong', async () => {
    const user = userEvent.setup()
    renderApp()

    await user.click(screen.getByRole('button', { name: /masuk/i }))

    expect(screen.getByText(/tidak boleh kosong/i)).toBeInTheDocument()
    expect(authStore.isAuthenticated()).toBe(false)
  })
})
