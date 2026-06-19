import { createClient } from '@supabase/supabase-js'

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY

if (!supabaseUrl || !supabaseAnonKey) {
  // Fail loudly in dev rather than getting cryptic auth errors later.
  console.error('Missing VITE_SUPABASE_URL or VITE_SUPABASE_ANON_KEY in frontend/.env')
}

// The real client only exists in the browser. During prerender/SSR (Node),
// `createClient` would construct a realtime client that needs a WebSocket Node
// doesn't have — and nothing actually calls Supabase while rendering static HTML
// (every usage is inside effects). So on the server we export a stub that throws
// only if something unexpectedly touches it.
const isBrowser = typeof window !== 'undefined'

export const supabase = isBrowser
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
