import { useState } from 'react'
import { useAuth } from '../context/AuthContext'

/* ------------------------------------------------------------------ */
/* mock catalog data                                                   */
/* ------------------------------------------------------------------ */
const NAV = [
  { label: 'Home', icon: HomeIcon },
  { label: 'All Products', icon: GridIcon },
  { label: 'Categories', icon: TagIcon },
  { label: 'My Orders', icon: BagIcon },
  { label: 'Favorites', icon: HeartIcon },
  { label: 'Offers', icon: GiftIcon },
]

const CATEGORIES = [
  { name: 'Breads', emoji: '🍞' },
  { name: 'Cakes', emoji: '🎂' },
  { name: 'Pastries', emoji: '🥐' },
  { name: 'Cookies', emoji: '🍪' },
  { name: 'Donuts', emoji: '🍩' },
  { name: 'Desserts', emoji: '🍰' },
  { name: 'Gift Hampers', emoji: '🎁' },
]

const FEATURED = [
  { name: 'Chocolate Pastry', price: 3.49, rating: 4.8, reviews: 120, emoji: '🍫' },
  { name: 'Butter Croissant', price: 2.49, rating: 4.7, reviews: 98, emoji: '🥐' },
  { name: 'Blueberry Muffin', price: 2.29, rating: 4.9, reviews: 75, emoji: '🧁' },
  { name: 'Cheesy Garlic Bread', price: 3.49, rating: 4.7, reviews: 64, emoji: '🧄' },
  { name: 'Red Velvet Cupcake', price: 2.99, rating: 4.8, reviews: 110, emoji: '🍰' },
]

const ORDER = [
  { name: 'Butter Croissant', qty: 1, price: 2.49, emoji: '🥐' },
  { name: 'Chocolate Pastry', qty: 1, price: 3.49, emoji: '🍫' },
  { name: 'Red Velvet Cupcake', qty: 1, price: 2.99, emoji: '🍰' },
]

const FEATURES = [
  { title: 'Freshly Baked', sub: 'Every day, in every store', icon: '🥐' },
  { title: 'Fast Delivery', sub: 'On time, every time', icon: '🚚' },
  { title: 'Premium Quality', sub: 'Best ingredients, always', icon: '🛡️' },
  { title: '24/7 Support', sub: "We're here to help", icon: '💬' },
]

