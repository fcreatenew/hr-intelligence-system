import { workforce } from '../services/fixtures.js'
import { fmtDecimal, fmtInt, fmtPercent } from '../utils/format.js'

import PageHead from '../components/PageHead.jsx'
import Section from '../components/Section.jsx'
import KpiCard from '../components/KpiCard.jsx'
import BarList from '../components/BarList.jsx'
import DataTable from '../components/DataTable.jsx'

export default function WorkforcePage() {
  const statusRows = workforce.distribusi_status.map((item) => ({
    id: item.status,
    ...item,
    porsi: item.jumlah / workforce.total_karyawan,
  }))

  return (
    <>
      <PageHead
        kicker="Modul 01"
        title="Workforce"
        accent="analytics"
        note="Komposisi, pertumbuhan, dan sebaran karyawan. Sumber: HR Application."
      />

      <div className="grid grid--kpi">
        <KpiCard label="Total Karyawan" value={fmtInt(workforce.total_karyawan)} tone="amber" />
        <KpiCard label="Aktif" value={fmtInt(workforce.aktif)} hint={fmtPercent(workforce.aktif / workforce.total_karyawan)} tone="sage" delay={60} />
        <KpiCard label="Kontrak" value={fmtInt(workforce.kontrak)} hint={fmtPercent(workforce.kontrak / workforce.total_karyawan)} tone="sky" delay={120} />
        <KpiCard label="Rata-rata Usia" value={`${fmtDecimal(workforce.rata_usia, 1)} th`} tone="amber" delay={180} />
        <KpiCard label="Rata-rata Masa Kerja" value={`${fmtDecimal(workforce.rata_masa_kerja_bulan / 12, 1)} th`} hint={`${fmtDecimal(workforce.rata_masa_kerja_bulan, 1)} bulan`} tone="sky" delay={240} />
      </div>

      <Section index="01" title="Sebaran per Departemen" meta={`${workforce.per_departemen.length} departemen`}>
        <div className="grid grid--halves">
          <div className="card">
            <div className="card__label">Jumlah Karyawan</div>
            <BarList items={workforce.per_departemen} labelKey="departemen" valueKey="jumlah" />
          </div>
          <div className="card">
            <div className="card__label">Distribusi Usia</div>
            <BarList items={workforce.distribusi_usia} labelKey="rentang" valueKey="jumlah" />
          </div>
        </div>
      </Section>

      <Section index="02" title="Komposisi Status Kerja">
        <div className="card card--flush">
          <DataTable
            rows={statusRows}
            columns={[
              { key: 'status', label: 'Status' },
              { key: 'jumlah', label: 'Jumlah', numeric: true, render: (r) => fmtInt(r.jumlah) },
              { key: 'porsi', label: 'Porsi', numeric: true, render: (r) => fmtPercent(r.porsi) },
            ]}
          />
        </div>
      </Section>

      <Section index="03" title="Workforce Composition" meta="karyawan per departemen & turnover rate">
        <div className="card card--flush">
          <DataTable
            rows={workforce.per_departemen.map((d) => ({ id: d.departemen, ...d }))}
            columns={[
              { key: 'departemen', label: 'Departemen' },
              { key: 'jumlah', label: 'Karyawan', numeric: true, render: (r) => fmtInt(r.jumlah) },
              { key: 'turnover_rate', label: 'Turnover Rate', numeric: true, render: (r) => fmtPercent(r.turnover_rate) },
            ]}
          />
        </div>
      </Section>
    </>
  )
}
