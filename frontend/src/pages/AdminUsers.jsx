import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { createPortal } from 'react-dom'
import { useAuth } from '../context/AuthContext'
import { fetchUsers, setUserRole, renameUserEmail } from '../lib/users'

// Selectable roles, lowest → highest privilege. 'customer' clears any role.
const ROLES = [
  { value: 'customer', label: 'Customer' },
  { value: 'cashier', label: 'Cashier' },
  { value: 'editor', label: 'Editor' },
  { value: 'hr', label: 'HR' },
  { value: 'admin', label: 'Admin' },
]

const ROLE_STYLES = {
  admin: 'bg-brand-100 text-brand-700',
  editor: 'bg-blue-100 text-blue-700',
  cashier: 'bg-green-100 text-green-700',
  hr: 'bg-purple-100 text-purple-700',
  customer: 'bg-slate-100 text-slate-600',
}

const fmtDate = (s) =>
  s ? new Date(s).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'

export default function AdminUsers() {
  const { user } = useAuth()
  const [users, setUsers] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [query, setQuery] = useState('')
  const [renaming, setRenaming] = useState(null) // the user row being renamed

  const load = async () => {
    setLoading(true)
    setError('')
    try {
      setUsers(await fetchUsers())
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
  }, [])

  const changeRole = async (email, role) => {
    const prev = users
    setUsers((us) => us.map((u) => (u.email === email ? { ...u, role } : u)))
    try {
      await setUserRole(email, role)
    } catch (err) {
      window.alert(`Could not change role: ${err.message}`)
      setUsers(prev) // revert
    }
  }

  const onRenamed = (id, newEmail) => {
    setUsers((us) => us.map((u) => (u.id === id ? { ...u, email: newEmail } : u)))
    setRenaming(null)
  }

  const visible = useMemo(() => {
    const q = query.trim().toLowerCase()
    if (!q) return users
    return users.filter(
      (u) => u.email?.toLowerCase().includes(q) || u.name?.toLowerCase().includes(q),
    )
  }, [users, query])

  const myEmail = (user?.email || '').toLowerCase()

  return (
    <div className="min-h-screen bg-navy-50/40 text-navy-800">
      <header className="sticky top-0 z-20 border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-5xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
          <div className="flex items-center gap-3">
            <Link
              to="/admin"
              className="rounded-full border border-slate-300 px-3 py-1.5 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600"
            >
              ← Dashboard
            </Link>
            <div>
              <h1 className="text-xl font-bold text-navy-800">Users</h1>
              <p className="text-xs text-slate-500">{users.length} accounts</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <input
              type="search"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Search name or email"
              className="w-full rounded-full border border-slate-300 bg-white px-4 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 sm:w-64"
            />
            <button
              onClick={load}
              className="shrink-0 rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600"
            >
              Refresh
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        {loading ? (
          <p className="py-16 text-center text-sm text-slate-500">Loading…</p>
        ) : error ? (
          <div className="rounded-2xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">
            <p className="font-semibold">Couldn&apos;t load users.</p>
            <p className="mt-1">{error}</p>
            <p className="mt-3 text-red-600/80">
              Make sure <code>SUPABASE_SERVICE_ROLE_KEY</code> is set in{' '}
              <code>backend/.env</code> and that your email is the founding admin.
            </p>
          </div>
        ) : visible.length === 0 ? (
          <div className="py-16 text-center text-sm text-slate-500">No users found.</div>
        ) : (
          <div className="space-y-3">
            {visible.map((u) => {
              const isSelf = u.email?.toLowerCase() === myEmail
              return (
                <div
                  key={u.id || u.email}
                  className="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm"
                >
                  <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-navy-100 text-sm font-bold text-navy-700">
                    {(u.name || u.email || '?').charAt(0).toUpperCase()}
                  </span>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <p className="truncate font-semibold text-navy-800">{u.name || '—'}</p>
                      <span
                        className={`rounded-full px-2 py-0.5 text-[0.65rem] font-semibold capitalize ${
                          ROLE_STYLES[u.role] || ROLE_STYLES.customer
                        }`}
                      >
                        {u.role}
                      </span>
                      {isSelf && (
                        <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[0.65rem] font-semibold text-amber-700">
                          You
                        </span>
                      )}
                    </div>
                    <p className="truncate text-xs text-slate-500">{u.email}</p>
                    <p className="text-[0.65rem] text-slate-400">Joined {fmtDate(u.created_at)}</p>
                  </div>

                  <div className="flex items-center gap-2">
                    <button
                      type="button"
                      onClick={() => setRenaming(u)}
                      className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600"
                    >
                      Rename email
                    </button>
                    {u.is_env_admin ? (
                      <span className="rounded-lg bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-400">
                        Founding admin
                      </span>
                    ) : (
                      <select
                        value={u.role}
                        onChange={(e) => changeRole(u.email, e.target.value)}
                        disabled={isSelf}
                        title={isSelf ? "You can't change your own role" : undefined}
                        className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-brand-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
                      >
                        {ROLES.map((r) => (
                          <option key={r.value} value={r.value}>
                            {r.label}
                          </option>
                        ))}
                      </select>
                    )}
                  </div>
                </div>
              )
            })}
          </div>
        )}
      </main>

      {renaming && (
        <RenameModal
          target={renaming}
          onCancel={() => setRenaming(null)}
          onRenamed={onRenamed}
        />
      )}
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* rename modal                                                        */
/* ------------------------------------------------------------------ */
function RenameModal({ target, onCancel, onRenamed }) {
  const [value, setValue] = useState(target.email || '')
  const [busy, setBusy] = useState(false)
  const [err, setErr] = useState('')

  const submit = async (e) => {
    e.preventDefault()
    setErr('')
    const next = value.trim().toLowerCase()
    if (!next || next === (target.email || '').toLowerCase()) {
      setErr('Enter a different email address.')
      return
    }
    setBusy(true)
    try {
      await renameUserEmail(target.id, next, target.email)
      onRenamed(target.id, next)
    } catch (e2) {
      setErr(e2.message)
      setBusy(false)
    }
  }

  if (typeof document === 'undefined') return null

  return createPortal(
    <div
      className="fixed inset-0 z-[80] flex items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm"
      onClick={() => !busy && onCancel()}
      role="dialog"
      aria-modal="true"
      aria-label="Rename email"
    >
      <form
        onSubmit={submit}
        className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        <h3 className="text-lg font-bold text-navy-800">Rename login email</h3>
        <p className="mt-2 text-sm leading-relaxed text-slate-500">
          Changes the Supabase login email for{' '}
          <span className="font-semibold text-navy-700">{target.email}</span>. The user signs in
          with the new address afterwards; their assigned role carries over.
        </p>
        <input
          type="email"
          value={value}
          onChange={(e) => setValue(e.target.value)}
          autoFocus
          className="mt-4 w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
          placeholder="new@email.com"
        />
        {err && <p className="mt-2 text-sm text-red-600">{err}</p>}
        <div className="mt-6 flex justify-end gap-3">
          <button
            type="button"
            onClick={onCancel}
            disabled={busy}
            className="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-navy-700 transition hover:bg-slate-50 disabled:opacity-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={busy}
            className="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:cursor-wait disabled:opacity-80"
          >
            {busy && (
              <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
            )}
            {busy ? 'Renaming…' : 'Rename'}
          </button>
        </div>
      </form>
    </div>,
    document.body,
  )
}
