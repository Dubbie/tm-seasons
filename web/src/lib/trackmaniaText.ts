export function stripTrackmaniaFormatting(input: string): string {
  return input
    .replace(/\$[0-9a-fA-F]{1,3}/g, '')
    .replace(/\$[gGiIoOsSwWzZnNtTmM<>]/g, '')
    .replace(/\$\$/g, '$')
}

export function displayTrackmaniaMapName(name: string | null | undefined, uid: string): string {
  const cleaned = name ? stripTrackmaniaFormatting(name).trim() : ''
  return cleaned || uid
}
