export default function PageHead({ kicker, title, accent, note }) {
  return (
    <header className="page-head">
      <div>
        <div className="page-head__kicker">{kicker}</div>
        <h1 className="page-head__title">
          {title} {accent ? <strong>{accent}</strong> : null}
        </h1>
      </div>
      {note ? <p className="page-head__note">{note}</p> : null}
    </header>
  )
}
