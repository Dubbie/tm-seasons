import type { ApiPointEvent } from '@/lib/api'
import { displayTrackmaniaMapName } from '@/lib/trackmaniaText'

function mapLabel(event: ApiPointEvent): string {
  const name = event.map ? displayTrackmaniaMapName(event.map.name, event.map.uid) : ''
  if (name) return `${name}`
  if (event.map?.uid) return `map ${event.map.uid}`
  return 'a map'
}

export function describeEvent(event: ApiPointEvent): string {
  if (event.type === 'first_finish') {
    return `finished on ${mapLabel(event)}`
  }

  if (event.type.startsWith('medal_')) {
    const medal = event.type.replace('medal_', '')
    const medalLabel = medal.charAt(0).toUpperCase() + medal.slice(1)
    return `earned ${medalLabel} medal on ${mapLabel(event)}`
  }

  if (event.type.startsWith('entered_top_')) {
    const threshold = event.type.replace('entered_top_', '')
    if (threshold === '1') return 'took 1st place in club'
    return `entered club top ${threshold}`
  }

  return event.description ?? event.type
}
