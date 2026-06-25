import { useState } from 'react'
import { Link, Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { EyeIcon, EyeOffIcon } from '../components/EyeIcons'
import { sanitizePhone, isValidPhone } from '../lib/phone'

// Account settings page. Reached from the Menu header account dropdown behind
// ProtectedRoute, so `user` is always present and already has a contact number.
export default function Profile() {
  const { user, loading, updateProfile, updateContactNumber, changePassword } = useAuth()

  // Profile info form.
  const [name, setName] = useState(user?.name || '')
  const [contact, setContact] = useState(user?.contact_number || '')
  const [savingInfo, setSavingInfo] = useState(false)
  const [infoError, setInfoError] = useState('')
  const [infoSuccess, setInfoSuccess] = useState('')

  // Password form.
  const [password, setPassword] = useState('')
  const [passwordConfirm, setPasswordConfirm] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [savingPw, setSavingPw] = useState(false)
  const [pwError, setPwError] = useState('')
  const [pwSuccess, setPwSuccess] = useState('')

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-navy-50/40 text-slate-500">
        Loading…
      </div>
    )
  }
  if (!user) return <Navigate to="/login" replace />

  const handleInfoSubmit = async (e) => {
    e.preventDefault()
    setInfoError('')
    setInfoSuccess('')

    const trimmedName = name.trim()
    if (trimmedName.length < 2) {
      setInfoError('Please enter your full name.')
      return
    }
    if (!isValidPhone(contact)) {
      setInfoError('Please enter a valid contact number.')
      return
    }

    const nameChanged = trimmedName !== (user.name || '')
    const contactChanged = contact.trim() !== (user.contact_number || '')
    if (!nameChanged && !contactChanged) {
      setInfoSuccess('No changes to save.')
      return
    }

    setSavingInfo(true)
    try {
      // Update the contact number first — it's the one that can fail on the
      // uniqueness check, so we don't half-save the name if it's taken.
      if (contactChanged) await updateContactNumber(contact.trim())
      if (nameChanged) await updateProfile({ name: trimmedName })
      setInfoSuccess('Your information has been updated.')
    } catch (err) {
      setInfoError(err.message || 'Unable to update your information.')
    } finally {
      setSavingInfo(false)
    }
  }

  const handlePasswordSubmit = async (e) => {
    e.preventDefault()
    setPwError('')
    setPwSuccess('')

    if (password.length < 6) {
      setPwError('Password should be at least 6 characters.')
      return
    }
    if (password !== passwordConfirm) {
      setPwError('Passwords do not match.')
      return
    }

    setSavingPw(true)
    try {
      await changePassword(password, passwordConfirm)
      setPwSuccess('Your password has been updated.')
      setPassword('')
      setPasswordConfirm('')
    } catch (err) {
      setPwError(err.message || 'Unable to update your password.')
    } finally {
      setSavingPw(false)
    }
  }

  const initials = (user.name || user.email || '?')
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()

  return (
    <div className="min-h-screen bg-navy-50/40 text-navy-800">
      <header className="border-b border-slate-100 bg-white">
        <div className="mx-auto flex max-w-3xl items-start justify-between gap-4 px-4 py-6 sm:px-6">
          <div className="flex items-center gap-3">
            <Link to="/" className="shrink-0">
              <img src="/images/logo (1).png" alt="bw Superbakeshop" className="h-10 w-auto" />
            </Link>
            <div>
              <h1 className="text-2xl font-bold text-navy-800">My Profile</h1>
              <p className="text-sm text-slate-500">Manage your account details.</p>
            </div>
          </div>
          <Link
            to="/menu"
            className="text-sm font-medium text-slate-500 transition hover:text-brand-600"
          >
            ← Back to menu
          </Link>
        </div>
      </header>

      <main className="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6">
        {/* identity summary */}
        <section className="flex items-center gap-4 rounded-2xl bg-white p-6 shadow-sm">
          {user.avatar_url ? (
            <img
              src={user.avatar_url}
              alt={user.name}
              className="h-16 w-16 rounded-full object-cover ring-2 ring-brand-500/20"
            />
          ) : (
            <span className="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-navy-700 to-navy-900 text-xl font-bold text-white ring-2 ring-brand-500/20">
              {initials}
            </span>
          )}
          <div className="min-w-0">
            <p className="truncate text-lg font-bold text-navy-800">{user.name}</p>
            <p className="truncate text-sm text-slate-500">{user.email}</p>
          </div>
        </section>

        {/* account details */}
        <section className="rounded-2xl bg-white p-6 shadow-sm">
          <h2 className="text-lg font-bold text-navy-800">Account details</h2>
          <p className="mt-1 text-sm text-slate-500">Update your name and contact number.</p>

          {infoError && (
            <div className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {infoError}
            </div>
          )}
          {infoSuccess && (
            <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
              {infoSuccess}
            </div>
          )}

          <form onSubmit={handleInfoSubmit} noValidate className="mt-4 space-y-4">
            <Field label="Email">
              <input
                type="email"
                value={user.email}
                disabled
                className="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-500"
              />
              <p className="mt-1 text-xs text-slate-400">
                Email is tied to your sign-in and can&apos;t be changed here.
              </p>
            </Field>

            <Field label="Name">
              <input
                type="text"
                required
                minLength={2}
                value={name}
                onChange={(e) => {
                  setName(e.target.value)
                  setInfoSuccess('')
                }}
                placeholder="Jane Baker"
                className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
              />
            </Field>

            <Field label="Contact number">
              <input
                type="tel"
                required
                inputMode="tel"
                autoComplete="tel"
                maxLength={20}
                value={contact}
                onChange={(e) => {
                  setContact(sanitizePhone(e.target.value))
                  setInfoSuccess('')
                }}
                placeholder="e.g. 0917 123 4567"
                className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
              />
            </Field>

            <button
              type="submit"
              disabled={savingInfo}
              className="rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 focus:ring-2 focus:ring-brand-500/40 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {savingInfo ? 'Saving…' : 'Save changes'}
            </button>
          </form>
        </section>

        {/* password */}
        <section className="rounded-2xl bg-white p-6 shadow-sm">
          <h2 className="text-lg font-bold text-navy-800">Change password</h2>
          <p className="mt-1 text-sm text-slate-500">
            Set a new password for signing in with email.
          </p>

          {pwError && (
            <div className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {pwError}
            </div>
          )}
          {pwSuccess && (
            <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
              {pwSuccess}
            </div>
          )}

          <form onSubmit={handlePasswordSubmit} noValidate className="mt-4 space-y-4">
            <Field label="New password">
              <div className="relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  required
                  minLength={6}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="At least 6 characters"
                  className="w-full rounded-lg border border-slate-300 py-2.5 pl-3 pr-10 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((s) => !s)}
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                >
                  {showPassword ? <EyeOffIcon className="h-5 w-5" /> : <EyeIcon className="h-5 w-5" />}
                </button>
              </div>
            </Field>

            <Field label="Confirm new password">
              <input
                type={showPassword ? 'text' : 'password'}
                required
                value={passwordConfirm}
                onChange={(e) => setPasswordConfirm(e.target.value)}
                placeholder="Re-enter your new password"
                className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
              />
            </Field>

            <button
              type="submit"
              disabled={savingPw}
              className="rounded-lg bg-gradient-to-r from-navy-700 to-navy-800 px-6 py-2.5 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-navy-800/30 transition hover:from-navy-800 hover:to-navy-900 focus:ring-2 focus:ring-navy-700/40 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {savingPw ? 'Updating…' : 'Update password'}
            </button>
          </form>
        </section>
      </main>
    </div>
  )
}

function Field({ label, children }) {
  return (
    <div>
      <label className="mb-1 block text-sm font-medium text-navy-800">{label}</label>
      {children}
    </div>
  )
}
