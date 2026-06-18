import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import Reveal from '../components/Reveal'

// "Find a Store" page — search and filter bw Superbakeshop branches.

const REGIONS = ['All', 'Metro Manila', 'Luzon', 'Visayas', 'Mindanao']

const STORES = [
  { name: 'BW Superbakeshop — Commonwealth', region: 'Metro Manila', address: '123 Commonwealth Ave, Quezon City', hours: '6:00 AM – 9:00 PM', phone: '(02) 8123 4567' },
  { name: 'BW Superbakeshop — Makati', region: 'Metro Manila', address: '88 Ayala Ave, Makati City', hours: '7:00 AM – 10:00 PM', phone: '(02) 8234 5678' },
  { name: 'BW Superbakeshop — Pasig', region: 'Metro Manila', address: 'Ortigas Center, Pasig City', hours: '6:30 AM – 9:30 PM', phone: '(02) 8345 6789' },
  { name: 'BW Superbakeshop — Pampanga', region: 'Luzon', address: 'MacArthur Hwy, San Fernando, Pampanga', hours: '6:00 AM – 9:00 PM', phone: '(045) 961 2345' },
  { name: 'BW Superbakeshop — Baguio', region: 'Luzon', address: 'Session Rd, Baguio City', hours: '7:00 AM – 9:00 PM', phone: '(074) 442 1234' },
  { name: 'BW Superbakeshop — Cebu', region: 'Visayas', address: 'Osmeña Blvd, Cebu City', hours: '6:30 AM – 10:00 PM', phone: '(032) 255 6789' },
  { name: 'BW Superbakeshop — Iloilo', region: 'Visayas', address: 'Diversion Rd, Mandurriao, Iloilo City', hours: '7:00 AM – 9:00 PM', phone: '(033) 320 4567' },
  { name: 'BW Superbakeshop — Davao', region: 'Mindanao', address: 'C.M. Recto St, Davao City', hours: '6:00 AM – 9:30 PM', phone: '(082) 224 7890' },
  { name: 'BW Superbakeshop — Cagayan de Oro', region: 'Mindanao', address: 'Corrales Ave, Cagayan de Oro', hours: '7:00 AM – 9:00 PM', phone: '(088) 856 3456' },
]

// Directions link (turn-by-turn from the user's location).
const dirHref = (address) =>
  `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(address)}`

// Embeddable map for an address — no API key required.
const embedSrc = (address) =>
  `https://www.google.com/maps?q=${encodeURIComponent(address)}&output=embed`

export default function Stores() {
  const [region, setRegion] = useState('All')
  const [query, setQuery] = useState('')
  const [selectedName, setSelectedName] = useState(STORES[0].name)

  const visible = useMemo(() => {
    const q = query.trim().toLowerCase()
    return STORES.filter((s) => {
      const inRegion = region === 'All' || s.region === region
      const matches =
        !q || s.name.toLowerCase().includes(q) || s.address.toLowerCase().includes(q)
      return inRegion && matches
    })
  }, [region, query])

  // Selected store always falls back to the first one currently visible.
  const selected = visible.find((s) => s.name === selectedName) || visible[0]

  return (
    <div className="min-h-screen bg-white text-navy-800">
      <StoresHeader />

      {/* hero + search */}
      <section className="relative overflow-hidden bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900">
        <div className="pointer-events-none absolute inset-0 select-none text-5xl opacity-[0.06]">
          <div className="absolute left-8 top-10">🥐</div>
          <div className="absolute right-16 top-24">🧁</div>
          <div className="absolute left-1/3 top-32">🍞</div>
          <div className="absolute right-8 bottom-10">🍰</div>
        </div>
        <div className="relative mx-auto max-w-3xl px-4 py-16 text-center sm:px-6">
          <span className="flex mx-auto h-14 w-14 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20">
            <StoreIcon className="h-7 w-7 text-brand-400" />
          </span>
          <h1 className="mt-5 text-4xl font-bold text-white sm:text-5xl">
            Find a{' '}
            <span className="font-script font-normal text-brand-400">store</span>
          </h1>
          <p className="mx-auto mt-4 max-w-md text-base text-navy-50/80">
            120+ branches nationwide. Search for the bw Superbakeshop nearest you.
          </p>
          <div className="relative mx-auto mt-7 max-w-md">
            <SearchIcon className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
            <input
              type="search"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Search by city, area, or branch name"
              className="w-full rounded-full border border-white/20 bg-white py-3.5 pl-11 pr-4 text-sm text-navy-800 outline-none transition focus:ring-2 focus:ring-brand-400/40"
            />
          </div>
        </div>
      </section>

      {/* listings */}
      <section className="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        {/* region filter */}
        <div className="mb-8 flex flex-wrap justify-center gap-2">
          {REGIONS.map((r) => (
            <button
              key={r}
              type="button"
              onClick={() => setRegion(r)}
              className={`rounded-full px-4 py-2 text-sm font-semibold transition ${
                region === r
                  ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md shadow-brand-500/30'
                  : 'border border-slate-200 bg-white text-navy-700 hover:border-brand-200 hover:text-brand-600'
              }`}
            >
              {r}
            </button>
          ))}
        </div>

        <p className="mb-5 text-center text-sm text-slate-500">
          {visible.length} {visible.length === 1 ? 'store' : 'stores'} found
        </p>

        {visible.length === 0 ? (
          <div className="py-16 text-center">
            <div className="text-5xl">📍</div>
            <p className="mt-3 text-sm text-slate-500">
              No stores found{query && ` for “${query}”`}. Try another area.
            </p>
          </div>
        ) : (
          <div className="grid gap-6 lg:grid-cols-2">
            {/* map (top on mobile, right on desktop) */}
            <div className="order-1 lg:order-2">
              <div className="lg:sticky lg:top-20">
                <div className="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                  <iframe
                    key={selected.address}
                    title={`Map of ${selected.name}`}
                    src={embedSrc(selected.address)}
                    className="h-72 w-full lg:h-[26rem]"
                    style={{ border: 0 }}
                    loading="lazy"
                    referrerPolicy="no-referrer-when-downgrade"
                    allowFullScreen
                  />
                </div>
                <div className="mt-3 flex flex-col gap-3 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                  <div className="min-w-0">
                    <h3 className="truncate text-sm font-bold text-navy-800">{selected.name}</h3>
                    <p className="truncate text-xs text-slate-500">{selected.address}</p>
                  </div>
                  <a
                    href={dirHref(selected.address)}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
                  >
                    <PinIcon className="h-4 w-4" />
                    Get directions
                  </a>
                </div>
              </div>
            </div>

            {/* list (bottom on mobile, left on desktop) */}
            <div className="order-2 space-y-3 lg:order-1">
              {visible.map((s, i) => (
                <Reveal key={s.name} delay={(i % 4) * 60}>
                  <StoreCard
                    store={s}
                    selected={selected?.name === s.name}
                    onSelect={() => setSelectedName(s.name)}
                  />
                </Reveal>
              ))}
            </div>
          </div>
        )}
      </section>

      <Footer />
    </div>
  )
}

