import { hrApi } from '../services/api.js'
import { useAsyncData } from '../hooks/useAsyncData.js'
import { turnover } from '../services/fixtures.js'
import { fmtDecimal, fmtInt, fmtPercent } from '../utils/format.js'

import PageHead from '../components/PageHead.jsx'
import Section from '../components/Section.jsx'
import KpiCard from '../components/KpiCard.jsx'
import BarList from '../components/BarList.jsx'
import DataTable from '../components/DataTable.jsx'
import { RiskDonut } from '../components/charts.jsx'

export default function TurnoverPage() {
  const { data: info } = useAsyncData(() => hrApi.modelInfo('turnover'), [])
  const metrics = info?.metrik ?? {}

  return (
    <>
      <PageHead
        kicker="Modul 02"
        title="Turnover"
        accent="analytics"
        note="Analisis attrition dan sebaran risiko. Prediksi dari model turnover v2 (Soft Voting ensemble)."
      />

      <div className="grid grid--kpi">
        <KpiCard label="Turnover Rate" value={fmtPercent(turnover.turnover_rate)} hint="rasio kelas positif" tone="rust" />
        <KpiCard label="Risiko Tinggi" value={fmtInt(turnover.risk_distribution[2].jumlah)} hint={fmtPercent(turnover.risk_distribution[2].jumlah / 1200)} tone="rust" delay={60} />
        <KpiCard label="ROC AUC" value={fmtDecimal(metrics.roc_auc, 4)} hint="holdout test" tone="amber" delay={120} />
        <KpiCard label="Recall" value={fmtDecimal(metrics.recall, 4)} hint={`threshold ${info?.threshold ?? '—'}`} tone="sky" delay={180} />
        <KpiCard label="PR AUC" value={fmtDecimal(metrics.pr_auc, 4)} hint="area presisi-recall" tone="sky" delay={240} />
      </div>

      <Section index="01" title="Sebaran Tingkat Risiko" meta="klasifikasi model">
        <div className="grid grid--split">
          <div className="card">
            <div className="card__label">Turnover Rate per Departemen</div>
            <BarList
              items={turnover.per_departemen}
              labelKey="departemen"
              valueKey="turnover_rate"
              format={(v) => fmtPercent(v)}
            />
          </div>
          <div className="card">
            <div className="card__label">Distribusi Risiko</div>
            <RiskDonut data={turnover.risk_distribution} />
          </div>
        </div>
      </Section>

      <Section index="02" title="Turnover menurut Masa Kerja" meta="kategori tenure">
        <div className="grid grid--halves">
          <div className="card">
            <div className="card__label">Turnover Rate per Masa Kerja</div>
            <BarList
              items={turnover.per_masa_kerja}
              labelKey="rentang"
              valueKey="turnover_rate"
              format={(v) => fmtPercent(v)}
            />
          </div>
          <div className="card card--flush">
            <DataTable
              rows={turnover.per_masa_kerja.map((m) => ({ id: m.rentang, ...m }))}
              columns={[
                { key: 'rentang', label: 'Masa Kerja' },
                { key: 'turnover_rate', label: 'Turnover Rate', numeric: true, render: (r) => fmtPercent(r.turnover_rate) },
              ]}
            />
          </div>
        </div>
      </Section>
    </>
  )
}
