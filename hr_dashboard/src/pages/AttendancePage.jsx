import { hrApi } from '../services/api.js'
import { useAsyncData } from '../hooks/useAsyncData.js'
import { attendance } from '../services/fixtures.js'
import { fmtDecimal, fmtPercent } from '../utils/format.js'

import PageHead from '../components/PageHead.jsx'
import Section from '../components/Section.jsx'
import KpiCard from '../components/KpiCard.jsx'
import BarList from '../components/BarList.jsx'
import DataTable from '../components/DataTable.jsx'
import { AttendanceTrendChart } from '../components/charts.jsx'

export default function AttendancePage() {
  const { data: info } = useAsyncData(() => hrApi.modelInfo('absensi'), [])
  const metrics = info?.metrik ?? {}

  return (
    <>
      <PageHead
        kicker="Modul 03"
        title="Attendance"
        accent="analytics"
        note="Kehadiran, absen, keterlambatan, dan lembur. Prediksi absensi tinggi dari model absensi v1."
      />

      <div className="grid grid--kpi">
        <KpiCard label="Attendance Rate" value={fmtPercent(attendance.attendance_rate)} tone="sage" />
        <KpiCard label="Absence Rate" value={fmtPercent(attendance.absence_rate)} hint={`${attendance.hari_absen_per_karyawan} hari/karyawan`} tone="rust" delay={60} />
        <KpiCard label="Late Rate" value={fmtPercent(attendance.late_rate)} tone="amber" delay={120} />
        <KpiCard label="Rata-rata Lembur" value={`${fmtDecimal(attendance.rata_lembur_jam, 1)} jam`} hint="per bulan" tone="sky" delay={180} />
        <KpiCard label="ROC AUC Model" value={fmtDecimal(metrics.roc_auc, 4)} hint={`threshold ${info?.threshold?.toFixed(4) ?? '—'}`} tone="amber" delay={240} />
      </div>

      <Section index="01" title="Tren Kehadiran" meta="bulanan">
        <div className="card">
          <AttendanceTrendChart data={attendance.tren} />
        </div>
      </Section>

      <Section index="02" title="Kehadiran per Departemen">
        <div className="grid grid--split">
          <div className="card">
            <div className="card__label">Attendance Rate</div>
            <BarList
              items={attendance.per_departemen}
              labelKey="departemen"
              valueKey="attendance_rate"
              format={(v) => fmtPercent(v)}
            />
          </div>
          <div className="card card--flush">
            <DataTable
              rows={attendance.per_departemen.map((d) => ({ id: d.departemen, ...d }))}
              columns={[
                { key: 'departemen', label: 'Departemen' },
                { key: 'attendance_rate', label: 'Kehadiran', numeric: true, render: (r) => fmtPercent(r.attendance_rate) },
                { key: 'lembur_jam', label: 'Lembur (jam)', numeric: true, render: (r) => fmtDecimal(r.lembur_jam, 1) },
              ]}
            />
          </div>
        </div>
      </Section>
    </>
  )
}
