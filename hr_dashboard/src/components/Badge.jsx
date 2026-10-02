import { levelClass } from '../utils/format.js'

export default function Badge({ level, children }) {
  return (
    <span className={`badge ${levelClass(level)}`}>{children ?? level}</span>
  )
}
