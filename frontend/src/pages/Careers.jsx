import { Link } from 'react-router-dom'
import Reveal from '../components/Reveal'

// Careers / "Join our team" page, built with the navy + brand-orange theme.

const PERKS = [
  { icon: '🧡', title: 'Caring Culture', text: 'Work with a warm, supportive team that feels like family.' },
  { icon: '📈', title: 'Grow With Us', text: 'Clear career paths, training, and room to rise as we expand.' },
  { icon: '🍰', title: 'Tasty Perks', text: 'Free treats, staff discounts, and meals on every shift.' },
  { icon: '🏥', title: 'Health Benefits', text: 'HMO coverage, paid leave, and government-mandated benefits.' },
  { icon: '⏰', title: 'Flexible Shifts', text: 'Schedules that respect your time and life outside work.' },
  { icon: '🎉', title: 'Fun Events', text: 'Team outings, celebrations, and performance bonuses.' },
]

const JOBS = [
  { title: 'Head Baker', dept: 'Production', type: 'Full-time', location: 'Quezon City', },
  { title: 'Pastry Chef', dept: 'Production', type: 'Full-time', location: 'Makati' },
  { title: 'Store Crew', dept: 'Retail', type: 'Full-time', location: 'Multiple branches' },
  { title: 'Cashier', dept: 'Retail', type: 'Part-time', location: 'Pasig' },
  { title: 'Delivery Rider', dept: 'Logistics', type: 'Full-time', location: 'Metro Manila' },
  { title: 'Marketing Associate', dept: 'Corporate', type: 'Full-time', location: 'Quezon City (Hybrid)' },
]

const applyHref = (title) =>
  `mailto:careers@bwsuperbakeshop.com?subject=${encodeURIComponent(`Application: ${title}`)}`

export default function Careers() {
  return (
    <div className="min-h-screen bg-white text-navy-800">
      <CareersHeader />
      <Hero />
      <Perks />
      <Openings />
      <Culture />
      <CTA />
      <Footer />
    </div>
  )
}

function CareersHeader() {
  return (
    <header className="sticky top-0 z-50 border-b border-slate-100 bg-white">
      <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <Link to="/" className="flex min-w-0 items-center gap-2">
          <img src="/images/logo (1).png" alt="bw Superbakeshop" className="h-9 w-auto shrink-0 sm:h-11" />
          <span className="truncate font-brand text-lg font-bold text-brand-500 sm:text-2xl">
            Superbakeshop
          </span>
        </Link>
        <div className="flex items-center gap-4">
          <Link
            to="/"
            className="text-sm font-medium text-navy-700 transition hover:text-brand-600"
          >
            ← Back to home
          </Link>
          <a
            href="#openings"
            className="hidden rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 sm:block"
          >
            View Openings
          </a>
        </div>
      </div>
    </header>
  )
}

function Hero() {
  return (
    <section className="relative overflow-hidden bg-navy-900">
      <img
        src="/images/bakery-team.jpg"
        alt=""
        aria-hidden="true"
        className="absolute inset-0 h-full w-full object-cover"
      />
      <div className="absolute inset-0 bg-gradient-to-b from-navy-900/85 via-navy-900/80 to-navy-900/90" />
      <div className="relative mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
        
        <h1 className="mt-5 text-4xl font-bold leading-tight text-white sm:text-5xl">
          Bake your career with{' '}
          <span className="font-script font-normal text-brand-400">us</span>
        </h1>
        <p className="mx-auto mt-5 max-w-xl text-base text-navy-50/80">
          Join a passionate team that brings freshly baked happiness to families
          every day. Discover a place where your talent rises.
        </p>
        <div className="mt-8 flex flex-wrap justify-center gap-3">
          <a
            href="#openings"
            className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
          >
            See open roles
          </a>
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

function Perks() {
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
        {PERKS.map((p, i) => (
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

function Openings() {
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
          {JOBS.map((job, i) => (
            <Reveal key={job.title} delay={(i % 4) * 60}>
              <div className="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-sm transition hover:shadow-md sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h3 className="text-base font-semibold text-navy-800">{job.title}</h3>
                  <div className="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500">
                    <span>🏷️ {job.dept}</span>
                    <span>🕒 {job.type}</span>
                    <span>📍 {job.location}</span>
                  </div>
                </div>
                <a
                  href={applyHref(job.title)}
                  className="shrink-0 rounded-full bg-navy-800 px-6 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-brand-600"
                >
                  Apply
                </a>
              </div>
            </Reveal>
          ))}
        </div>
        <p className="mt-8 text-center text-sm text-slate-500">
          Don&apos;t see a role that fits? Send your resume to{' '}
          <a
            href="mailto:careers@bwsuperbakeshop.com"
            className="font-semibold text-brand-600 hover:text-brand-500"
          >
            careers@bwsuperbakeshop.com
          </a>
        </p>
      </div>
    </section>
  )
}

function Culture() {
  return (
    <section id="culture" className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <div className="grid items-center gap-10 lg:grid-cols-2">
        <Reveal direction="left" className="relative">
          <img
            src="https://images.unsplash.com/photo-1556217477-d325251ece38?auto=format&fit=crop&w=800&q=80"
            alt="Bakers working together in the kitchen"
            className="h-80 w-full rounded-3xl object-cover shadow-xl"
          />
          <div className="absolute -bottom-5 -right-3 hidden rounded-2xl bg-navy-800 px-5 py-4 text-white shadow-xl sm:block">
            <p className="font-script text-2xl text-brand-400">500+</p>
            <p className="text-xs text-navy-50/80">team members</p>
          </div>
        </Reveal>
        <Reveal direction="right" delay={120}>
          <span className="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">
            Our culture
          </span>
          <h2 className="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">
            Where passion meets pastry
          </h2>
          <p className="mt-4 text-sm leading-relaxed text-slate-600">
            At bw Superbakeshop, every team member plays a part in creating moments
            of joy. We believe in mentorship, fairness, and celebrating wins
            together — big or small.
          </p>
          <ul className="mt-5 space-y-2 text-sm text-slate-600">
            {['Hands-on training from day one', 'Promote-from-within philosophy', 'Safe, inclusive workplace', 'Recognition for great work'].map((item) => (
              <li key={item} className="flex items-center gap-2">
                <span className="text-brand-500">✓</span>
                {item}
              </li>
            ))}
          </ul>
        </Reveal>
      </div>
    </section>
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
        <a
          href="#openings"
          className="mt-6 inline-block rounded-full bg-white px-7 py-3 text-sm font-semibold text-brand-600 shadow-md transition hover:bg-navy-50"
        >
          Browse open roles
        </a>
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
        <p className="text-xs">© {2026} bw Superbakeshop. Made with 🧡</p>
        <Link to="/" className="text-xs font-medium transition hover:text-brand-400">
          ← Back to home
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
