import { createContext, useContext, useEffect, useState } from 'react'
import { supabase } from '../lib/supabase'
import api from '../lib/api'
import { normalizePhone } from '../lib/phone'

const AuthContext = createContext(null)

// Role allowlists from frontend/.env. Admins get the full /admin dashboard;
// editors can only edit landing-page content (/admin/content).
const toList = (v) =>
  (v || '')
    .split(',')
    .map((s) => s.trim().toLowerCase())
    .filter(Boolean)
const ADMIN_EMAILS = toList(import.meta.env.VITE_ADMIN_EMAILS)
const EDITOR_EMAILS = toList(import.meta.env.VITE_EDITOR_EMAILS)
const HR_EMAILS = toList(import.meta.env.VITE_HR_EMAILS)

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
  // The DB-backed role from GET /me. Env allowlists give an instant first guess
  // (below); this refines it for runtime-assigned roles like cashier/editor.
  const [dbRole, setDbRole] = useState(null)
  // True until /me resolves for the current user. Route guards wait on this so
  // a DB-assigned cashier/editor isn't bounced before their role is known.
  const [roleLoading, setRoleLoading] = useState(true)

  // Ask the API for the signed-in user's effective role. The server already
  // merges the env allowlists, so its answer is authoritative once it lands.
  //
  // `roleLoading` is a one-way latch: it starts true and is only ever set to
  // false here. We deliberately never flip it back to true, because Supabase
  // fires onAuthStateChange on every tab refocus / token refresh — if those
  // background refreshes set roleLoading=true, the route guards would flash
  // their loading screen and remount the admin page ("Loading content…") on
  // every tab switch. The first resolution still gates the guards (so a
  // DB-assigned cashier/editor isn't bounced on a hard refresh); later
  // refreshes update the role silently.
  const refreshRole = async (current) => {
    if (!current) {
      setDbRole(null)
      setRoleLoading(false)
      return
    }
    try {
      const { data } = await api.get('/me')
      setDbRole(data?.role || 'customer')
    } catch {
      // Leave dbRole null and fall back to the env-based guess.
      setDbRole(null)
    } finally {
      setRoleLoading(false)
    }
  }

  useEffect(() => {
    // Restore any existing session on first load.
    supabase.auth.getSession().then(({ data }) => {
      const u = normalize(data.session?.user)
      setUser(u)
      setLoading(false)
      refreshRole(u)
    })

    // Keep React state in sync with Supabase (login, logout, token refresh,
    // and the redirect back from Google OAuth all flow through here).
    const { data: sub } = supabase.auth.onAuthStateChange((_event, session) => {
      const u = normalize(session?.user)
      setUser(u)
      refreshRole(u)
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

  // Facebook OAuth — redirects the browser to Facebook, then back to /dashboard.
  const loginWithFacebook = async () => {
    const { error } = await supabase.auth.signInWithOAuth({
      provider: 'facebook',
      options: { redirectTo: `${window.location.origin}/dashboard` },
    })
    if (error) throw error
  }

  // Email + password sign up. `payload` comes from the register form.
  const register = async ({ name, email, contact_number, password, password_confirmation }) => {
    if (password_confirmation !== undefined && password !== password_confirmation) {
      throw new Error('Passwords do not match.')
    }

    // Reject duplicate numbers before creating the account. The user isn't
    // signed in yet, so we ask the database via a SECURITY DEFINER function.
    const normalized = normalizePhone(contact_number)
    const { data: taken, error: checkError } = await supabase.rpc('contact_number_taken', {
      p_number: normalized,
    })
    if (checkError) throw checkError
    if (taken) throw new Error('This contact number is already in use by another account.')

    const { data, error } = await supabase.auth.signUp({
      email,
      password,
      options: { data: { full_name: name, contact_number } },
    })
    if (error) throw error

    // If email confirmation is off, Supabase auto-creates a session. Use that
    // brief window to claim the number in profiles so the UNIQUE constraint
    // closes any race between two simultaneous sign-ups.
    if (data.session && data.user) {
      const { error: claimError } = await supabase
        .from('profiles')
        .upsert({ id: data.user.id, contact_number: normalized }, { onConflict: 'id' })
      if (claimError && claimError.code === '23505') {
        await supabase.auth.signOut()
        throw new Error('This contact number is already in use by another account.')
      }
    }

    // We want the user to sign in explicitly afterwards, so drop that session.
    await supabase.auth.signOut()
    return normalize(data.user)
  }

  // Save a contact number onto the current user (used by the complete-profile
  // step after Google sign-in, where no phone was collected). Uniqueness is
  // enforced by a UNIQUE constraint on public.profiles.contact_number — the
  // client can't read other users' metadata, so the database is the gatekeeper.
  const updateContactNumber = async (contact_number) => {
    const {
      data: { user: current },
    } = await supabase.auth.getUser()
    if (!current) throw new Error('You must be signed in to add a contact number.')

    // Claim the normalized number in profiles first; a duplicate trips the
    // UNIQUE constraint (Postgres error 23505) before we touch user metadata.
    const { error: profileError } = await supabase
      .from('profiles')
      .upsert(
        { id: current.id, contact_number: normalizePhone(contact_number), updated_at: new Date().toISOString() },
        { onConflict: 'id' },
      )
    if (profileError) {
      if (profileError.code === '23505') {
        throw new Error('This contact number is already in use by another account.')
      }
      throw profileError
    }

    // Keep the user-entered value on the auth user for display.
    const { data, error } = await supabase.auth.updateUser({ data: { contact_number } })
    if (error) throw error
    setUser(normalize(data.user))
    return normalize(data.user)
  }

  // Update the display name on the current user (Profile page).
  const updateProfile = async ({ name }) => {
    const { data, error } = await supabase.auth.updateUser({ data: { full_name: name } })
    if (error) throw error
    setUser(normalize(data.user))
    return normalize(data.user)
  }

  // Change the signed-in user's password. Supabase updates the password for the
  // current session; there's no separate "old password" check on the client, so
  // we just validate the new value matches its confirmation here.
  const changePassword = async (password, password_confirmation) => {
    if (!password || password.length < 6) {
      throw new Error('Password must be at least 6 characters.')
    }
    if (password_confirmation !== undefined && password !== password_confirmation) {
      throw new Error('Passwords do not match.')
    }
    const { error } = await supabase.auth.updateUser({ password })
    if (error) throw error
  }

  const logout = async () => {
    await supabase.auth.signOut()
    setUser(null)
    setDbRole(null)
  }

  const email = (user?.email || '').toLowerCase()
  const envAdmin = ADMIN_EMAILS.includes(email)
  const envEditor = EDITOR_EMAILS.includes(email)
  const envHr = HR_EMAILS.includes(email)

  // Effective role: the DB answer from /me once it lands, otherwise an instant
  // env-based guess (so admins don't flicker on first paint).
  const role = !user
    ? null
    : dbRole || (envAdmin ? 'admin' : envEditor ? 'editor' : envHr ? 'hr' : 'customer')

  const isAdmin = !!user && (role === 'admin' || envAdmin)
  const isEditor = !!user && (isAdmin || role === 'editor' || envEditor)
  const isHr = !!user && (isAdmin || role === 'hr' || envHr)
  const isCashier = !!user && role === 'cashier'

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        roleLoading,
        role,
        isAdmin,
        isEditor,
        isHr,
        isCashier,
        login,
        loginWithGoogle,
        loginWithFacebook,
        register,
        updateContactNumber,
        updateProfile,
        changePassword,
        logout,
      }}
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
