const API = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
const SESSION_KEY = 'bw_local_session'
const META_KEY = 'bw_local_meta'
const listeners = new Set()

const read = (key) => {
  try {
    return JSON.parse(localStorage.getItem(key))
  } catch {
    return null
  }
}

const write = (key, value) => {
  if (value === null) localStorage.removeItem(key)
  else localStorage.setItem(key, JSON.stringify(value))
}

const metaFor = (email) => (read(META_KEY) || {})[String(email).toLowerCase()] || {}

const saveMeta = (email, meta) =>
  write(META_KEY, { ...(read(META_KEY) || {}), [String(email).toLowerCase()]: meta })

const toUser = (u) => ({
  id: `local-${u.id}`,
  email: u.email,
  user_metadata: { full_name: u.name, ...metaFor(u.email) },
})

const setSession = (session, event) => {
  write(SESSION_KEY, session)
  listeners.forEach((fn) => fn(event, session))
}

const unavailable = async () => ({
  data: null,
  error: new Error('Not available in local mode. Sign in with email and password.'),
})

async function post(path, body) {
  let res
  try {
    res = await fetch(`${API}${path}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(body),
    })
  } catch {
    return { data: null, error: new Error('Cannot reach the local API. Is the backend running?') }
  }
  const data = await res.json().catch(() => ({}))
  if (!res.ok) {
    const first = data.errors ? Object.values(data.errors)[0]?.[0] : null
    return { data: null, error: new Error(first || data.message || 'Request failed') }
  }
  return { data, error: null }
}

export const localAuth = {
  auth: {
    getSession: async () => ({ data: { session: read(SESSION_KEY) }, error: null }),

    getUser: async () => ({ data: { user: read(SESSION_KEY)?.user || null }, error: null }),

    onAuthStateChange: (fn) => {
      listeners.add(fn)
      return { data: { subscription: { unsubscribe: () => listeners.delete(fn) } } }
    },

    signInWithPassword: async ({ email, password }) => {
      const { data, error } = await post('/login', { email, password })
      if (error) return { data: { user: null, session: null }, error }
      const user = toUser(data.user)
      const session = { access_token: data.token, user }
      setSession(session, 'SIGNED_IN')
      return { data: { user, session }, error: null }
    },

    signUp: async ({ email, password, options }) => {
      const meta = options?.data || {}
      const { data, error } = await post('/register', {
        name: meta.full_name || email.split('@')[0],
        email,
        password,
        password_confirmation: password,
      })
      if (error) return { data: { user: null, session: null }, error }
      saveMeta(email, meta)
      return { data: { user: toUser(data.user), session: null }, error: null }
    },

    updateUser: async ({ data: meta, password }) => {
      const session = read(SESSION_KEY)
      if (!session) return { data: { user: null }, error: new Error('You must be signed in.') }
      if (password) return unavailable()
      const merged = { ...metaFor(session.user.email), ...(meta || {}) }
      saveMeta(session.user.email, merged)
      const user = { ...session.user, user_metadata: { ...session.user.user_metadata, ...merged } }
      setSession({ ...session, user }, 'USER_UPDATED')
      return { data: { user }, error: null }
    },

    signOut: async () => {
      setSession(null, 'SIGNED_OUT')
      return { error: null }
    },

    signInWithOAuth: unavailable,
    resetPasswordForEmail: unavailable,
  },

  rpc: async () => ({ data: false, error: null }),

  from: () => ({
    upsert: async () => ({ error: null }),
  }),
}

export default localAuth
