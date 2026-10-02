export default function Section({ index, title, meta, children }) {
  return (
    <section className="section">
      <div className="section__head">
        <span className="section__index">{index}</span>
        <h2 className="section__title">{title}</h2>
        {meta ? <span className="section__meta">{meta}</span> : null}
      </div>
      {children}
    </section>
  )
}
