import { createClient } from '@supabase/supabase-js'
import { localAuth } from './localAuth'

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY

const useLocalAuth = !supabaseUrl || !supabaseAnonKey

if (useLocalAuth && import.meta.env.DEV) {
  console.info('Supabase is not configured: using local auth against the Laravel API.')
}

// The real client only exists in the browser. During prerender/SSR (Node),
// `createClient` would construct a realtime client that needs a WebSocket Node
// doesn't have — and nothing actually calls Supabase while rendering static HTML
// (every usage is inside effects). So on the server we export a stub that throws
// only if something unexpectedly touches it.
const isBrowser = typeof window !== 'undefined'

export const supabase = isBrowser && useLocalAuth
  ? localAuth
  : isBrowser
  ? createClient(supabaseUrl, supabaseAnonKey, {
      auth: {
        persistSession: true,
        autoRefreshToken: true,
        detectSessionInUrl: true, // handles the redirect back from Google OAuth
      },
    })
  : new Proxy(
      {},
      {
        get() {
          throw new Error('Supabase client is unavailable during server prerender')
        },
      },
    )

export default supabase
