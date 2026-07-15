import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import Reveal from '../components/Reveal'
import { DEFAULT_CONTENT, getCachedContent, getSiteContent } from '../lib/content'
import { uploadResume, submitApplication } from '../lib/careers'
import { useSeo } from '../lib/seo'

// Careers / "Join our team" page. Content is editable from the HR admin panel
// (Careers section) and stored in site_content.careers.

export default function Careers({ content: controlledContent, preview = false }) {
  useSeo('/careers', !preview)
  const [content, setContent] = useState(getCachedContent)

  useEffect(() => {
    if (!controlledContent) getSiteContent().then(setContent)
  }, [controlledContent])

  const resolved = controlledContent || content
  const cr = resolved.careers || DEFAULT_CONTENT.careers
  const email = cr.email || DEFAULT_CONTENT.careers.email

  // Application modal state (null = closed, string = job title being applied for)
  const [applyJob, setApplyJob] = useState(null)

  return (
    <div className="min-h-screen bg-white text-navy-800">
      <CareersHeader />
      <Hero hero={cr.hero} />
      <Perks perks={cr.perks || []} />
      <Openings jobs={cr.jobs || []} email={email} onApply={preview ? null : setApplyJob} />
      <Culture culture={cr.culture} />
      <CTA />
      <Footer />
      {applyJob && <ApplicationModal position={applyJob} onClose={() => setApplyJob(null)} />}
    </div>
  )
}

function CareersHeader() {
  return (
    <header className="sticky top-0 z-50 border-b border-slate-100 bg-white">
      <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <Link to="/" className="flex min-w-0 items-center">
          <img src="/favicon-192x192.png" alt="bw Superbakeshop" className="h-16 w-auto shrink-0 sm:h-20" />
        </Link>
        <div className="flex items-center gap-4">
          <Link
            to="/"
            className="text-sm font-medium text-navy-700 transition hover:text-brand-600"
          >
            &larr; Back to home
          </Link>
          <Link
            to="/careers/openings"
            className="hidden rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 sm:block"
          >
            View Openings
          </Link>
        </div>
      </div>
    </header>
  )
}

function Hero({ hero }) {
  const h = hero || DEFAULT_CONTENT.careers.hero
  return (
    <section className="relative overflow-hidden bg-navy-900">
      <img
        src={h.image || '/images/bakery-team.jpg'}
        alt=""
        aria-hidden="true"
        loading="lazy"
        decoding="async"
        className="absolute inset-0 h-full w-full object-cover"
      />
      <div className="absolute inset-0 bg-gradient-to-b from-navy-900/85 via-navy-900/80 to-navy-900/90" />
      <div className="relative mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
        <h1 className="mt-5 text-4xl font-bold leading-tight text-white sm:text-5xl">
          {h.title || 'Bake your career with us'}
        </h1>
        <p className="mx-auto mt-5 max-w-xl text-base text-navy-50/80">
          {h.subtitle}
        </p>
        <div className="mt-8 flex flex-wrap justify-center gap-3">
          <Link
            to="/careers/openings"
            className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
          >
            See open roles
          </Link>
          <a
            href="#culture"
            className="rounded-full border border-white/30 px-7 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
          >
            Our culture
          </a>
        </div>
      </div>
    </section>
  )
}

function Perks({ perks }) {
  return (
    <section className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <Reveal>
        <SectionHeading
          eyebrow="Why join us"
          title="More than just a job"
          subtitle="We take care of our people the same way we care about our customers."
        />
      </Reveal>
      <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {perks.map((p, i) => (
          <Reveal key={p.title} delay={(i % 3) * 100}>
            <div className="h-full rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition hover:shadow-lg">
              <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-2xl">
                {p.icon}
              </span>
              <h3 className="mt-4 text-base font-semibold text-navy-800">{p.title}</h3>
              <p className="mt-2 text-sm text-slate-500">{p.text}</p>
            </div>
          </Reveal>
        ))}
      </div>
    </section>
  )
}

