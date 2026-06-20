import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { DEFAULT_CONTENT, getCachedContent, getSiteContent } from '../lib/content'
import { uploadResume, submitApplication } from '../lib/careers'
import { useSeo } from '../lib/seo'

// Full-page job listings with split layout: job list on the left, detail panel on the right.

export default function Openings() {
  useSeo('/careers/openings')
  const [content, setContent] = useState(getCachedContent)
  const [search, setSearch] = useState('')
  const [selDepts, setSelDepts] = useState([])
  const [selLocations, setSelLocations] = useState([])
  const [selTypes, setSelTypes] = useState([])
  const [selectedIdx, setSelectedIdx] = useState(null)
  const [applyJob, setApplyJob] = useState(null)

  useEffect(() => {
    getSiteContent().then(setContent)
  }, [])

  const cr = content.careers || DEFAULT_CONTENT.careers
  const email = cr.email || DEFAULT_CONTENT.careers.email
  const allJobs = cr.jobs || []

  // Filter options from CMS (managed by HR in Filters tab), falling back to values derived from jobs
  const depts = cr.departments || Array.from(new Set(allJobs.map((j) => j.dept).filter(Boolean)))
  const locations = cr.locations || Array.from(new Set(allJobs.map((j) => j.location).filter(Boolean)))
  const jobTypes = cr.jobTypes || Array.from(new Set(allJobs.map((j) => j.type).filter(Boolean)))

  const filtered = allJobs.filter((job) => {
    if (selDepts.length > 0 && !selDepts.includes(job.dept)) return false
    if (selLocations.length > 0 && !selLocations.includes(job.location)) return false
    if (selTypes.length > 0 && !selTypes.includes(job.type)) return false
    if (search) {
      const q = search.toLowerCase()
      return (
        (job.title || '').toLowerCase().includes(q) ||
        (job.dept || '').toLowerCase().includes(q) ||
        (job.location || '').toLowerCase().includes(q) ||
        (job.type || '').toLowerCase().includes(q)
      )
    }
    return true
  })

  const hasFilters = selDepts.length > 0 || selLocations.length > 0 || selTypes.length > 0

  const selectedJob = selectedIdx !== null ? filtered[selectedIdx] : null

  return (
    <div className="flex min-h-screen flex-col bg-slate-50 text-navy-800">
      {/* Header */}
      <header className="sticky top-0 z-50 border-b border-slate-100 bg-white">
        <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
          <Link to="/" className="flex min-w-0 items-center gap-2">
            <img src="/images/logo (1).png" alt="bw Superbakeshop" className="h-9 w-auto shrink-0 sm:h-11" />
            <span className="truncate font-brand text-lg font-bold text-brand-500 sm:text-2xl">
              Superbakeshop
            </span>
          </Link>
          <div className="flex items-center gap-4">
            <Link
              to="/careers"
              className="text-sm font-medium text-navy-700 transition hover:text-brand-600"
            >
              &larr; Back to Careers
            </Link>
            
          </div>
        </div>
      </header>

      {/* Hero banner */}
      <section className="relative bg-navy-900">
        <div className="absolute inset-0 bg-[url('/images/bakery-team.jpg')] bg-cover bg-center" />
        <div className="absolute inset-0 bg-navy-900/80" />
        <div className="relative mx-auto max-w-4xl px-4 py-14 text-center sm:px-6">
          <span className="text-xs font-semibold uppercase tracking-[0.3em] text-brand-400">
            Open positions
          </span>
          <h1 className="mt-3 text-3xl font-bold text-white sm:text-4xl">
            Find your place at bw Superbakeshop
          </h1>
          <p className="mx-auto mt-4 max-w-xl text-sm text-navy-50/70">
            Browse all our current openings. Filter by department or search for your ideal role.
          </p>

          {/* Search bar */}
          <div className="mx-auto mt-8 max-w-md">
            <div className="relative">
              <SearchIcon className="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                type="text"
                placeholder="Search by title, department, or location..."
                value={search}
                onChange={(e) => { setSearch(e.target.value); setSelectedIdx(null) }}
                className="w-full rounded-full border border-white/20 bg-white/10 py-3 pl-10 pr-4 text-sm text-white placeholder-navy-50/50 outline-none backdrop-blur transition focus:border-brand-400 focus:ring-2 focus:ring-brand-500/30"
              />
            </div>
          </div>
        </div>
      </section>

      {/* Filter dropdowns */}
      <div className="border-b border-slate-200 bg-white px-4 sm:px-6">
        <div className="flex flex-wrap items-center gap-3 py-3">
          <CheckboxDropdown
            label="Department"
            options={depts}
            selected={selDepts}
            onChange={(v) => { setSelDepts(v); setSelectedIdx(null) }}
          />
          <CheckboxDropdown
            label="Location"
            options={locations}
            selected={selLocations}
            onChange={(v) => { setSelLocations(v); setSelectedIdx(null) }}
          />
          <CheckboxDropdown
            label="Job Type"
            options={jobTypes}
            selected={selTypes}
            onChange={(v) => { setSelTypes(v); setSelectedIdx(null) }}
          />
          {hasFilters && (
            <button
              onClick={() => { setSelDepts([]); setSelLocations([]); setSelTypes([]); setSelectedIdx(null) }}
              className="text-xs font-semibold text-brand-600 hover:text-brand-500"
            >
              Clear all
            </button>
          )}
        </div>
      </div>

      {/* Main split layout */}
      <div className="flex min-h-0 flex-1">
        {/* Left: Job list */}
        <div className={`w-full shrink-0 overflow-y-auto border-r border-slate-200 bg-white sm:w-[45%] lg:w-[48%] ${selectedJob ? 'hidden sm:block' : ''}`}>
          <div className="px-4 py-3">
            <p className="text-xs text-slate-400">
              {filtered.length === allJobs.length
                ? `${allJobs.length} positions`
                : `${filtered.length} of ${allJobs.length}`}
            </p>
          </div>
          <div className="divide-y divide-slate-100">
            {filtered.map((job, i) => {
              const active = selectedIdx === i
              return (
                <button
                  key={`${job.title}-${i}`}
                  onClick={() => setSelectedIdx(i)}
                  className={`block w-full px-4 py-4 text-left transition ${
                    active
                      ? 'border-l-3 border-l-brand-500 bg-brand-50/50'
                      : 'hover:bg-slate-50'
                  }`}
                >
                  <h3 className={`text-sm font-semibold ${active ? 'text-brand-600' : 'text-navy-800'}`}>
                    {job.title}
                  </h3>
                  <div className="mt-1.5 flex flex-wrap gap-1.5">
                    {job.dept && (
                      <span className="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-[0.65rem] font-medium text-brand-700">
                        <DeptIcon className="h-2.5 w-2.5" />
                        {job.dept}
                      </span>
                    )}
                    {job.type && (
                      <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[0.65rem] font-medium text-blue-700">
                        <ClockIcon className="h-2.5 w-2.5" />
                        {job.type}
                      </span>
                    )}
                    {job.location && (
                      <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[0.65rem] font-medium text-slate-600">
                        <PinIcon className="h-2.5 w-2.5" />
                        {job.location}
                      </span>
                    )}
                  </div>
                  {job.requirements?.length > 0 && (
                    <p className="mt-2 line-clamp-2 text-xs text-slate-500">
                      {job.requirements.slice(0, 2).join(' · ')}
                    </p>
                  )}
                </button>
              )
            })}
          </div>

          {filtered.length === 0 && (
            <div className="px-4 py-16 text-center">
              <p className="text-sm font-semibold text-slate-400">No positions found</p>
              <p className="mt-1 text-xs text-slate-400">
                {search ? 'Try a different search term.' : 'Check back soon.'}
              </p>
              {(search || hasFilters) && (
                <button
                  onClick={() => { setSearch(''); setSelDepts([]); setSelLocations([]); setSelTypes([]) }}
                  className="mt-3 text-xs font-semibold text-brand-600 hover:text-brand-500"
                >
                  Clear filters
                </button>
              )}
            </div>
          )}

          {/* Bottom email CTA */}
          <div className="border-t border-slate-100 px-4 py-4">
            <p className="text-xs text-slate-500">
              Don&apos;t see a fit?{' '}
              <a href={`mailto:${email}`} className="font-semibold text-brand-600 hover:text-brand-500">
                {email}
              </a>
            </p>
          </div>
        </div>

        {/* Right: Job detail */}
        <div className={`min-w-0 flex-1 overflow-y-auto ${selectedJob ? '' : 'hidden sm:block'}`}>
          {selectedJob ? (
            <div className="mx-auto max-w-2xl px-6 py-8 lg:px-10">
              {/* Mobile back button */}
              <button
                onClick={() => setSelectedIdx(null)}
                className="mb-4 text-xs font-semibold text-brand-600 hover:text-brand-500 sm:hidden"
              >
                &larr; Back to listings
              </button>

              <div>
                <h2 className="text-2xl font-bold text-navy-800">{selectedJob.title}</h2>
                <div className="mt-3 flex flex-wrap gap-2">
                  {selectedJob.dept && (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700">
                      <DeptIcon className="h-3 w-3" />
                      {selectedJob.dept}
                    </span>
                  )}
                  {selectedJob.type && (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                      <ClockIcon className="h-3 w-3" />
                      {selectedJob.type}
                    </span>
                  )}
                  {selectedJob.location && (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                      <PinIcon className="h-3 w-3" />
                      {selectedJob.location}
                    </span>
                  )}
                </div>
              </div>

              {/* Description */}
              {selectedJob.description && (
                <div className="mt-8">
                  <h3 className="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-400">
                    <DescIcon className="h-4 w-4" />
                    Job Description
                  </h3>
                  <p className="mt-3 text-sm leading-relaxed text-slate-600">
                    {selectedJob.description}
                  </p>
                </div>
              )}

              {/* Requirements */}
              {selectedJob.requirements?.length > 0 && (
                <div className="mt-8">
                  <h3 className="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-400">
                    <CheckListIcon className="h-4 w-4" />
                    Requirements
                  </h3>
                  <ul className="mt-3 space-y-2">
                    {selectedJob.requirements.map((req, i) => (
                      <li key={i} className="flex items-start gap-2 text-sm text-slate-600">
                        <span className="mt-0.5 shrink-0 text-brand-500">&#10003;</span>
                        {req}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {/* No description/requirements fallback */}
              {!selectedJob.description && !(selectedJob.requirements?.length > 0) && (
                <div className="mt-8 rounded-xl border-2 border-dashed border-slate-200 py-12 text-center">
                  <p className="text-sm text-slate-400">
                    No additional details for this position yet.
                  </p>
                  <p className="mt-1 text-xs text-slate-400">
                    Apply now or contact us for more information.
                  </p>
                </div>
              )}

              {/* Bottom apply CTA */}
              <div className="mt-10 rounded-2xl bg-navy-900 p-6 text-center">
                <h4 className="text-lg font-bold text-white">Interested in this role?</h4>
                <p className="mt-1 text-sm text-navy-50/70">
                  Submit your application and we&apos;ll get back to you.
                </p>
                <button
                  onClick={() => setApplyJob(selectedJob.title)}
                  className="mt-4 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
                >
                  Apply for {selectedJob.title}
                </button>
              </div>
            </div>
          ) : (
            <div className="flex h-full items-center justify-center p-8">
              <div className="text-center">
                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100">
                  <BriefcaseIcon className="h-7 w-7 text-slate-400" />
                </div>
                <p className="mt-4 text-sm font-medium text-slate-500">Select a position</p>
                <p className="mt-1 text-xs text-slate-400">
                  Click on a job listing to view the full description and requirements.
                </p>
              </div>
            </div>
          )}
        </div>
      </div>

      {applyJob && <ApplicationModal position={applyJob} onClose={() => setApplyJob(null)} />}
    </div>
  )
}

/* Application modal */
function ApplicationModal({ position, onClose }) {
  const fileRef = useRef(null)
  const [form, setForm] = useState({ name: '', email: '', phone: '', position })
  const [file, setFile] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [done, setDone] = useState(false)
  const [error, setError] = useState('')

  const set = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }))

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!form.name || !form.email || !form.position) return
    setSubmitting(true)
    setError('')
    try {
      let cvPath = null
      if (file) cvPath = await uploadResume(file)
      await submitApplication({
        name: form.name,
        email: form.email,
        phone: form.phone || null,
        position: form.position,
        cvPath,
      })
      setDone(true)
    } catch (err) {
      setError(err.message || 'Something went wrong. Please try again.')
    } finally {
      setSubmitting(false)
    }
  }

  const handleFile = (e) => {
    const f = e.target.files?.[0]
    if (!f) return
    if (f.size > 5 * 1024 * 1024) {
      setError('File is too large. Maximum size is 5 MB.')
      if (fileRef.current) fileRef.current.value = ''
      return
    }
    setFile(f)
    setError('')
  }

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <div
        className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl sm:p-8"
        onClick={(e) => e.stopPropagation()}
      >
        {done ? (
          <div className="py-8 text-center">
            <span className="text-4xl">&#10003;</span>
            <h3 className="mt-3 text-xl font-bold text-navy-800">Application submitted!</h3>
            <p className="mt-2 text-sm text-slate-500">
              Thank you for applying. Our HR team will review your application and get back to you.
            </p>
            <button
              onClick={onClose}
              className="mt-6 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-brand-600 hover:to-brand-600"
            >
              Close
            </button>
          </div>
        ) : (
          <>
            <div className="mb-5 flex items-center justify-between">
              <h3 className="text-lg font-bold text-navy-800">Apply for {position}</h3>
              <button onClick={onClose} className="text-slate-400 transition hover:text-slate-600">
                &#10005;
              </button>
            </div>
            <form onSubmit={handleSubmit} className="space-y-4">
              <label className="block">
                <span className="mb-1 block text-xs font-medium text-slate-500">
                  Full name <span className="text-red-500">*</span>
                </span>
                <input
                  required
                  value={form.name}
                  onChange={set('name')}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </label>
              <label className="block">
                <span className="mb-1 block text-xs font-medium text-slate-500">
                  Email <span className="text-red-500">*</span>
                </span>
                <input
                  type="email"
                  required
                  value={form.email}
                  onChange={set('email')}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </label>
              <label className="block">
                <span className="mb-1 block text-xs font-medium text-slate-500">Phone</span>
                <input
                  type="tel"
                  value={form.phone}
                  onChange={set('phone')}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </label>
              <label className="block">
                <span className="mb-1 block text-xs font-medium text-slate-500">
                  Position <span className="text-red-500">*</span>
                </span>
                <input
                  required
                  value={form.position}
                  onChange={set('position')}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </label>
              <div>
                <span className="mb-1 block text-xs font-medium text-slate-500">
                  Upload CV / Resume (PDF, DOC, DOCX &mdash; max 5 MB)
                </span>
                <input
                  ref={fileRef}
                  type="file"
                  accept=".pdf,.doc,.docx"
                  onChange={handleFile}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-navy-800 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-white hover:file:bg-brand-600"
                />
                {file && (
                  <p className="mt-1 text-xs text-slate-500">{file.name} ({(file.size / 1024).toFixed(0)} KB)</p>
                )}
              </div>
              {error && <p className="text-sm text-red-600">{error}</p>}
              <button
                type="submit"
                disabled={submitting}
                className="w-full rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:opacity-60"
              >
                {submitting ? 'Submitting...' : 'Submit application'}
              </button>
            </form>
          </>
        )}
      </div>
    </div>
  )
}

