import { useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import BrandPanel from '../components/BrandPanel'
import { sanitizePhone, isValidPhone } from '../lib/phone'

export default function CompleteProfile() {
  const { user, loading, updateContactNumber } = useAuth()
  const navigate = useNavigate()

  const [contact, setContact] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-navy-900 text-white">
        Loading…
      </div>
    )
  }

  // Must be signed in to be here; if a number already exists, nothing to do.
  if (!user) return <Navigate to="/login" replace />
  if (user.contact_number) return <Navigate to="/dashboard" replace />

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    if (!isValidPhone(contact)) {
      setError('Please enter a valid contact number.')
      return
    }
    setSubmitting(true)
    try {
      await updateContactNumber(contact.trim())
      navigate('/dashboard')
    } catch (err) {
      setError(err.message || 'Unable to save your number. Please try again.')
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
                📞
              </div>
              <h2 className="mt-4 text-2xl font-bold text-navy-800">One last step</h2>
              <p className="mt-1 text-sm text-slate-500">
                Hi {user.name}! Please add your contact number <br className="hidden sm:block" />
                so we can reach you about your orders.
              </p>
            </div>

            {error && (
              <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} noValidate className="space-y-4">
              <div>
                <label className="mb-1 block text-sm font-medium text-navy-800">Contact number</label>
                <input
                  type="tel"
                  required
                  inputMode="tel"
                  autoComplete="tel"
                  maxLength={20}
                  value={contact}
                  onChange={(e) => setContact(sanitizePhone(e.target.value))}
                  placeholder="e.g. 0917 123 4567"
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </div>

              <button
                type="submit"
                disabled={submitting}
                className="w-full rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 focus:ring-2 focus:ring-brand-500/40 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {submitting ? 'Saving…' : 'Continue'}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  )
}
