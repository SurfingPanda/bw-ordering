import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import BrandPanel from '../components/BrandPanel'

export default function Register() {
  const { register } = useAuth()
  const navigate = useNavigate()

  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  })
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const update = (field) => (e) =>
    setForm((prev) => ({ ...prev, [field]: e.target.value }))

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await register(form)
      navigate('/dashboard')
    } catch (err) {
      const errors = err.response?.data?.errors
      const message =
        (errors && Object.values(errors)[0]?.[0]) ||
        err.response?.data?.message ||
        'Unable to register. Please try again.'
      setError(message)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-navy-900 p-4 sm:p-6">
      <div className="grid w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl lg:grid-cols-[1fr_1.1fr]">
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

            {error && (
              <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
              <Field label="Name">
                <input
                  type="text"
                  required
                  value={form.name}
                  onChange={update('name')}
                  placeholder="Jane Baker"
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </Field>

              <Field label="Email">
                <input
                  type="email"
                  required
                  value={form.email}
                  onChange={update('email')}
                  placeholder="you@example.com"
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </Field>

              <Field label="Password">
                <input
                  type="password"
                  required
                  value={form.password}
                  onChange={update('password')}
                  placeholder="At least 8 characters"
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </Field>

              <Field label="Confirm password">
                <input
                  type="password"
                  required
                  value={form.password_confirmation}
                  onChange={update('password_confirmation')}
                  placeholder="Re-enter your password"
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </Field>

              <button
                type="submit"
                disabled={submitting}
                className="w-full rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 focus:ring-2 focus:ring-brand-500/40 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {submitting ? 'Creating account…' : 'Create account'}
              </button>
            </form>

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

function Field({ label, children }) {
  return (
    <div>
      <label className="mb-1 block text-sm font-medium text-navy-800">{label}</label>
      {children}
    </div>
  )
}
