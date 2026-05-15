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

function readCookie(name: string): string | null {
  const cookie = document.cookie
    .split('; ')
    .find((part) => part.startsWith(`${name}=`))
    ?.split('=')
    .slice(1)
    .join('=')

  return cookie ?? null
}