/* ------------------------------------------------------------------ */
/* page                                                                */
/* ------------------------------------------------------------------ */
export default function Dashboard() {
  const { user } = useAuth()

  return (
    <div className="flex min-h-screen bg-slate-100 font-sans text-slate-800">
      <Sidebar user={user} />

      <div className="flex min-w-0 flex-1 flex-col">
        <TopBar />

        <div className="space-y-6 px-4 pb-8 sm:px-6">
          <Hero />

          <div className="flex flex-col gap-6 xl:flex-row">
            <main className="min-w-0 flex-1 space-y-6">
              <CategorySection />
              <FeaturedSection />
              <FeatureBadges />
            </main>

            <OrderPanel />
          </div>
        </div>
      </div>
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* sidebar                                                             */
/* ------------------------------------------------------------------ */
function Sidebar({ user }) {
  const { logout } = useAuth()
  const [active, setActive] = useState('Home')

  return (
    <aside className="sticky top-0 hidden h-screen w-64 shrink-0 flex-col bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 text-white lg:flex">
      {/* brand */}
      <div className="flex flex-col items-center border-b border-white/10 px-6 py-6 text-center">
        <ChefHat className="mb-1 h-8 w-8 text-brand-400" />
        <h1 className="font-script text-3xl">Bakery</h1>
        <p className="text-[0.6rem] font-semibold tracking-[0.3em] text-brand-400">
          ORDERING SYSTEM
        </p>
      </div>

      {/* nav */}
      <nav className="flex-1 space-y-1 px-4 py-5">
        {NAV.map(({ label, icon: Icon }) => {
          const isActive = active === label
          return (
            <button
              key={label}
              onClick={() => setActive(label)}
              className={`flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition ${
                isActive
                  ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-lg shadow-brand-500/30'
                  : 'text-navy-50/70 hover:bg-white/5 hover:text-white'
              }`}
            >
              <Icon className="h-5 w-5" />
              {label}
            </button>
          )
        })}
      </nav>

      {/* promo card */}
      <div className="mx-4 mb-4 rounded-2xl bg-white/10 p-4 text-center ring-1 ring-white/10">
        <div className="text-3xl">🛵</div>
        <p className="mt-1 text-sm font-semibold">We Value Happiness!</p>
        <p className="mt-1 text-xs text-navy-50/70">Freshly baked, right to your doorstep.</p>
        <button className="mt-3 w-full rounded-lg bg-white py-2 text-xs font-semibold text-navy-800 transition hover:bg-navy-50">
          ORDER NOW
        </button>
      </div>

      {/* profile */}
      <button
        onClick={logout}
        title="Sign out"
        className="flex items-center gap-3 border-t border-white/10 px-5 py-4 text-left transition hover:bg-white/5"
      >
        <span className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-500 text-sm font-bold">
          {(user?.name || 'C').charAt(0).toUpperCase()}
        </span>
        <span className="min-w-0">
          <span className="block truncate text-sm font-semibold">
            Hi, {user?.name || 'Customer'}
          </span>
          <span className="block text-xs text-navy-50/60">Good to see you again!</span>
        </span>
      </button>
    </aside>
  )
}

/* ------------------------------------------------------------------ */
/* top bar                                                             */
/* ------------------------------------------------------------------ */
function TopBar() {
  return (
    <header className="sticky top-0 z-20 flex items-center gap-4 border-b border-slate-200 bg-slate-100/90 px-4 py-4 backdrop-blur sm:px-6">
      <div className="relative flex-1">
        <SearchIcon className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
        <input
          type="search"
          placeholder="Search for breads, cakes, pastries..."
          className="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
        />
      </div>

      <div className="hidden items-center gap-2 text-sm text-slate-600 sm:flex">
        <PinIcon className="h-5 w-5 text-brand-500" />
        <div className="leading-tight">
          <p className="text-[0.65rem] text-slate-400">Deliver to</p>
          <p className="font-semibold text-slate-700">Home</p>
        </div>
      </div>

      <IconButton badge="3">
        <BellIcon className="h-5 w-5" />
      </IconButton>
      <IconButton badge="2">
        <CartIcon className="h-5 w-5" />
      </IconButton>
    </header>
  )
}

function IconButton({ children, badge }) {
  return (
    <button className="relative flex h-10 w-10 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:text-brand-600">
      {children}
      {badge && (
        <span className="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-brand-500 text-[0.6rem] font-bold text-white">
          {badge}
        </span>
      )}
    </button>
  )
}

/* ------------------------------------------------------------------ */
/* hero                                                                */
/* ------------------------------------------------------------------ */
function Hero() {
  return (
    <section className="relative overflow-hidden rounded-3xl bg-gradient-to-r from-brand-600 to-brand-400 px-7 py-8 text-white shadow-lg sm:px-10">
      <div className="relative z-10 max-w-md">
        <span className="inline-block rounded-full bg-white/20 px-3 py-1 text-[0.65rem] font-semibold tracking-wide">
          NEW &amp; DELICIOUS
        </span>
        <h2 className="mt-3 font-script text-3xl leading-none text-white drop-shadow">
          Chocolate
        </h2>
        <h2 className="text-4xl font-extrabold uppercase leading-tight tracking-tight">
          Hazelnut Cake
        </h2>
        <p className="mt-2 text-sm text-white/90">
          Rich chocolate sponge layered with creamy hazelnut frosting.
        </p>
        <button className="mt-5 inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-brand-600 shadow transition hover:bg-navy-50">
          Order Now <ArrowIcon className="h-4 w-4" />
        </button>

        <div className="mt-5 flex gap-1.5">
          <span className="h-2 w-6 rounded-full bg-white" />
          <span className="h-2 w-2 rounded-full bg-white/50" />
          <span className="h-2 w-2 rounded-full bg-white/50" />
          <span className="h-2 w-2 rounded-full bg-white/50" />
        </div>
      </div>

      {/* cake image */}
      <div className="pointer-events-none absolute right-4 top-1/2 hidden h-48 w-48 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-7xl sm:flex md:right-12 md:h-56 md:w-56">
        <img
          src="https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=400&q=80"
          alt="Chocolate hazelnut cake"
          className="h-full w-full rounded-full object-cover shadow-2xl"
          onError={(e) => {
            e.currentTarget.style.display = 'none'
          }}
        />
      </div>

      {/* discount badge */}
      <div className="absolute right-5 top-5 z-10 flex h-16 w-16 flex-col items-center justify-center rounded-full bg-navy-800 text-center text-white shadow-lg md:h-20 md:w-20">
        <span className="text-[0.5rem] font-medium uppercase tracking-wide text-navy-50/70">
          Introductory
        </span>
        <span className="text-base font-extrabold leading-none md:text-lg">20%</span>
        <span className="text-[0.6rem] font-semibold text-brand-400">OFF</span>
      </div>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* categories                                                          */
/* ------------------------------------------------------------------ */
function CategorySection() {
  return (
    <section>
      <SectionHeader title="Shop by Category" />
      <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-7">
        {CATEGORIES.map((c) => (
          <button
            key={c.name}
            className="flex flex-col items-center gap-2 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100 transition hover:-translate-y-0.5 hover:shadow-md"
          >
            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-brand-500/10 text-2xl">
              {c.emoji}
            </span>
            <span className="text-center text-xs font-medium text-slate-600">{c.name}</span>
          </button>
        ))}
      </div>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* featured                                                            */
/* ------------------------------------------------------------------ */
function FeaturedSection() {
  return (
    <section>
      <SectionHeader title="Featured for You" />
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">
        {FEATURED.map((p) => (
          <article
            key={p.name}
            className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100 transition hover:-translate-y-0.5 hover:shadow-md"
          >
            <div className="flex h-28 items-center justify-center bg-gradient-to-br from-brand-500/15 to-navy-700/10 text-5xl">
              {p.emoji}
            </div>
            <div className="space-y-1.5 p-3">
              <h4 className="truncate text-sm font-semibold text-navy-800">{p.name}</h4>
              <div className="flex items-center gap-1 text-xs text-slate-400">
                <StarIcon className="h-3.5 w-3.5 text-amber-400" />
                <span className="font-medium text-slate-600">{p.rating}</span>
                <span>({p.reviews})</span>
              </div>
              <div className="flex items-center justify-between pt-1">
                <span className="text-base font-bold text-brand-600">${p.price.toFixed(2)}</span>
              </div>
              <button className="mt-1 flex w-full items-center justify-center gap-1.5 rounded-lg border border-brand-500 py-1.5 text-xs font-semibold text-brand-600 transition hover:bg-brand-500 hover:text-white">
                <CartIcon className="h-3.5 w-3.5" />
                Add to Cart
              </button>
            </div>
          </article>
        ))}
      </div>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* feature badges                                                      */
/* ------------------------------------------------------------------ */
function FeatureBadges() {
  return (
    <section className="grid grid-cols-2 gap-4 lg:grid-cols-4">
      {FEATURES.map((f) => (
        <div
          key={f.title}
          className="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100"
        >
          <span className="flex h-11 w-11 items-center justify-center rounded-full bg-brand-500/10 text-xl">
            {f.icon}
          </span>
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-navy-800">{f.title}</p>
            <p className="truncate text-xs text-slate-400">{f.sub}</p>
          </div>
        </div>
      ))}
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* order panel                                                         */
/* ------------------------------------------------------------------ */
function OrderPanel() {
  const subtotal = ORDER.reduce((sum, i) => sum + i.price * i.qty, 0)
  const delivery = 2.0
  const total = subtotal + delivery

  return (
    <aside className="w-full shrink-0 xl:w-80">
      <div className="sticky top-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <div className="flex items-center justify-between">
          <h3 className="text-sm font-bold text-navy-800">Your Order ({ORDER.length})</h3>
          <button className="text-xs font-medium text-brand-600 hover:underline">Edit</button>
        </div>

        <div className="mt-4 space-y-3">
          {ORDER.map((item) => (
            <div key={item.name} className="flex items-center gap-3">
              <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand-500/10 text-xl">
                {item.emoji}
              </span>
              <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium text-navy-800">{item.name}</p>
                <p className="text-xs text-slate-400">${item.price.toFixed(2)}</p>
              </div>
              <div className="flex items-center gap-2">
                <Stepper>−</Stepper>
                <span className="w-4 text-center text-sm font-semibold">{item.qty}</span>
                <Stepper>+</Stepper>
              </div>
            </div>
          ))}
        </div>

        <div className="mt-5 space-y-2 border-t border-dashed border-slate-200 pt-4 text-sm">
          <Row label="Subtotal" value={subtotal} />
          <Row label="Delivery Fee" value={delivery} />
          <div className="flex items-center justify-between border-t border-slate-200 pt-2 text-base font-bold text-navy-800">
            <span>Total</span>
            <span className="text-brand-600">${total.toFixed(2)}</span>
          </div>
        </div>

        <button className="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
          Proceed to Checkout <ArrowIcon className="h-4 w-4" />
        </button>
        <p className="mt-3 flex items-center justify-center gap-1.5 text-xs text-slate-400">
          <ShieldIcon className="h-4 w-4 text-emerald-500" />
          Safe &amp; Secure Payments
        </p>
      </div>
    </aside>
  )
}

function Stepper({ children }) {
  return (
    <button className="flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 text-sm text-slate-600 transition hover:border-brand-500 hover:text-brand-600">
      {children}
    </button>
  )
}

function Row({ label, value }) {
  return (
    <div className="flex items-center justify-between text-slate-500">
      <span>{label}</span>
      <span className="font-medium text-slate-700">${value.toFixed(2)}</span>
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* shared bits                                                         */
/* ------------------------------------------------------------------ */
function SectionHeader({ title }) {
  return (
    <div className="mb-3 flex items-center justify-between">
      <h3 className="text-lg font-bold text-navy-800">{title}</h3>
      <button className="text-xs font-medium text-brand-600 hover:underline">View all →</button>
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* icons                                                               */
/* ------------------------------------------------------------------ */
function svg(props, ...children) {
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      {...props}
    >
      {children}
    </svg>
  )
}

// NOTE: declared as hoisted functions so the NAV array (defined above) can
// reference them at module-eval time without a temporal-dead-zone error.
function HomeIcon(p) { return svg(p, <path key="a" d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1Z" />) }
function GridIcon(p) { return svg(p, <g key="a"><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /></g>) }
function TagIcon(p) { return svg(p, <g key="a"><path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L3 13V3h10l7.59 7.59a2 2 0 0 1 0 2.82Z" /><circle cx="7.5" cy="7.5" r="1.5" /></g>) }
function BagIcon(p) { return svg(p, <g key="a"><path d="M6 7h12l-1 14H7L6 7Z" /><path d="M9 7a3 3 0 0 1 6 0" /></g>) }
function HeartIcon(p) { return svg(p, <path key="a" d="M20.8 8.6a5 5 0 0 0-8.8-2.3A5 5 0 0 0 3.2 8.6c0 4 5.4 7.9 8.8 10.2 3.4-2.3 8.8-6.2 8.8-10.2Z" />) }
function GiftIcon(p) { return svg(p, <g key="a"><rect x="3" y="8" width="18" height="4" rx="1" /><path d="M5 12v9h14v-9M12 8v13M12 8S10 3 7.5 4.5 12 8 12 8Zm0 0s2-5 4.5-3.5S12 8 12 8Z" /></g>) }
function SearchIcon(p) { return svg(p, <g key="a"><circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3" /></g>) }
function PinIcon(p) { return svg(p, <g key="a"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10" r="2.5" /></g>) }
function BellIcon(p) { return svg(p, <g key="a"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.7 21a2 2 0 0 1-3.4 0" /></g>) }
function CartIcon(p) { return svg(p, <g key="a"><circle cx="9" cy="21" r="1.5" /><circle cx="18" cy="21" r="1.5" /><path d="M3 4h2l2.4 12.2a1 1 0 0 0 1 .8h9.2a1 1 0 0 0 1-.8L21 8H6" /></g>) }
function ArrowIcon(p) { return svg(p, <path key="a" d="M5 12h14m-6-6 6 6-6 6" />) }
function StarIcon(p) { return svg({ ...p, fill: 'currentColor', stroke: 'none' }, <path key="a" d="m12 2 3 6.3 6.9.9-5 4.8 1.2 6.8L12 17.8 5.9 20.8 7.1 14l-5-4.8 6.9-.9Z" />) }
function ShieldIcon(p) { return svg(p, <g key="a"><path d="M12 3 5 6v6c0 4 3 6.5 7 8 4-1.5 7-4 7-8V6Z" /><path d="m9 12 2 2 4-4" /></g>) }

function ChefHat({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M7 21h10a1 1 0 0 0 1-1v-5H6v5a1 1 0 0 0 1 1Z" />
      <path d="M18.5 4.5a3.5 3.5 0 0 0-3.2 2.07 3.5 3.5 0 0 0-6.6 0A3.5 3.5 0 1 0 6 13.5h12a3.5 3.5 0 0 0 .5-9Z" />
    </svg>
  )
}
