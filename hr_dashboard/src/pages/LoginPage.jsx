import { useState } from 'react'

import PageHead from '../components/PageHead.jsx'
import { useAuth } from '../hooks/useAuth.js'
import { dataMode } from '../services/api.js'

/**
 * Gerbang masuk dashboard. Key tidak diverifikasi di sini — validasi nyata
 * terjadi saat request pertama ke API (401 akan tampil sebagai data terdegradasi),
 * karena hanya backend yang memegang kebenaran kunci.
 */
export default function LoginPage() {
  const { setKey } = useAuth()
  const [value, setValue] = useState('')
  const [error, setError] = useState('')

  function handleSubmit(event) {
    event.preventDefault()

    if (!value.trim()) {
      setError('API key tidak boleh kosong.')
      return
    }

    setKey(value)
  }

  return (
    <>
      <PageHead
        kicker="Akses Terbatas"
        title="Masuk"
        accent="console"
        note="Dashboard menampilkan data payroll dan anomali gaji. Masukkan API key HR Intelligence untuk melanjutkan."
      />

      <div className="card" style={{ maxWidth: 460 }}>
        <form onSubmit={handleSubmit}>
          <div className="field">
            <label htmlFor="api-key">API Key</label>
            <input
              id="api-key"
              type="password"
              autoComplete="off"
              value={value}
              onChange={(event) => {
                setValue(event.target.value)
                setError('')
              }}
              placeholder="X-API-Key"
            />
            {error ? <div className="error">{error}</div> : null}
            <div className="hint">
              Dikirim sebagai header <code>X-API-Key</code> ke HR Intelligence API.
            </div>
          </div>

          <button type="submit" className="btn">
            Masuk
          </button>

          {dataMode === 'demo' ? (
            <div className="hint" style={{ marginTop: '1rem' }}>
              Mode data saat ini <strong>demo</strong>: dashboard memakai data contoh
              lokal, jadi key belum diverifikasi ke API.
            </div>
          ) : null}
        </form>
      </div>
    </>
  )
}