function StoreCard({ store, selected, onSelect }) {
  return (
    <button
      type="button"
      onClick={onSelect}
      className={`w-full rounded-2xl border bg-white p-5 text-left shadow-sm transition hover:shadow-md ${
        selected ? 'border-brand-400 ring-2 ring-brand-500/30' : 'border-slate-100'
      }`}
    >
      <span className="w-fit rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-600">
        {store.region}
      </span>
      <h3 className="mt-3 text-base font-bold text-navy-800">{store.name}</h3>
      <ul className="mt-3 space-y-2 text-sm text-slate-600">
        <li className="flex gap-2">
          <PinIcon className="mt-0.5 h-4 w-4 shrink-0 text-brand-500" />
          {store.address}
        </li>
        <li className="flex gap-2">
          <ClockIcon className="mt-0.5 h-4 w-4 shrink-0 text-brand-500" />
          {store.hours}
        </li>
        <li className="flex gap-2">
          <PhoneIcon className="mt-0.5 h-4 w-4 shrink-0 text-brand-500" />
          {store.phone}
        </li>
      </ul>
      <div className="mt-4 flex items-center gap-2">
        <span
          className={`inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-semibold transition ${
            selected ? 'bg-brand-50 text-brand-600' : 'bg-navy-50 text-navy-700'
          }`}
        >
          <MapIcon className="h-4 w-4" />
          {selected ? 'Showing on map' : 'Show on map'}
        </span>
        <a
          href={dirHref(store.address)}
          target="_blank"
          rel="noreferrer"
          onClick={(e) => e.stopPropagation()}
          className="inline-flex items-center gap-1.5 rounded-full bg-navy-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600"
        >
          <PinIcon className="h-4 w-4" />
          Directions
        </a>
      </div>
    </button>
  )
}

function StoresHeader() {
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
          <Link
            to="/menu"
            className="hidden rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 sm:block"
          >
            Order Now
          </Link>
        </div>
      </div>
    </header>
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

/* icons */
function StoreIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M3 9l1.5-5h15L21 9" />
      <path d="M4 9v11h16V9" />
      <path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" />
      <path d="M9 20v-5h6v5" />
    </svg>
  )
}

function SearchIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="11" cy="11" r="8" />
      <line x1="21" y1="21" x2="16.65" y2="16.65" />
    </svg>
  )
}

function PinIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z" />
      <circle cx="12" cy="10" r="3" />
    </svg>
  )
}

function MapIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6" />
      <line x1="8" y1="2" x2="8" y2="18" />
      <line x1="16" y1="6" x2="16" y2="22" />
    </svg>
  )
}

function ClockIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="9" />
      <polyline points="12 7 12 12 15 14" />
    </svg>
  )
}

function PhoneIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z" />
    </svg>
  )
}
