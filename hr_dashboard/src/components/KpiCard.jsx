export default function KpiCard({
  label,
  value,
  hint,
  tone = 'amber',
  delay = 0,
}) {
  return (
    <article
      className={`card card--${tone}`}
      style={{ animationDelay: `${delay}ms` }}
    >
      <span className="card__accent" />
      <div className="card__label">{label}</div>
      <div className="card__value">{value}</div>
      {hint ? <div className="card__hint">{hint}</div> : null}
    </article>
  )
}
