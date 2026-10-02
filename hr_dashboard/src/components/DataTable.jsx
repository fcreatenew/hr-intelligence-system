export default function DataTable({ columns, rows, empty = 'Tidak ada data.' }) {
  if (!rows?.length) {
    return <div className="notice">{empty}</div>
  }

  return (
    <table className="table">
      <thead>
        <tr>
          {columns.map((col) => (
            <th key={col.key} className={col.numeric ? 'num' : undefined}>
              {col.label}
            </th>
          ))}
        </tr>
      </thead>
      <tbody>
        {rows.map((row, index) => (
          <tr key={row.id ?? index}>
            {columns.map((col) => (
              <td key={col.key} className={col.numeric ? 'num' : undefined}>
                {col.render ? col.render(row) : row[col.key]}
              </td>
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  )
}
