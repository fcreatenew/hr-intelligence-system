import { Navigate, Route, Routes } from 'react-router-dom'

import AppLayout from './layouts/AppLayout.jsx'
import LoginPage from './pages/LoginPage.jsx'
import OverviewPage from './pages/OverviewPage.jsx'
import WorkforcePage from './pages/WorkforcePage.jsx'
import TurnoverPage from './pages/TurnoverPage.jsx'
import AttendancePage from './pages/AttendancePage.jsx'
import PayrollPage from './pages/PayrollPage.jsx'
import ModelsPage from './pages/ModelsPage.jsx'
import { useAuth } from './hooks/useAuth.js'

export default function App() {
  const { isAuthenticated } = useAuth()

  if (!isAuthenticated) {
    return (
      <div className="shell">
        <main className="canvas">
          <LoginPage />
        </main>
      </div>
    )
  }

  return (
    <Routes>
      <Route element={<AppLayout />}>
        <Route index element={<OverviewPage />} />
        <Route path="workforce" element={<WorkforcePage />} />
        <Route path="turnover" element={<TurnoverPage />} />
        <Route path="attendance" element={<AttendancePage />} />
        <Route path="payroll" element={<PayrollPage />} />
        <Route path="models" element={<ModelsPage />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  )
}
