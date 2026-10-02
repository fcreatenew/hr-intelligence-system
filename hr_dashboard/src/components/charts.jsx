import {
  Area,
  AreaChart,
  CartesianGrid,
  Cell,
  Legend,
  Line,
  LineChart,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'

import { chartColors, fmtCompact, fmtRupiahCompact } from '../utils/format.js'

const axisStyle = {
  fontSize: 11,
  fontFamily: "'IBM Plex Mono', monospace",
  fill: chartColors.dim,
}

function TooltipBox({ active, payload, label, formatter }) {
  if (!active || !payload?.length) return null

  return (
    <div
      style={{
        background: '#1c1814',
        border: '1px solid rgba(244,237,225,0.24)',
        padding: '0.5rem 0.7rem',
        fontSize: '0.75rem',
        fontFamily: "'IBM Plex Mono', monospace",
      }}
    >
      <div style={{ color: chartColors.dim, marginBottom: 4 }}>{label}</div>
      {payload.map((entry) => (
        <div key={entry.name} style={{ color: entry.color }}>
          {entry.name}: {formatter ? formatter(entry.value) : fmtCompact(entry.value)}
        </div>
      ))}
    </div>
  )
}

/** Tren payroll: nilai historis + proyeksi (garis putus-putus). */
export function PayrollTrendChart({ history = [], forecast = [] }) {
  const data = [
    ...history.map((item) => ({
      periode: item.periode,
      historis: item.total_payroll,
    })),
    ...forecast.map((item) => ({
      periode: item.periode,
      proyeksi: item.nilai,
    })),
  ]

  return (
    <ResponsiveContainer width="100%" height={260}>
      <AreaChart data={data} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
        <defs>
          <linearGradient id="payrollFill" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor={chartColors.amber} stopOpacity={0.35} />
            <stop offset="100%" stopColor={chartColors.amber} stopOpacity={0.02} />
          </linearGradient>
        </defs>
        <CartesianGrid stroke={chartColors.grid} vertical={false} />
        <XAxis dataKey="periode" tick={axisStyle} axisLine={false} tickLine={false} />
        <YAxis
          tick={axisStyle}
          axisLine={false}
          tickLine={false}
          tickFormatter={fmtCompact}
          width={54}
        />
        <Tooltip content={<TooltipBox formatter={fmtRupiahCompact} />} />
        <Area
          type="monotone"
          dataKey="historis"
          name="Historis"
          stroke={chartColors.amber}
          fill="url(#payrollFill)"
          strokeWidth={2}
          dot={{ r: 3 }}
        />
        <Line
          type="monotone"
          dataKey="proyeksi"
          name="Proyeksi"
          stroke={chartColors.sky}
          strokeWidth={2}
          strokeDasharray="5 4"
          dot={{ r: 3 }}
        />
      </AreaChart>
    </ResponsiveContainer>
  )
}

/** Tren kehadiran bulanan. */
export function AttendanceTrendChart({ data = [] }) {
  return (
    <ResponsiveContainer width="100%" height={240}>
      <LineChart data={data} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
        <CartesianGrid stroke={chartColors.grid} vertical={false} />
        <XAxis dataKey="periode" tick={axisStyle} axisLine={false} tickLine={false} />
        <YAxis
          tick={axisStyle}
          axisLine={false}
          tickLine={false}
          domain={['dataMin - 0.01', 'dataMax + 0.01']}
          tickFormatter={(v) => `${(v * 100).toFixed(1)}%`}
          width={54}
        />
        <Tooltip content={<TooltipBox formatter={(v) => `${(v * 100).toFixed(1)}%`} />} />
        <Line
          type="monotone"
          dataKey="kehadiran"
          name="Kehadiran"
          stroke={chartColors.sage}
          strokeWidth={2}
          dot={{ r: 3 }}
        />
        <Line
          type="monotone"
          dataKey="absen"
          name="Absen"
          stroke={chartColors.rust}
          strokeWidth={2}
          strokeDasharray="4 3"
          dot={{ r: 3 }}
        />
      </LineChart>
    </ResponsiveContainer>
  )
}

const RISK_COLORS = {
  Rendah: chartColors.sage,
  Sedang: chartColors.amber,
  Tinggi: chartColors.rust,
}

/** Distribusi tingkat risiko turnover. */
export function RiskDonut({ data = [] }) {
  return (
    <ResponsiveContainer width="100%" height={240}>
      <PieChart>
        <Pie
          data={data}
          dataKey="jumlah"
          nameKey="level"
          innerRadius={58}
          outerRadius={92}
          paddingAngle={3}
          stroke="none"
        >
          {data.map((entry) => (
            <Cell
              key={entry.level}
              fill={RISK_COLORS[entry.level] ?? chartColors.sky}
            />
          ))}
        </Pie>
        <Legend
          formatter={(value) => (
            <span style={{ fontSize: 12, color: chartColors.dim }}>{value}</span>
          )}
        />
        <Tooltip content={<TooltipBox />} />
      </PieChart>
    </ResponsiveContainer>
  )
}
