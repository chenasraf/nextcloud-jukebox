export function formatTime(seconds: number): string {
  const m = Math.floor(seconds / 60)
  const s = Math.floor(seconds % 60)
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
}

export function formatDuration(seconds: number): string {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor(seconds / 60 - h * 60)
  const s = Math.floor(seconds % 60)
  let formatted = ''
  if (h > 0) {
    formatted += `${h}:${m.toString().padStart(2, '0')}:`
  } else {
    formatted += `${m}:`
  }
  formatted += s.toString().padStart(2, '0')
  return formatted
}

export const formatDate = (iso: string): string => {
  const date = new Date(iso)
  return date.toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
