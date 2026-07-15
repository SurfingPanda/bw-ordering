import axios from 'axios'
import { supabase } from './supabase'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    Accept: 'application/json',
  },
})

// Supabase is the identity provider. Attach its access token (when signed in)
// as a Bearer token so the Laravel API can verify the user via SupabaseAuth.
api.interceptors.request.use(async (config) => {
  try {
    const { data } = await supabase.auth.getSession()
    const token = data?.session?.access_token
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
  } catch {
    // No session (guest) or SSR — send the request unauthenticated.
  }
  return config
})

// Surface the server's error message (Laravel validation / 401 / 403) as a
// plain Error so the UI's `err.message` shows something useful.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const data = error.response?.data
    const message = data?.message || data?.error || error.message || 'Request failed'
    return Promise.reject(new Error(message))
  },
)

export default api
