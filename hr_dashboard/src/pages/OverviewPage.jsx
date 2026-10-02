import { hrApi, payrollApi } from '../services/api.js'
import { useAsyncData } from '../hooks/useAsyncData.js'
import { attendance, turnover, workforce } from '../services/fixtures.js'
import { fmtInt, fmtPercent, fmtRupiahCompact } from '../utils/format.js'

import PageHead from '../components/PageHead.jsx'
import Section from '../components/Section.jsx'
import KpiCard from '../components/KpiCard.jsx'
import BarList from '../components/BarList.jsx'
import Badge from '../components/Badge.jsx'
import DataTable from '../components/DataTable.jsx'
import { RiskDonut } from '../components/charts.jsx'

export default function OverviewPage() {
  const { data: health } = useAsyncData(() => hrApi.health(), [])
  const { data: payroll } = useAsyncData(() => payrollApi.summary(), [])
  const { data: anomalies } = useAsyncData(() => payrollApi.anomalies(), [])

  const loaded = health?.models_loaded ?? 0
  const total = health?.models_total ?? 5

  const anomalyRows = (anomalies?.hasil ?? []).slice(0, 6)

  return (
    <>
      <PageHead
        kicker="Ringkasan Operasional"
        title="Kondisi"
        accent="workforce"
        note="Angka model berasal dari lima model ML aktif. Data workforce & absensi bersumber dari HR Application."
      />

      <div className="grid grid--kpi">
        <KpiCard
          label="Total Karyawan"
          value={fmtInt(workforce.total_karyawan)}
          hint={`${fmtPercent(workforce.pertumbuhan_tahunan)} pertumbuhan tahunan`}
          tone="amber"
          delay={0}
        />
        <KpiCard
          label="Turnover Rate"
          value={fmtPercent(turnover.turnover_rate)}
          hint="Rasio positif pada data training"
          tone="rust"
          delay={60}
        />
        <KpiCard
          label="Attendance Rate"
          value={fmtPercent(attendance.attendance_rate)}
          hint={`rata-rata lembur ${attendance.rata_lembur_jam} jam/bulan`}
          tone="sage"
          delay={120}
        />
        <KpiCard
          label="Total Payroll"
          value={fmtRupiahCompact(payroll?.total_payroll)}
          hint={`${payroll?.periode_awal ?? '—'} – ${payroll?.periode_akhir ?? '—'}`}
          tone="sky"
          delay={180}
        />
        <KpiCard
          label="Model Aktif"
          value={`${loaded}/${total}`}
          hint={health?.status === 'ok' ? 'semua model termuat' : 'sebagian model gagal dimuat'}
          tone={health?.status === 'ok' ? 'sage' : 'rust'}
          delay={240}
        />
        <KpiCard
          label="Anomali Payroll"
          value={fmtInt(anomalies?.jumlah_anomali)}
          hint={`ambang skor ${anomalies?.threshold ?? '—'}`}
          tone="rust"
          delay={300}
        />
      </div>

      <Section index="01" title="Komposisi Workforce" meta={`${workforce.per_departemen.length} departemen`}>
        <div className="grid grid--split">
          <div className="card">
            <div className="card__label">Karyawan per Departemen</div>
            <BarList
              items={workforce.per_departemen}
              labelKey="departemen"
              valueKey="jumlah"
            />
          </div>
          <div className="card">
            <div className="card__label">Distribusi Tingkat Risiko Turnover</div>
            <RiskDonut data={turnover.risk_distribution} />
          </div>
        </div>
      </Section>

      <Section index="02" title="Sinyal Anomali Payroll Terbaru" meta="untuk verifikasi tim payroll">
        <div className="card card--flush">
          <DataTable
            rows={anomalyRows}
            empty="Tidak ada anomali terdeteksi."
            columns={[
              { key: 'employee_id', label: 'Karyawan' },
              { key: 'periode', label: 'Periode' },
              {
                key: 'skor_anomali',
                label: 'Skor',
                numeric: true,
                render: (row) => row.skor_anomali.toFixed(4),
              },
              {
                key: 'level',
                label: 'Level',
                render: (row) => <Badge level={row.level} />,
              },
              { key: 'alasan', label: 'Alasan' },
            ]}
          />
        </div>
      </Section>
    </>
  )
}
