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
  status: 'draft' | 'scheduled' | 'active' | 'ended' | 'finalized'
  finalized_at: string | null
  finalized_by_user_id: number | null
  created_by_user_id: number | null
  maps?: ApiMap[]
  created_at: string | null
  updated_at: string | null
}

export type ApiTrackmaniaClub = {
  id: number
  club_id: string
  name: string
  tag: string | null
  description: string | null
  member_count: number | null
  icon_url: string | null
  is_primary: boolean
  last_synced_at: string | null
  created_at: string | null
  updated_at: string | null
}

export type ApiTrackmaniaPlayer = {
  id: number
  account_id: string
  display_name: string
  zone_id: string | null
  zone_name: string | null
  is_active: boolean
  last_seen_in_club_at: string | null
}

export type ApiClubMember = {
  id: number
  trackmania_club_id: number
  trackmania_player_id: number
  joined_at: string | null
  synced_at: string | null
  is_active: boolean
  player: ApiTrackmaniaPlayer
}

export type ApiPaginatedCollection<T> = {
  data: T[]
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
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

export async function finalizeAdminSeason(id: number): Promise<{
  rewards_granted: { player_id: number; position: number; type: string; points: number }[]
  players_processed: number
  final_standings: { player_id: number; current_position: number; time_ms: number }[]
}> {
  const response = await request<ApiResource<{
    rewards_granted: { player_id: number; position: number; type: string; points: number }[]
    players_processed: number
    final_standings: { player_id: number; current_position: number; time_ms: number }[]
  }>>(`/api/admin/seasons/${id}/finalize`, { method: 'POST' })

  return response.data
}

export async function updateAdminSeasonStatuses(): Promise<{ activated: number[]; ended: number[] }> {
  const response = await request<ApiResource<{ activated: number[]; ended: number[] }>>('/api/admin/seasons/update-statuses', {
    method: 'POST',
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

export async function adminClubs(): Promise<ApiTrackmaniaClub[]> {
  const payload = await request<ApiPaginatedCollection<ApiTrackmaniaClub>>('/api/admin/clubs')
  return payload.data
}

export async function adminClub(id: number): Promise<ApiTrackmaniaClub> {
  const payload = await request<ApiResource<ApiTrackmaniaClub>>(`/api/admin/clubs/${id}`)
  return payload.data
}

export async function adminPrimaryClub(): Promise<ApiTrackmaniaClub> {
  const payload = await request<ApiResource<ApiTrackmaniaClub>>('/api/admin/club')
  return payload.data
}

export async function syncAdminClub(clubId: string): Promise<{
  club: ApiTrackmaniaClub
  imported: number
  updated: number
  deactivated: number
  total_members: number
}> {
  return request('/api/admin/clubs/sync', {
    method: 'POST',
    body: JSON.stringify({ club_id: clubId }),
  })
}

export async function syncAdminPrimaryClub(clubId: string): Promise<{
  club: ApiTrackmaniaClub
  imported: number
  updated: number
  deactivated: number
  enriched: number
  total_members: number
}> {
  return request('/api/admin/club/sync', {
    method: 'POST',
    body: JSON.stringify({ club_id: clubId }),
  })
}

export async function adminClubMembers(
  clubId: number,
  params: {
    search?: string
    active?: '1' | '0' | ''
    sort?: 'display_name' | 'last_seen'
    direction?: 'asc' | 'desc'
    page?: number
  } = {},
): Promise<ApiPaginatedCollection<ApiClubMember>> {
  const query = new URLSearchParams()
  if (params.search) query.set('search', params.search)
  if (params.active !== undefined && params.active !== '') query.set('active', params.active)
  if (params.sort) query.set('sort', params.sort)
  if (params.direction) query.set('direction', params.direction)
  if (params.page) query.set('page', String(params.page))

  const suffix = query.toString() ? `?${query.toString()}` : ''
  return request<ApiPaginatedCollection<ApiClubMember>>(`/api/admin/clubs/${clubId}/members${suffix}`)
}

export async function adminPrimaryClubMembers(
  params: {
    search?: string
    active?: '1' | '0' | ''
    sort?: 'display_name' | 'last_seen'
    direction?: 'asc' | 'desc'
    page?: number
  } = {},
): Promise<ApiPaginatedCollection<ApiClubMember>> {
  const query = new URLSearchParams()
  if (params.search) query.set('search', params.search)
  if (params.active !== undefined && params.active !== '') query.set('active', params.active)
  if (params.sort) query.set('sort', params.sort)
  if (params.direction) query.set('direction', params.direction)
  if (params.page) query.set('page', String(params.page))

  const suffix = query.toString() ? `?${query.toString()}` : ''
  return request<ApiPaginatedCollection<ApiClubMember>>(`/api/admin/club/members${suffix}`)
}

export async function publicClubs(): Promise<ApiTrackmaniaClub[]> {
  const payload = await request<ApiPaginatedCollection<ApiTrackmaniaClub>>('/api/clubs', {}, false)
  return payload.data
}

export async function publicClub(id: number): Promise<ApiTrackmaniaClub> {
  const payload = await request<ApiResource<ApiTrackmaniaClub>>(`/api/clubs/${id}`, {}, false)
  return payload.data
}

export async function publicClubMembers(clubId: number, active: '1' | '0' = '1'): Promise<ApiClubMember[]> {
  const payload = await request<ApiPaginatedCollection<ApiClubMember>>(`/api/clubs/${clubId}/members?active=${active}`, {}, false)
  return payload.data
}

export type ApiSeasonMapPlayerRecord = {
  id: number
  season_id: number
  map_id: number
  map?: ApiMap
  trackmania_player_id: number
  player?: ApiTrackmaniaPlayer
  global_position: number | null
  current_position: number | null
  time_ms: number | null
  baseline_time_ms: number | null
  first_seen_at: string | null
  last_seen_at: string | null
  last_improved_at: string | null
  total_improvements: number
}

export type ApiPointEvent = {
  id: number
  season_id: number
  map_id: number | null
  map?: ApiMap | null
  trackmania_player_id: number
  player?: ApiTrackmaniaPlayer
  type: string
  points: number
  description: string | null
  metadata: Record<string, unknown> | null
  created_at: string | null
}

export type ApiStandingEntry = {
  position: number
  player_id: number
  player: ApiTrackmaniaPlayer | null
  total_points: number
  maps_completed: number
  event_count: number
  events_by_type: Record<string, number>
}

export type ApiPlayerSeasonDetail = {
  player_id: number
  player: ApiTrackmaniaPlayer | null
  total_points: number
  position: number | null
  maps_completed: number
  event_count: number
  events_by_type: Record<string, number>
  events: ApiPointEvent[]
  milestones: ApiPlayerMapMilestone[]
}

export type ApiPlayerMapMilestone = {
  id: number
  season_id: number
  map_id: number
  map?: ApiMap | null
  trackmania_player_id: number
  milestone_key: string
  achieved_at: string
}

export type ApiLeaderboardPoll = {
  id: number
  season_id: number | null
  season?: ApiSeason | null
  status: string
  started_at: string | null
  finished_at: string | null
  maps_polled_count: number
  players_processed_count: number
  snapshot_count?: number
  error_message: string | null
}

export type ApiSeasonLeaderboardEntry = {
  id: number
  trackmania_player_id: number
  player: ApiTrackmaniaPlayer | null
  global_position: number | null
  current_position: number | null
  time_ms: number | null
}

export type ApiPollResult = {
  message: string
  total_maps: number
  maps_processed: number
  snapshots_created: number
  records_updated: number
}

export async function pollSeason(seasonId: number): Promise<ApiPollResult> {
  return request<ApiPollResult>(`/api/admin/seasons/${seasonId}/poll`, { method: 'POST' })
}

export async function adminSeasonRecords(
  seasonId: number,
  params: { map_id?: number; player_id?: number; sort?: string; direction?: string; page?: number } = {},
): Promise<ApiSeasonMapPlayerRecord[]> {
  const query = new URLSearchParams()
  if (params.map_id) query.set('map_id', String(params.map_id))
  if (params.player_id) query.set('player_id', String(params.player_id))
  if (params.sort) query.set('sort', params.sort)
  if (params.direction) query.set('direction', params.direction)
  if (params.page) query.set('page', String(params.page))

  const suffix = query.toString() ? `?${query.toString()}` : ''
  const payload = await request<ApiPaginatedCollection<ApiSeasonMapPlayerRecord>>(`/api/admin/seasons/${seasonId}/records${suffix}`)
  return payload.data
}

export async function adminPolls(): Promise<ApiLeaderboardPoll[]> {
  const payload = await request<ApiPaginatedCollection<ApiLeaderboardPoll>>('/api/admin/polls')
  return payload.data
}

export async function adminPoll(id: number): Promise<ApiLeaderboardPoll> {
  const payload = await request<ApiResource<ApiLeaderboardPoll>>(`/api/admin/polls/${id}`)
  return payload.data
}

export async function publicSeasonLeaderboard(slug: string): Promise<{
  season: ApiSeason
  leaderboard: { map: { id: number; name: string | null; uid: string }; entries: ApiSeasonLeaderboardEntry[] }[]
}> {
  const payload = await request<ApiResource<{ season: ApiSeason; leaderboard: { map: { id: number; name: string | null; uid: string }; entries: ApiSeasonLeaderboardEntry[] }[] }>>(`/api/seasons/${slug}/leaderboard`, {}, false)
  return payload.data
}

export async function publicSeasonStandings(slug: string): Promise<{
  season_id: number
  season_name: string
  standings: ApiStandingEntry[]
}> {
  const payload = await request<ApiResource<{ season_id: number; season_name: string; standings: ApiStandingEntry[] }>>(`/api/seasons/${slug}/standings`, {}, false)
  return payload.data
}

export async function publicSeasonEvents(
  slug: string,
  params: { player_id?: number; page?: number } = {},
): Promise<ApiPaginatedCollection<ApiPointEvent>> {
  const query = new URLSearchParams()
  if (params.player_id) query.set('player_id', String(params.player_id))
  if (params.page) query.set('page', String(params.page))

  const suffix = query.toString() ? `?${query.toString()}` : ''
  return request<ApiPaginatedCollection<ApiPointEvent>>(`/api/seasons/${slug}/events${suffix}`, {}, false)
}

export async function publicSeasonPlayer(slug: string, playerId: number): Promise<ApiPlayerSeasonDetail> {
  const payload = await request<ApiResource<ApiPlayerSeasonDetail>>(`/api/seasons/${slug}/players/${playerId}`, {}, false)
  return payload.data
}

export async function adminSeasonPoints(seasonId: number): Promise<{
  season_id: number
  standings: ApiStandingEntry[]
  total_events: number
}> {
  const payload = await request<ApiResource<{ season_id: number; standings: ApiStandingEntry[]; total_events: number }>>(`/api/admin/seasons/${seasonId}/points`)
  return payload.data
}

export async function adminSeasonEvents(
  seasonId: number,
  params: { player_id?: number; map_id?: number; type?: string; page?: number; per_page?: number } = {},
): Promise<ApiPaginatedCollection<ApiPointEvent>> {
  const query = new URLSearchParams()
  if (params.player_id) query.set('player_id', String(params.player_id))
  if (params.map_id) query.set('map_id', String(params.map_id))
  if (params.type) query.set('type', params.type)
  if (params.page) query.set('page', String(params.page))
  if (params.per_page) query.set('per_page', String(params.per_page))

  const suffix = query.toString() ? `?${query.toString()}` : ''
  return request<ApiPaginatedCollection<ApiPointEvent>>(`/api/admin/seasons/${seasonId}/events${suffix}`)
}

export async function recalculateSeasonPoints(seasonId: number): Promise<{ message: string; events_count: number }> {
  return request(`/api/admin/seasons/${seasonId}/recalculate`, { method: 'POST' })
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