/* Checkbox multi-select dropdown */
function CheckboxDropdown({ label, options, selected, onChange }) {
  const [open, setOpen] = useState(false)
  const ref = useRef(null)

  useEffect(() => {
    const handler = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false) }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [])

  const toggle = (val) => {
    onChange(selected.includes(val) ? selected.filter((v) => v !== val) : [...selected, val])
  }

  const display = selected.length === 0
    ? `All ${label}s`
    : selected.length === 1
      ? selected[0]
      : `${selected.length} selected`

  return (
    <div className="relative" ref={ref}>
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className={`flex items-center gap-2 rounded-lg border bg-white py-2 pl-3 pr-3 text-sm transition ${
          selected.length > 0
            ? 'border-brand-500 text-brand-700'
            : 'border-slate-300 text-navy-800'
        } hover:border-brand-400`}
      >
        <span>{display}</span>
        <ChevronIcon className={`h-3.5 w-3.5 text-slate-400 transition ${open ? 'rotate-180' : ''}`} />
      </button>
      {open && (
        <div className="absolute left-0 top-full z-50 mt-1 min-w-[180px] rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
          {options.map((opt) => (
            <label
              key={opt}
              className="flex cursor-pointer items-center gap-2.5 px-3 py-2 text-sm text-navy-800 transition hover:bg-slate-50"
            >
              <input
                type="checkbox"
                checked={selected.includes(opt)}
                onChange={() => toggle(opt)}
                className="h-3.5 w-3.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30"
              />
              {opt}
            </label>
          ))}
          {selected.length > 0 && (
            <button
              type="button"
              onClick={() => onChange([])}
              className="mt-1 w-full border-t border-slate-100 px-3 py-2 text-left text-xs font-semibold text-brand-600 hover:bg-slate-50"
            >
              Clear
            </button>
          )}
        </div>
      )}
    </div>
  )
}

/* Icons */
function iconBase(p) {
  return {
    viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
    strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round',
    'aria-hidden': true, ...p,
  }
}
function ChevronIcon(p) {
  return <svg {...iconBase(p)}><polyline points="6 9 12 15 18 9" /></svg>
}
function SearchIcon(p) {
  return <svg {...iconBase(p)}><circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" /></svg>
}
function DeptIcon(p) {
  return <svg {...iconBase(p)}><path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z" /><circle cx="7" cy="7" r="1" /></svg>
}
function ClockIcon(p) {
  return <svg {...iconBase(p)}><circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" /></svg>
}
function PinIcon(p) {
  return <svg {...iconBase(p)}><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10" r="3" /></svg>
}
function BriefcaseIcon(p) {
  return <svg {...iconBase(p)}><rect x="2" y="7" width="20" height="14" rx="2" /><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /><path d="M2 13h20" /></svg>
}
function DescIcon(p) {
  return <svg {...iconBase(p)}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /><polyline points="10 9 9 9 8 9" /></svg>
}
function CheckListIcon(p) {
  return <svg {...iconBase(p)}><path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" /></svg>
}
