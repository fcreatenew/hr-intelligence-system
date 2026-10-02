import { payrollApi } from '../services/api.js'
import { useAsyncData } from '../hooks/useAsyncData.js'
import { fixtures } from '../services/fixtures.js'
import { fmtInt, fmtRupiah, fmtRupiahCompact } from '../utils/format.js'

import PageHead from '../components/PageHead.jsx'
import Section from '../components/Section.jsx'
import KpiCard from '../components/KpiCard.jsx'
import BarList from '../components/BarList.jsx'
import Badge from '../components/Badge.jsx'
import DataTable from '../components/DataTable.jsx'
import { PayrollTrendChart } from '../components/charts.jsx'

export default function PayrollPage() {
  const { data: summary } = useAsyncData(() => payrollApi.summary(), [])
  const { data: departments } = useAsyncData(() => payrollApi.departments(), [])
  const { data: periods } = useAsyncData(() => payrollApi.periods(), [])
  const { data: anomalies } = useAsyncData(() => payrollApi.anomalies(), [])
  const { data: forecast } = useAsyncData(() => payrollApi.forecast(), [])

  const deptRows = departments ?? fixtures.payroll.departments
  const periodRows = periods ?? fixtures.payroll.periods
  const forecastData = forecast?.periode
    ? forecast.periode.map((p, i) => ({ periode: p, nilai: forecast.nilai[i] }))
    : []

  return (
    <>
      <PageHead
        kicker="Modul 04"
        title="Payroll"
        accent="analytics"
        note="Analitik biaya payroll, deteksi anomali, dan proyeksi. Sumber: Payroll Intelligence API."
      />

      <div className="grid grid--kpi">
        <KpiCard label="Total Payroll" value={fmtRupiahCompact(summary?.total_payroll)} hint={`${summary?.periode_awal ?? '—'} – ${summary?.periode_akhir ?? '—'}`} tone="amber" />
        <KpiCard label="Rata-rata Gaji" value={fmtRupiahCompact(summary?.rata_rata_gaji)} hint={`median ${fmtRupiahCompact(summary?.median_gaji)}`} tone="sky" delay={60} />
        <KpiCard label="Biaya Lembur" value={fmtRupiahCompact(summary?.total_biaya_lembur)} tone="rust" delay={120} />
        <KpiCard label="Karyawan" value={fmtInt(summary?.jumlah_karyawan)} hint="tercakup dalam data" tone="sage" delay={180} />
        <KpiCard label="Anomali" value={fmtInt(anomalies?.jumlah_anomali)} hint={`ambang ${anomalies?.threshold ?? '—'}`} tone="rust" delay={240} />
      </div>

      <Section index="01" title="Tren & Proyeksi Payroll" meta={forecast?.model ? `model: ${forecast.model}` : 'historis + proyeksi'}>
        <div className="card">
          <PayrollTrendChart history={periodRows} forecast={forecastData} />
        </div>
      </Section>

      <Section index="02" title="Payroll per Departemen" meta={`${deptRows.length} departemen`}>
        <div className="grid grid--split">
          <div className="card">
            <div className="card__label">Total Payroll</div>
            <BarList
              items={deptRows}
              labelKey="departemen"
              valueKey="total_payroll"
              format={fmtRupiahCompact}
            />
          </div>
          <div className="card card--flush">
            <DataTable
              rows={deptRows.map((d) => ({ id: d.departemen, ...d }))}
              columns={[
                { key: 'departemen', label: 'Departemen' },
                { key: 'jumlah_karyawan', label: 'Karyawan', numeric: true, render: (r) => fmtInt(r.jumlah_karyawan) },
                { key: 'total_payroll', label: 'Total', numeric: true, render: (r) => fmtRupiahCompact(r.total_payroll) },
                { key: 'rata_rata_gaji', label: 'Rata-rata', numeric: true, render: (r) => fmtRupiahCompact(r.rata_rata_gaji) },
              ]}
            />
          </div>
        </div>
      </Section>

      <Section index="03" title="Anomali Payroll" meta="untuk verifikasi, bukan tuduhan">
        <div className="card card--flush">
          <DataTable
            rows={(anomalies?.hasil ?? []).map((a, i) => ({ id: `${a.employee_id}-${i}`, ...a }))}
            empty="Tidak ada anomali pada ambang ini."
            columns={[
              { key: 'employee_id', label: 'Karyawan' },
              { key: 'periode', label: 'Periode' },
              { key: 'skor_anomali', label: 'Skor', numeric: true, render: (r) => Number(r.skor_anomali).toFixed(4) },
              { key: 'level', label: 'Level', render: (r) => <Badge level={r.level} /> },
              { key: 'alasan', label: 'Alasan' },
            ]}
          />
        </div>
      </Section>

      <Section index="04" title="Rincian per Periode">
        <div className="card card--flush">
          <DataTable
            rows={periodRows.map((p) => ({ id: p.periode, ...p }))}
            columns={[
              { key: 'periode', label: 'Periode' },
              { key: 'jumlah_karyawan', label: 'Karyawan', numeric: true, render: (r) => fmtInt(r.jumlah_karyawan) },
              { key: 'total_payroll', label: 'Total Payroll', numeric: true, render: (r) => fmtRupiah(r.total_payroll) },
              { key: 'rata_rata_gaji', label: 'Rata-rata', numeric: true, render: (r) => fmtRupiah(r.rata_rata_gaji) },
              { key: 'total_biaya_lembur', label: 'Biaya Lembur', numeric: true, render: (r) => fmtRupiah(r.total_biaya_lembur) },
            ]}
          />
        </div>
      </Section>
    </>
  )
}