function Openings({ jobs, email, onApply }) {
  return (
    <section id="openings" className="bg-navy-50/60 py-16">
      <div className="mx-auto max-w-4xl px-4 sm:px-6">
        <Reveal>
          <SectionHeading
            eyebrow="Open positions"
            title="Find your place"
            subtitle="Browse our current openings and apply in just one click."
          />
        </Reveal>
        <div className="mt-10 space-y-3">
          {jobs.map((job, i) => (
            <Reveal key={`${job.title}-${i}`} delay={(i % 4) * 60}>
              <div className="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-sm transition hover:shadow-md sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h3 className="text-base font-semibold text-navy-800">{job.title}</h3>
                  <div className="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500">
                    <span>{job.dept}</span>
                    <span>{job.type}</span>
                    <span>{job.location}</span>
                  </div>
                </div>
                <button
                  onClick={() => onApply?.(job.title)}
                  className="shrink-0 rounded-full bg-navy-800 px-6 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-brand-600"
                >
                  Apply
                </button>
              </div>
            </Reveal>
          ))}
        </div>
        {jobs.length === 0 && (
          <p className="mt-10 text-center text-sm text-slate-500">
            No open positions right now. Check back soon!
          </p>
        )}
        <p className="mt-8 text-center text-sm text-slate-500">
          Don&apos;t see a role that fits? Send your resume to{' '}
          <a
            href={`mailto:${email}`}
            className="font-semibold text-brand-600 hover:text-brand-500"
          >
            {email}
          </a>
        </p>
      </div>
    </section>
  )
}

function Culture({ culture }) {
  const c = culture || DEFAULT_CONTENT.careers.culture
  return (
    <section id="culture" className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <div className="grid items-center gap-10 lg:grid-cols-2">
        <Reveal direction="left" className="relative">
          <img
            src={c.image}
            alt="Bakers working together in the kitchen"
            loading="lazy"
            decoding="async"
            className="h-80 w-full rounded-3xl object-cover shadow-xl"
          />
          <div className="absolute -bottom-5 -right-3 hidden rounded-2xl bg-navy-800 px-5 py-4 text-white shadow-xl sm:block">
            <p className="font-script text-2xl text-brand-400">{c.stat || '500+'}</p>
            <p className="text-xs text-navy-50/80">{c.statLabel || 'team members'}</p>
          </div>
        </Reveal>
        <Reveal direction="right" delay={120}>
          <span className="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">
            {c.eyebrow || 'Our culture'}
          </span>
          <h2 className="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">
            {c.title}
          </h2>
          <p className="mt-4 text-sm leading-relaxed text-slate-600">
            {c.text}
          </p>
          <ul className="mt-5 space-y-2 text-sm text-slate-600">
            {(c.highlights || []).map((item) => (
              <li key={item} className="flex items-center gap-2">
                <span className="text-brand-500">&#10003;</span>
                {item}
              </li>
            ))}
          </ul>
        </Reveal>
      </div>
    </section>
  )
}

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
      if (file) {
        cvPath = await uploadResume(file)
      }
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

function CTA() {
  return (
    <Reveal as="section" className="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
      <div className="rounded-3xl bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-12 text-center text-white shadow-xl sm:px-12">
        <h2 className="text-2xl font-bold sm:text-3xl">Ready to rise with us?</h2>
        <p className="mx-auto mt-2 max-w-md text-sm text-white/90">
          Take the first step toward a sweet new career. We can&apos;t wait to
          meet you.
        </p>
        <Link
          to="/careers/openings"
          className="mt-6 inline-block rounded-full bg-white px-7 py-3 text-sm font-semibold text-brand-600 shadow-md transition hover:bg-navy-50"
        >
          Browse open roles
        </Link>
      </div>
    </Reveal>
  )
}

function Footer() {
  return (
    <footer className="bg-navy-900 text-navy-50/70">
      <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm sm:flex-row sm:px-6">
        <div className="flex items-center gap-2">
          <img src="/images/logo (1).png" alt="bw Superbakeshop" className="h-10 w-auto" />
          <span className="font-brand text-xl font-bold text-white">Superbakeshop</span>
        </div>
        <p className="text-xs">&copy; {2026} BW Superbakeshop</p>
        <Link to="/" className="text-xs font-medium transition hover:text-brand-400">
          &larr; Back to home
        </Link>
      </div>
    </footer>
  )
}

function SectionHeading({ eyebrow, title, subtitle }) {
  return (
    <div className="mx-auto max-w-2xl text-center">
      {eyebrow && (
        <span className="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">
          {eyebrow}
        </span>
      )}
      <h2 className="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">{title}</h2>
      {subtitle && <p className="mt-3 text-sm text-slate-500">{subtitle}</p>}
    </div>
  )
}
