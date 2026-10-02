import { hrApi } from '../services/api.js'
import { useAsyncData } from '../hooks/useAsyncData.js'
import { attendance, turnover, workforce } from '../services/fixtures.js'
import { fmtDecimal, fmtInt, fmtPercent, fmtRupiahCompact } from '../utils/format.js'

import PageHead from '../components/PageHead.jsx'
import Section from '../components/Section.jsx'
import KpiCard from '../components/KpiCard.jsx'
import Badge from '../components/Badge.jsx'
import DataTable from '../components/DataTable.jsx'

const METRIC_LABELS = {
  roc_auc: 'ROC AUC',
  pr_auc: 'PR AUC',
  accuracy: 'Accuracy',
  precision: 'Precision',
  recall: 'Recall',
  f1: 'F1',
  f1_macro: 'Macro-F1',
  f1_weighted: 'Weighted-F1',
  mae_ordinal: 'MAE Ordinal',
  c_index: 'C-index',
  isolation_forest_roc_auc: 'ROC AUC',
  isolation_forest_pr_auc: 'PR AUC',
}

export default function ModelsPage() {
  const { data: health } = useAsyncData(() => hrApi.health(), [])
  const { data: models } = useAsyncData(() => hrApi.models(), [])

  const loaded = health?.models_loaded ?? 0
  const total = health?.models_total ?? 0

  return (
    <>
      <PageHead
        kicker="Modul 05"
        title="HR KPI &"
        accent="Model"
        note="Indikator kunci HR dan status lima model ML yang melayani prediksi."
      />

      <Section index="00" title="HR KPI" meta="indikator kunci">
        <div className="grid grid--kpi">
          <KpiCard label="Employee Count" value={fmtInt(workforce.total_karyawan)} tone="amber" />
          <KpiCard label="Turnover Rate" value={fmtPercent(turnover.turnover_rate)} tone="rust" delay={60} />
          <KpiCard label="Attendance Rate" value={fmtPercent(attendance.attendance_rate)} tone="sage" delay={120} />
          <KpiCard label="Average Salary" value={fmtRupiahCompact(16175423)} tone="sky" delay={180} />
          <KpiCard label="Overtime Hours" value={fmtDecimal(attendance.rata_lembur_jam, 1)} hint="rata-rata jam/bulan" tone="amber" delay={240} />
          <KpiCard label="Employee Risk" value={fmtInt(turnover.risk_distribution[2].jumlah)} hint="kategori risiko tinggi" tone="rust" delay={300} />
        </div>
      </Section>

      <Section index="01" title="Status Model ML" meta={`${loaded}/${total} termuat`}>
        <div className="card card--flush">
          <DataTable
            rows={(models ?? []).map((m) => ({ id: m.use_case, ...m }))}
            empty="Tidak ada model terdaftar."
            columns={[
              { key: 'use_case', label: 'Use Case' },
              { key: 'label', label: 'Label' },
              { key: 'model', label: 'Algoritma' },
              { key: 'versi_model', label: 'Versi' },
              {
                key: 'threshold',
                label: 'Threshold',
                numeric: true,
                render: (r) => (r.threshold != null ? r.threshold.toFixed(4) : '—'),
              },
              {
                key: 'loaded',
                label: 'Status',
                render: (r) => (
                  <Badge level={r.loaded ? 'rendah' : 'tinggi'}>
                    {r.loaded ? 'Aktif' : 'Gagal'}
                  </Badge>
                ),
              },
            ]}
          />
        </div>
      </Section>

      <Section index="02" title="Metrik Evaluasi per Model" meta="holdout test">
        <div className="grid grid--halves">
          {(models ?? []).map((model) => (
            <ModelCard key={model.use_case} model={model} />
          ))}
        </div>
      </Section>
    </>
  )
}

function ModelCard({ model }) {
  const { data } = useAsyncData(() => hrApi.modelInfo(model.use_case), [model.use_case])
  const metrics = data?.metrik ?? {}

  const entries = Object.entries(metrics).filter(
    ([key, value]) =>
      METRIC_LABELS[key] && typeof value === 'number',
  )

  return (
    <article className="card">
      <span className="card__accent" />
      <div className="card__label">{model.label}</div>
      <div className="card__value" style={{ fontSize: '1.4rem' }}>
        {model.model}
      </div>
      <div className="card__hint">
        versi {model.versi_model}
        {model.threshold != null ? ` · threshold ${model.threshold.toFixed(4)}` : ''}
      </div>

      {entries.length ? (
        <div style={{ marginTop: '1rem' }}>
          {entries.map(([key, value]) => (
            <div
              key={key}
              className="row"
              style={{ justifyContent: 'space-between', padding: '0.25rem 0' }}
            >
              <span className="muted" style={{ fontSize: '0.8rem' }}>
                {METRIC_LABELS[key]}
              </span>
              <span className="mono" style={{ fontSize: '0.8rem' }}>
                {fmtDecimal(value, 4)}
              </span>
            </div>
          ))}
        </div>
      ) : (
        <div className="card__hint" style={{ marginTop: '1rem' }}>
          metrik tidak tersedia
        </div>
      )}
    </article>
  )
}
