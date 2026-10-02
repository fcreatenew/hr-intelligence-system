import { NavLink, Outlet } from 'react-router-dom'

import { dataMode, hrApi } from '../services/api.js'
import { useAsyncData } from '../hooks/useAsyncData.js'

const NAV = [
  { to: '/', label: 'Ringkasan', num: '00' },
  { to: '/workforce', label: 'Workforce', num: '01' },
  { to: '/turnover', label: 'Turnover', num: '02' },
  { to: '/attendance', label: 'Attendance', num: '03' },
  { to: '/payroll', label: 'Payroll', num: '04' },
  { to: '/models', label: 'HR KPI & Model', num: '05' },
]

function ModelStatus() {
  const { data, loading } = useAsyncData(() => hrApi.health(), [])

  if (loading) {
    return (
      <span>
        <span className="status-dot" />
        memuat status…
      </span>
    )
  }

  const loaded = data?.models_loaded ?? 0
  const total = data?.models_total ?? 0
  const live = data?.status === 'ok'

  return (
    <span>
      <span className={`status-dot ${live ? 'is-live' : 'is-down'}`} />
      {loaded}/{total} model aktif
    </span>
  )
}

export default function AppLayout() {
  return (
    <div className="shell">
      <aside className="masthead">
        <div className="brand">
          <span className="brand__mark">
            HR <em>Intelligence</em>
          </span>
          <span className="brand__rule" />
          <span className="brand__sub">Operations Console</span>
        </div>

        <nav className="nav">
          {NAV.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.to === '/'}
              className={({ isActive }) =>
                `nav__link${isActive ? ' is-active' : ''}`
              }
            >
              <span className="nav__num">{item.num}</span>
              {item.label}
            </NavLink>
          ))}
        </nav>

        <div className="masthead__foot">
          <div>
            <ModelStatus />
          </div>
          <div style={{ marginTop: '0.5rem' }}>
            mode data: <strong>{dataMode}</strong>
          </div>
          <div style={{ marginTop: '0.5rem' }}>
            sumber: hr_intelligence_api · payroll_intelligence
          </div>
        </div>
      </aside>

      <main className="canvas">
        <Outlet />
      </main>
    </div>
  )
}
