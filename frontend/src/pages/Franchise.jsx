import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import Reveal from '../components/Reveal'
import { DEFAULT_CONTENT, getCachedContent, getSiteContent } from '../lib/content'
import { useSeo } from '../lib/seo'

// Franchise / "Own a bakeshop" page. Content is editable from the admin Site
// Editor (Franchise section) and stored in site_content.franchise.

const inquireHref = (email) =>
  `mailto:${email || DEFAULT_CONTENT.franchise.email}?subject=Franchise%20Inquiry&body=` +
  encodeURIComponent(
    'Name:\nContact number:\nPreferred location / city:\nPackage of interest:\nMessage:\n',
  )

export default function Franchise({ content: controlledContent, preview = false }) {
  useSeo('/franchise', !preview)
  const [content, setContent] = useState(getCachedContent)

  useEffect(() => {
    if (!controlledContent) getSiteContent().then(setContent)
  }, [controlledContent])

  const resolved = controlledContent || content
  const fr = resolved.franchise || DEFAULT_CONTENT.franchise
  const href = inquireHref(fr.email)

  return (
    <div className="min-h-screen bg-white text-navy-800">
      <FranchiseHeader href={href} />
      <Hero hero={fr.hero} href={href} />
      <Perks perks={fr.perks || []} />
      <Steps steps={fr.steps || []} />
      <Packages packages={fr.packages || []} href={href} />
      <CTA email={fr.email} href={href} />
      <Footer />
    </div>
  )
}

function FranchiseHeader({ href }) {
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
            href={href}
            className="hidden rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 sm:block"
          >
            Inquire now
          </a>
        </div>
      </div>
    </header>
  )
}

function Hero({ hero, href }) {
  const h = hero || DEFAULT_CONTENT.franchise.hero
  return (
    <section className="relative overflow-hidden bg-navy-900">
      <img
        src="/images/bakery-interior.jpg"
        alt=""
        aria-hidden="true"
        loading="lazy"
        decoding="async"
        className="absolute inset-0 h-full w-full object-cover"
      />
      <div className="absolute inset-0 bg-gradient-to-b from-navy-900/85 via-navy-900/80 to-navy-900/90" />
      <div className="relative mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
        <span className="text-xs font-semibold uppercase tracking-[0.3em] text-brand-400">
          {h.eyebrow}
        </span>
        <h1 className="mt-5 text-4xl font-bold leading-tight text-white sm:text-5xl">{h.title}</h1>
        <p className="mx-auto mt-5 max-w-xl text-base text-navy-50/80">{h.subtitle}</p>
        <div className="mt-8 flex flex-wrap justify-center gap-3">
          <a
            href={href}
            className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
          >
            Inquire about a franchise
          </a>
          <a
            href="#packages"
            className="rounded-full border border-white/30 px-7 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
          >
            View packages
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
          eyebrow="Why franchise with us"
          title="A partnership built to rise"
          subtitle="We give you the brand, the systems, and the support — you bring the passion for your community."
        />
      </Reveal>
      <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {perks.map((p, i) => (
          <Reveal key={p.title + i} delay={(i % 3) * 100}>
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

function Steps({ steps }) {
  return (
    <section className="bg-navy-50/60 py-16">
      <div className="mx-auto max-w-5xl px-4 sm:px-6">
        <Reveal>
          <SectionHeading
            eyebrow="How it works"
            title="From inquiry to grand opening"
            subtitle="A clear, guided path to opening your own branch."
          />
        </Reveal>
        <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {steps.map((s, i) => (
            <Reveal key={s.title + i} delay={(i % 4) * 80}>
              <div className="h-full rounded-2xl bg-white p-6 shadow-sm transition hover:shadow-md">
                <span className="font-script text-3xl text-brand-500">{s.n}</span>
                <h3 className="mt-2 text-base font-semibold text-navy-800">{s.title}</h3>
                <p className="mt-2 text-sm text-slate-500">{s.text}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  )
}

function Packages({ packages, href }) {
  return (
    <section id="packages" className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <Reveal>
        <SectionHeading
          eyebrow="Franchise packages"
          title="Pick the format that fits you"
          subtitle="Indicative investment ranges — final figures depend on size, location, and build-out."
        />
      </Reveal>
      <div className="mt-10 grid gap-5 lg:grid-cols-3">
        {packages.map((pkg, i) => (
          <Reveal key={pkg.name + i} delay={(i % 3) * 100}>
            <div
              className={`flex h-full flex-col rounded-2xl border p-6 shadow-sm transition hover:shadow-lg ${
                pkg.featured
                  ? 'border-brand-400 ring-2 ring-brand-500/20'
                  : 'border-slate-100 bg-white'
              }`}
            >
              {pkg.featured && (
                <span className="mb-3 w-fit rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-600">
                  Most popular
                </span>
              )}
              <h3 className="text-lg font-bold text-navy-800">{pkg.name}</h3>
              <p className="mt-1 text-2xl font-bold text-brand-600">{pkg.price}</p>
              <p className="mt-2 text-sm text-slate-500">{pkg.blurb}</p>
              <ul className="mt-4 space-y-2 text-sm text-slate-600">
                {(pkg.features || []).map((f, fi) => (
                  <li key={fi} className="flex items-start gap-2">
                    <span className="mt-0.5 text-brand-500">✓</span>
                    {f}
                  </li>
                ))}
              </ul>
              <a
                href={href}
                className={`mt-6 block rounded-full px-6 py-3 text-center text-sm font-semibold transition ${
                  pkg.featured
                    ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md shadow-brand-500/30 hover:from-brand-600 hover:to-brand-600'
                    : 'bg-navy-800 text-white hover:bg-brand-600'
                }`}
              >
                Inquire
              </a>
            </div>
          </Reveal>
        ))}
      </div>
    </section>
  )
}

function CTA({ email, href }) {
  return (
    <Reveal as="section" className="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
      <div className="rounded-3xl bg-gradient-to-r from-navy-800 to-navy-900 px-8 py-12 text-center text-white shadow-xl sm:px-12">
        <h2 className="text-2xl font-bold sm:text-3xl">Ready to start your own branch?</h2>
        <p className="mx-auto mt-2 max-w-md text-sm text-navy-50/80">
          Tell us about yourself and your location. Our franchising team will reply
          with the full kit and next steps.
        </p>
        <a
          href={href}
          className="mt-6 inline-block rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
        >
          Inquire about a franchise
        </a>
        <p className="mt-4 text-xs text-navy-50/70">
          Or email{' '}
          <a
            href={`mailto:${email || DEFAULT_CONTENT.franchise.email}`}
            className="font-semibold text-brand-400 hover:text-brand-300"
          >
            {email || DEFAULT_CONTENT.franchise.email}
          </a>
        </p>
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
        <p className="text-xs">© {2026} BW Superbakeshop</p>
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
