import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import BrandPanel from '../components/BrandPanel'
import BakeryBackdrop from '../components/BakeryBackdrop'
import GoogleIcon from '../components/GoogleIcon'
import { EyeIcon, EyeOffIcon } from '../components/EyeIcons'
import { sanitizePhone, isValidPhone } from '../lib/phone'

export default function Register() {
  const { register, loginWithGoogle } = useAuth()
  const navigate = useNavigate()

  const [form, setForm] = useState({
    name: '',
    email: '',
    contact_number: '',
    password: '',
    password_confirmation: '',
  })
  const [fieldErrors, setFieldErrors] = useState({})
  const [formError, setFormError] = useState('')
  const [success, setSuccess] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [showPassword, setShowPassword] = useState(false)
  const [showConfirm, setShowConfirm] = useState(false)

  const update = (field) => (e) => {
    setForm((prev) => ({ ...prev, [field]: e.target.value }))
    setFieldErrors((prev) => ({ ...prev, [field]: '' })) // clear the field's error as the user fixes it
  }

  // Phone numbers only: keep digits plus the few valid symbols (+, space, -, (), ).
  const updateContact = (e) => {
    setForm((prev) => ({ ...prev, contact_number: sanitizePhone(e.target.value) }))
    setFieldErrors((prev) => ({ ...prev, contact_number: '' }))
  }

  // Client-side checks so users get clear messages below the offending field.
  const validate = () => {
    const errs = {}
    if (form.name.trim().length < 2) errs.name = 'Please enter your full name.'
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) errs.email = 'Please enter a valid email address.'
    if (!isValidPhone(form.contact_number)) errs.contact_number = 'Please enter a valid contact number.'
    if (form.password.length < 6) errs.password = 'Password should be at least 6 characters.'
    if (form.password !== form.password_confirmation) errs.password_confirmation = 'Passwords do not match.'
    return errs
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setFormError('')

    const errs = validate()
    setFieldErrors(errs)
    if (Object.keys(errs).length > 0) return

    setSubmitting(true)
    try {
      await register(form)
      setSuccess('Account created successfully! Redirecting you to sign in…')
      // Brief pause so the user sees the confirmation, then go to login.
      setTimeout(() => navigate('/login', { state: { registered: true } }), 1800)
    } catch (err) {
      setFormError(err.message || 'Unable to register. Please try again.')
      setSubmitting(false)
    }
  }

  const handleGoogle = async () => {
    setFormError('')
    try {
      await loginWithGoogle()
    } catch (err) {
      setFormError(err.message || 'Unable to sign in with Google.')
    }
  }

  return (
    <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-navy-700 via-navy-800 to-navy-900 p-4 sm:p-6">
      <BakeryBackdrop />
      <div className="relative z-10 grid w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl lg:grid-cols-[1fr_1.1fr]">
        <BrandPanel />

        <div className="flex flex-col justify-center px-7 py-10 sm:px-12">
          <div className="mx-auto w-full max-w-sm">
            <div className="mb-6 flex flex-col items-center text-center">
              <div className="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-navy-700 to-navy-900 text-3xl shadow-lg ring-4 ring-brand-500/20">
                🏪
              </div>
              <h2 className="mt-4 text-2xl font-bold text-navy-800">Create your account</h2>
              <p className="mt-1 text-sm text-slate-500">Join the Bakery Ordering System</p>
            </div>

            {formError && (
              <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {formError}
              </div>
            )}

            {success && (
              <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {success}
              </div>
            )}

            <form onSubmit={handleSubmit} noValidate className="space-y-4">
              <Field label="Name" error={fieldErrors.name}>
                <input
                  type="text"
                  required
                  minLength={2}
                  value={form.name}
                  onChange={update('name')}
                  placeholder="Jane Baker"
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </Field>

              <Field label="Email" error={fieldErrors.email}>
                <input
                  type="email"
                  required
                  value={form.email}
                  onChange={update('email')}
                  placeholder="you@example.com"
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </Field>

              <Field label="Contact number" error={fieldErrors.contact_number}>
                <input
                  type="tel"
                  required
                  inputMode="tel"
                  autoComplete="tel"
                  pattern="[\d+\-\s()]{7,}"
                  title="Enter a valid contact number (digits only)"
                  maxLength={20}
                  value={form.contact_number}
                  onChange={updateContact}
                  placeholder="e.g. 0917 123 4567"
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </Field>

              <Field label="Password" error={fieldErrors.password}>
                <div className="relative">
                  <input
                    type={showPassword ? 'text' : 'password'}
                    required
                    minLength={6}
                    value={form.password}
                    onChange={update('password')}
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

              <Field label="Confirm password" error={fieldErrors.password_confirmation}>
                <div className="relative">
                  <input
                    type={showConfirm ? 'text' : 'password'}
                    required
                    value={form.password_confirmation}
                    onChange={update('password_confirmation')}
                    placeholder="Re-enter your password"
                    className="w-full rounded-lg border border-slate-300 py-2.5 pl-3 pr-10 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                  />
                  <button
                    type="button"
                    onClick={() => setShowConfirm((s) => !s)}
                    aria-label={showConfirm ? 'Hide password' : 'Show password'}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                  >
                    {showConfirm ? <EyeOffIcon className="h-5 w-5" /> : <EyeIcon className="h-5 w-5" />}
                  </button>
                </div>
              </Field>

              <button
                type="submit"
                disabled={submitting}
                className="w-full rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 focus:ring-2 focus:ring-brand-500/40 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {submitting ? 'Creating account…' : 'Create account'}
              </button>
            </form>

            <div className="my-5 flex items-center gap-3">
              <span className="h-px flex-1 bg-slate-200" />
              <span className="text-xs font-medium uppercase tracking-wide text-slate-400">or</span>
              <span className="h-px flex-1 bg-slate-200" />
            </div>

            <button
              type="button"
              onClick={handleGoogle}
              disabled={submitting}
              className="flex w-full items-center justify-center gap-3 rounded-lg border border-slate-300 bg-white py-2.5 text-sm font-semibold text-navy-800 transition hover:bg-slate-50 focus:ring-2 focus:ring-brand-500/30 disabled:cursor-not-allowed disabled:opacity-60"
            >
              <GoogleIcon className="h-5 w-5" />
              Continue with Google
            </button>

            <p className="mt-6 text-center text-xs text-slate-400">
              Already have an account?{' '}
              <Link to="/login" className="font-medium text-brand-600 hover:text-brand-500">
                Sign in
              </Link>
            </p>
          </div>
        </div>
      </div>
    </div>
  )
}

function Field({ label, error, children }) {
  return (
    <div>
      <label className="mb-1 block text-sm font-medium text-navy-800">{label}</label>
      {children}
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  )
}
