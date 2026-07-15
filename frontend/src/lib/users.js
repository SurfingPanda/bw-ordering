import api from './api'

// Admin user management — all calls hit the Laravel API and throw on error so
// the UI can catch. The Supabase access token is attached by the axios
// interceptor in api.js; the server gates these to admins (except /me).

// The signed-in user's effective role flags. Used by AuthContext.
export async function fetchMyRole() {
  const { data } = await api.get('/me')
  return data
}

// Admin: every Supabase account with its effective role. Throws a friendly
// message (e.g. 503) if the service-role key isn't configured.
export async function fetchUsers() {
  const { data } = await api.get('/users')
  return data || []
}

// Admin: set a user's role. role ∈ admin|editor|cashier|hr|customer.
export async function setUserRole(email, role) {
  const { data } = await api.patch('/users/role', { email, role })
  return data
}

// Admin: rename a Supabase account's login email (Admin API). Carries any
// stored role over to the new email.
export async function renameUserEmail(id, newEmail, oldEmail) {
  const { data } = await api.post('/users/rename', {
    id,
    new_email: newEmail,
    old_email: oldEmail || null,
  })
  return data
}
