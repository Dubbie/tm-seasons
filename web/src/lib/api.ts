const API_BASE = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/$/, '') ?? 'http://localhost:8000'

export type ApiUser = {
  id: number
  name: string
  email: string | null
  discord_id: string | null
  discord_username: string | null
  discord_global_name: string | null
  discord_avatar: string | null
  is_admin: boolean
  last_login_at: string | null
}

export type ApiMap = {
  id: number
  uid: string
  nadeo_map_id: string | null
  name: string | null
  author_account_id: string | null
  author_name: string | null
  author_time: number | null
  gold_time: number | null
  silver_time: number | null
  bronze_time: number | null
  map_type: string | null
  map_style: string | null
  thumbnail_url: string | null
  collection_name: string | null
  uploaded_at: string | null
  source_updated_at: string | null
  created_at: string | null
  updated_at: string | null
  season_pivot?: {
    id: number
    order_index: number
    is_active: boolean
  }
}

export type ApiSeason = {
  id: number
  name: string
  slug: string
  description: string | null
  starts_at: string | null
  ends_at: string | null
  is_active: boolean
  status: string
  created_by_user_id: number | null
  maps?: ApiMap[]
  created_at: string | null
  updated_at: string | null
}

type ApiCollection<T> = {
  data: T[]
}

type ApiResource<T> = {
  data: T
}

export async function fetchCsrfCookie(): Promise<void> {
  await fetch(`${API_BASE}/sanctum/csrf-cookie`, {
    method: 'GET',
    credentials: 'include',
  })
}

export async function fetchMe(): Promise<ApiUser | null> {
  const response = await fetch(`${API_BASE}/api/me`, {
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  })

  if (response.status === 401) {
    return null
  }

  if (!response.ok) {
    throw new Error(`Failed to fetch /api/me (${response.status})`)
  }

  return (await response.json()) as ApiUser
}

export async function logout(): Promise<void> {
  await fetchCsrfCookie()
  const csrfToken = readCookie('XSRF-TOKEN')

  const response = await fetch(`${API_BASE}/auth/logout`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(csrfToken ? { 'X-XSRF-TOKEN': decodeURIComponent(csrfToken) } : {}),
    },
    body: JSON.stringify({}),
  })

  if (!response.ok) {
    throw new Error(`Failed to logout (${response.status})`)
  }
}

export function discordRedirectUrl(): string {
  return `${API_BASE}/auth/discord/redirect`
}

export async function adminMaps(): Promise<ApiMap[]> {
  const payload = await request<ApiCollection<ApiMap>>('/api/admin/maps')
  return payload.data
}

export async function importAdminMap(uid: string): Promise<ApiMap> {
  const payload = await request<ApiResource<ApiMap>>('/api/admin/maps/import', {
    method: 'POST',
    body: JSON.stringify({ uid }),
  })
  return payload.data
}

export async function deleteAdminMap(id: number): Promise<void> {
  await request<void>(`/api/admin/maps/${id}`, { method: 'DELETE' })
}

export async function adminSeasons(): Promise<ApiSeason[]> {
  const payload = await request<ApiCollection<ApiSeason>>('/api/admin/seasons')
  return payload.data
}

export async function adminSeason(id: number): Promise<ApiSeason> {
  const payload = await request<ApiResource<ApiSeason>>(`/api/admin/seasons/${id}`)
  return payload.data
}

export async function createAdminSeason(payload: Partial<ApiSeason>): Promise<ApiSeason> {
  const response = await request<ApiResource<ApiSeason>>('/api/admin/seasons', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
  return response.data
}

export async function updateAdminSeason(id: number, payload: Partial<ApiSeason>): Promise<ApiSeason> {
  const response = await request<ApiResource<ApiSeason>>(`/api/admin/seasons/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
  return response.data
}

export async function deleteAdminSeason(id: number): Promise<void> {
  await request<void>(`/api/admin/seasons/${id}`, { method: 'DELETE' })
}

export async function attachMapToSeason(seasonId: number, mapId: number, orderIndex = 0): Promise<ApiSeason> {
  const payload = await request<ApiResource<ApiSeason>>(`/api/admin/seasons/${seasonId}/maps`, {
    method: 'POST',
    body: JSON.stringify({ map_id: mapId, order_index: orderIndex }),
  })
  return payload.data
}

export async function updateSeasonMap(seasonId: number, mapId: number, payload: { order_index?: number; is_active?: boolean }): Promise<ApiSeason> {
  const response = await request<ApiResource<ApiSeason>>(`/api/admin/seasons/${seasonId}/maps/${mapId}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
  return response.data
}

export async function removeSeasonMap(seasonId: number, mapId: number): Promise<void> {
  await request<void>(`/api/admin/seasons/${seasonId}/maps/${mapId}`, {
    method: 'DELETE',
  })
}

export async function publicSeasons(): Promise<ApiSeason[]> {
  const payload = await request<ApiCollection<ApiSeason>>('/api/seasons', {}, false)
  return payload.data
}

export async function publicSeason(slug: string): Promise<ApiSeason> {
  const payload = await request<ApiResource<ApiSeason>>(`/api/seasons/${slug}`, {}, false)
  return payload.data
}

export async function publicMap(uid: string): Promise<ApiMap> {
  const payload = await request<ApiResource<ApiMap>>(`/api/maps/${uid}`, {}, false)
  return payload.data
}

async function request<T>(
  path: string,
  init: RequestInit = {},
  includeCsrf = true,
): Promise<T> {
  if (includeCsrf && init.method && init.method !== 'GET') {
    await fetchCsrfCookie()
  }

  const csrfToken = readCookie('XSRF-TOKEN')
  const response = await fetch(`${API_BASE}${path}`, {
    credentials: 'include',
    ...init,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(includeCsrf && csrfToken ? { 'X-XSRF-TOKEN': decodeURIComponent(csrfToken) } : {}),
      ...init.headers,
    },
  })

  if (!response.ok) {
    const errorPayload = (await safeJson(response)) as { message?: string; error?: string } | null
    const message = errorPayload?.message ?? `Request failed (${response.status})`
    throw new Error(errorPayload?.error ? `${message}: ${errorPayload.error}` : message)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return (await response.json()) as T
}

async function safeJson(response: Response): Promise<unknown | null> {
  try {
    return await response.json()
  } catch {
    return null
  }
}

function readCookie(name: string): string | null {
  const cookie = document.cookie
    .split('; ')
    .find((part) => part.startsWith(`${name}=`))
    ?.split('=')
    .slice(1)
    .join('=')

  return cookie ?? null
}
