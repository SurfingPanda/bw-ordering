import { createContext, useContext, useEffect, useState } from 'react'
import { supabase } from '../lib/supabase'

const AuthContext = createContext(null)

// Map a Supabase user onto the shape the rest of the app expects (it reads `name`).
function normalize(supaUser) {
  if (!supaUser) return null
  const meta = supaUser.user_metadata || {}
  return {
    id: supaUser.id,
    email: supaUser.email,
    name: meta.full_name || meta.name || supaUser.email?.split('@')[0] || 'Customer',
    contact_number: meta.contact_number || null,
    avatar_url: meta.avatar_url || null,
  }
}

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    // Restore any existing session on first load.
    supabase.auth.getSession().then(({ data }) => {
      setUser(normalize(data.session?.user))
      setLoading(false)
    })

    // Keep React state in sync with Supabase (login, logout, token refresh,
    // and the redirect back from Google OAuth all flow through here).
    const { data: sub } = supabase.auth.onAuthStateChange((_event, session) => {
      setUser(normalize(session?.user))
    })

    return () => sub.subscription.unsubscribe()
  }, [])

  // Email + password sign in.
  const login = async (email, password) => {
    const { data, error } = await supabase.auth.signInWithPassword({ email, password })
    if (error) throw error
    return normalize(data.user)
  }

  // Google OAuth — redirects the browser to Google, then back to /dashboard.
  const loginWithGoogle = async () => {
    const { error } = await supabase.auth.signInWithOAuth({
      provider: 'google',
      options: { redirectTo: `${window.location.origin}/dashboard` },
    })
    if (error) throw error
  }

  // Email + password sign up. `payload` comes from the register form.
  const register = async ({ name, email, contact_number, password, password_confirmation }) => {
    if (password_confirmation !== undefined && password !== password_confirmation) {
      throw new Error('Passwords do not match.')
    }
    const { data, error } = await supabase.auth.signUp({
      email,
      password,
      options: { data: { full_name: name, contact_number } },
    })
    if (error) throw error
    // If email confirmation is off, Supabase auto-creates a session. We want the
    // user to sign in explicitly afterwards, so drop that session here.
    await supabase.auth.signOut()
    return normalize(data.user)
  }

  // Save a contact number onto the current user (used by the complete-profile
  // step after Google sign-in, where no phone was collected).
  const updateContactNumber = async (contact_number) => {
    const { data, error } = await supabase.auth.updateUser({ data: { contact_number } })
    if (error) throw error
    setUser(normalize(data.user))
    return normalize(data.user)
  }

  const logout = async () => {
    await supabase.auth.signOut()
    setUser(null)
  }

  return (
    <AuthContext.Provider
      value={{ user, loading, login, loginWithGoogle, register, updateContactNumber, logout }}
    >
      {children}
    </AuthContext.Provider>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return ctx
}
