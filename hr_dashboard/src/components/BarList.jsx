import { fmtInt } from '../utils/format.js'

/**
 * Daftar bar horizontal bergaya editorial. Dipakai untuk peringkat
 * departemen/kategori tanpa perlu library grafik.
 */
export default function BarList({ items, valueKey, labelKey, format = fmtInt }) {
  const max = Math.max(...items.map((item) => Number(item[valueKey]) || 0), 1)

  return (
    <div className="stack">
      {items.map((item) => {
        const value = Number(item[valueKey]) || 0
        const pct = (value / max) * 100

        return (
          <div key={item[labelKey]}>
            <div className="row" style={{ justifyContent: 'space-between' }}>
              <span style={{ fontSize: '0.86rem' }}>{item[labelKey]}</span>
              <span className="mono" style={{ fontSize: '0.8rem' }}>
                {format(value)}
              </span>
            </div>
            <div className="bar" style={{ marginTop: '0.35rem' }}>
              <div className="bar__fill" style={{ width: `${pct}%` }} />
            </div>
          </div>
        )
      })}
    </div>
  )
}
