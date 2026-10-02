import { Navigate, Route, Routes } from 'react-router-dom'

import AppLayout from './layouts/AppLayout.jsx'
import OverviewPage from './pages/OverviewPage.jsx'
import WorkforcePage from './pages/WorkforcePage.jsx'
import TurnoverPage from './pages/TurnoverPage.jsx'
import AttendancePage from './pages/AttendancePage.jsx'
import PayrollPage from './pages/PayrollPage.jsx'
import ModelsPage from './pages/ModelsPage.jsx'

export default function App() {
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
