import { useState } from 'react'
import { Link } from 'react-router-dom'
import Reveal from '../components/Reveal'

// Goldilocks-style marketing landing page for the Bakery Ordering System,
// built entirely with our existing navy + brand-orange palette.
export default function Landing() {
  return (
    <div className="min-h-screen bg-white text-navy-800">
      <AnnouncementBar />
      <NavBar />
      <Hero />
      <Categories />
      <BestSellers />
      <PromoBanner />
      <Features />
      <Story />
      <StoreLocator />
      <Newsletter />
      <Footer />
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* Top bar + navigation                                                */
/* ------------------------------------------------------------------ */

function AnnouncementBar() {
  return (
    <div className="bg-gradient-to-r from-brand-500 to-brand-600 text-center text-xs font-medium tracking-wide text-white">
      <p className="px-4 py-2">
        🚚 Free delivery on orders over ₱1,000 &nbsp;•&nbsp; Freshly baked every
        morning &nbsp;•&nbsp; Order now and taste the love!
      </p>
    </div>
  )
}

function NavBar() {
  const [open, setOpen] = useState(false)
  const links = [
    { label: 'Home', href: '#home' },
    { label: 'Cakes', href: '#categories' },
    { label: 'Best Sellers', href: '#best-sellers' },
    { label: 'Stores', href: '#stores' },
    { label: 'About', href: '#about' },
  ]

  return (
    <header className="sticky top-0 z-50 border-b border-slate-100 bg-white/90 backdrop-blur">
      <nav className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
        <Logo />

        <ul className="hidden items-center gap-7 text-sm font-medium text-navy-700 lg:flex">
          {links.map((l) => (
            <li key={l.label}>
              <a href={l.href} className="transition hover:text-brand-600">
                {l.label}
              </a>
            </li>
          ))}
        </ul>

        <div className="hidden items-center gap-3 lg:flex">
          <Link
            to="/login"
            className="text-sm font-semibold text-navy-700 transition hover:text-brand-600"
          >
            Sign In
          </Link>
          <Link
            to="/register"
            className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
          >
            Order Now
          </Link>
        </div>

        <button
          type="button"
          onClick={() => setOpen((o) => !o)}
          aria-label="Toggle menu"
          className="text-navy-800 lg:hidden"
        >
          <MenuIcon className="h-6 w-6" />
        </button>
      </nav>

      {open && (
        <div className="border-t border-slate-100 bg-white px-4 py-3 lg:hidden">
          <ul className="flex flex-col gap-1 text-sm font-medium text-navy-700">
            {links.map((l) => (
              <li key={l.label}>
                <a
                  href={l.href}
                  onClick={() => setOpen(false)}
                  className="block rounded-lg px-3 py-2 transition hover:bg-navy-50 hover:text-brand-600"
                >
                  {l.label}
                </a>
              </li>
            ))}
          </ul>
          <div className="mt-3 flex gap-3">
            <Link
              to="/login"
              className="flex-1 rounded-full border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-navy-700"
            >
              Sign In
            </Link>
            <Link
              to="/register"
              className="flex-1 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white"
            >
              Order Now
            </Link>
          </div>
        </div>
      )}
    </header>
  )
}

function Logo() {
  return (
    <Link to="/" className="flex items-center gap-2">
      <img
        src="/images/logo (1).png"
        alt="bw Superbakeshop"
        className="h-16 w-auto"
      />
      <span className="font-brand text-2xl font-bold text-brand-500 sm:text-3xl">
        Superbakeshop
      </span>
    </Link>
  )
}

/* ------------------------------------------------------------------ */
/* Hero                                                                */
/* ------------------------------------------------------------------ */

function Hero() {
  return (
    <section
      id="home"
      className="relative overflow-hidden bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900"
    >
      {/* faint baking-icon pattern */}
      <div className="pointer-events-none absolute inset-0 select-none text-5xl opacity-[0.06]">
        <div className="absolute left-8 top-10">🥐</div>
        <div className="absolute right-16 top-24">🧁</div>
        <div className="absolute left-1/3 top-40">🍞</div>
        <div className="absolute right-8 bottom-16">🍰</div>
        <div className="absolute left-12 bottom-10">🥨</div>
      </div>

      <div className="relative mx-auto grid max-w-6xl items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:py-24">
        <div className="text-center lg:text-left">
          <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold tracking-wide text-brand-400 ring-1 ring-white/20">
            <SparkleIcon className="h-4 w-4" />
            Freshly baked, made with love
          </span>
          <h1 className="mt-5 text-4xl font-bold leading-tight text-white sm:text-5xl">
            Freshly Baked Happiness,{' '}
            <span className="font-script font-normal text-brand-400">
              Delivered.
            </span>
          </h1>
          <p className="mx-auto mt-5 max-w-md text-base text-navy-50/80 lg:mx-0">
            From classic cakes to warm pandesal, order your favorite bakeshop
            treats online and have them delivered straight to your door.
          </p>
          <div className="mt-8 flex flex-wrap justify-center gap-3 lg:justify-start">
            <a
              href="#best-sellers"
              className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
            >
              Order Now
            </a>
            <a
              href="#categories"
              className="rounded-full border border-white/30 px-7 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
            >
              View Menu
            </a>
          </div>

          <div className="mt-10 flex items-center justify-center gap-8 lg:justify-start">
            <Stat value="50+" label="Treats baked daily" />
            <span className="h-10 w-px bg-white/20" />
            <Stat value="120+" label="Stores nationwide" />
            <span className="h-10 w-px bg-white/20" />
            <Stat value="30k+" label="Happy customers" />
          </div>
        </div>

        <div className="relative">
          <div className="absolute -inset-4 rounded-[2.5rem] bg-brand-500/20 blur-2xl" />
          <img
            src="https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=900&q=80"
            alt="A beautifully decorated layered cake"
            className="relative h-80 w-full rounded-[2rem] object-cover shadow-2xl sm:h-[26rem]"
          />
          <div className="absolute -bottom-5 -left-2 hidden items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-xl sm:flex">
            <span className="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-xl">
              🎂
            </span>
            <div className="text-left">
              <p className="text-sm font-semibold text-navy-800">Signature Cakes</p>
              <p className="text-xs text-slate-500">Baked fresh every day</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}

function Stat({ value, label }) {
  return (
    <div className="text-center lg:text-left">
      <p className="text-2xl font-bold text-white">{value}</p>
      <p className="text-xs text-navy-50/70">{label}</p>
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* Categories                                                          */
/* ------------------------------------------------------------------ */

const CATEGORIES = [
  { name: 'Cakes', emoji: '🎂' },
  { name: 'Breads', emoji: '🍞' },
  { name: 'Pastries', emoji: '🥐' },
  { name: 'Delicacies', emoji: '🍡' },
  { name: 'Cupcakes', emoji: '🧁' },
  { name: 'Cookies', emoji: '🍪' },
]

function Categories() {
  return (
    <section id="categories" className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <Reveal>
        <SectionHeading
          eyebrow="Shop by category"
          title="What are you craving today?"
          subtitle="Browse our full range of freshly baked goodies for every occasion."
        />
      </Reveal>
      <div className="mt-10 grid grid-cols-3 gap-4 sm:grid-cols-6">
        {CATEGORIES.map((c, i) => (
          <Reveal key={c.name} delay={i * 80}>
            <a
              href="#best-sellers"
              className="group flex flex-col items-center gap-3 rounded-2xl border border-slate-100 bg-white p-5 text-center shadow-sm transition hover:-translate-y-1 hover:border-brand-200 hover:shadow-lg"
            >
              <span className="flex h-16 w-16 items-center justify-center rounded-full bg-navy-50 text-3xl transition group-hover:bg-brand-100">
                {c.emoji}
              </span>
              <span className="text-sm font-semibold text-navy-700">{c.name}</span>
            </a>
          </Reveal>
        ))}
      </div>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* Best sellers                                                        */
/* ------------------------------------------------------------------ */

const PRODUCTS = [
  {
    name: 'Classic Mocha Cake',
    price: '₱650',
    tag: 'Best Seller',
    img: 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?auto=format&fit=crop&w=600&q=80',
  },
  {
    name: 'Ube Chiffon Cake',
    price: '₱720',
    tag: 'New',
    img: 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=600&q=80',
  },
  {
    name: 'Soft Ensaymada',
    price: '₱45',
    tag: 'Popular',
    img: 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=600&q=80',
  },
  {
    name: 'Fresh Pandesal (12pcs)',
    price: '₱60',
    tag: 'Daily',
    img: 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?auto=format&fit=crop&w=600&q=80',
  },
  {
    name: 'Chocolate Cupcakes',
    price: '₱180',
    tag: 'Best Seller',
    img: 'https://images.unsplash.com/photo-1426869981800-95ebf51ce900?auto=format&fit=crop&w=600&q=80',
  },
  {
    name: 'Buttery Croissant',
    price: '₱85',
    tag: 'Popular',
    img: 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=600&q=80',
  },
  {
    name: 'Red Velvet Slice',
    price: '₱150',
    tag: 'New',
    img: 'https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?auto=format&fit=crop&w=600&q=80',
  },
  {
    name: 'Assorted Cookies',
    price: '₱220',
    tag: 'Popular',
    img: 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=600&q=80',
  },
]

function BestSellers() {
  return (
    <section id="best-sellers" className="bg-navy-50/60 py-16">
      <div className="mx-auto max-w-6xl px-4 sm:px-6">
        <Reveal>
          <SectionHeading
            eyebrow="Crowd favorites"
            title="Our Best Sellers"
            subtitle="Tried, tested, and loved — the treats our customers can't get enough of."
          />
        </Reveal>
        <div className="mt-10 grid grid-cols-2 gap-5 md:grid-cols-4">
          {PRODUCTS.map((p, i) => (
            <Reveal key={p.name} delay={(i % 4) * 80}>
              <ProductCard product={p} />
            </Reveal>
          ))}
        </div>
        <div className="mt-10 text-center">
          <Link
            to="/register"
            className="inline-block rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
          >
            See full menu
          </Link>
        </div>
      </div>
    </section>
  )
}

function ProductCard({ product }) {
  return (
    <div className="group overflow-hidden rounded-2xl bg-white shadow-sm transition hover:shadow-xl">
      <div className="relative overflow-hidden">
        <img
          src={product.img}
          alt={product.name}
          className="h-40 w-full object-cover transition duration-300 group-hover:scale-105"
        />
        <span className="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wide text-brand-600">
          {product.tag}
        </span>
      </div>
      <div className="p-4">
        <h3 className="text-sm font-semibold text-navy-800">{product.name}</h3>
        <div className="mt-3 flex items-center justify-between">
          <span className="text-lg font-bold text-brand-600">{product.price}</span>
          <button
            type="button"
            aria-label={`Add ${product.name} to cart`}
            className="flex h-9 w-9 items-center justify-center rounded-full bg-navy-800 text-white transition hover:bg-brand-600"
          >
            <PlusIcon className="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* Promo banner                                                        */
/* ------------------------------------------------------------------ */

function PromoBanner() {
  return (
    <Reveal as="section" className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <div className="relative overflow-hidden rounded-3xl bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-12 text-white shadow-xl sm:px-12">
        <div className="pointer-events-none absolute -right-6 -top-6 text-[9rem] opacity-20">
          🎉
        </div>
        <div className="relative max-w-xl">
          <p className="font-script text-2xl text-white/90">Celebrate every moment</p>
          <h2 className="mt-2 text-3xl font-bold sm:text-4xl">
            Custom cakes for birthdays & special occasions
          </h2>
          <p className="mt-3 text-sm text-white/90">
            Make it unforgettable with a personalized cake, baked fresh and
            decorated just the way you want it.
          </p>
          <Link
            to="/register"
            className="mt-6 inline-block rounded-full bg-white px-7 py-3 text-sm font-semibold text-brand-600 shadow-md transition hover:bg-navy-50"
          >
            Order a custom cake
          </Link>
        </div>
      </div>
    </Reveal>
  )
}

/* ------------------------------------------------------------------ */
/* Features                                                            */
/* ------------------------------------------------------------------ */

const FEATURES = [
  {
    icon: BreadIcon,
    title: 'Freshly Baked Daily',
    text: 'Every treat is baked fresh each morning using quality ingredients.',
  },
  {
    icon: TruckIcon,
    title: 'Fast Delivery',
    text: 'Get your orders delivered hot and fresh, right to your doorstep.',
  },
  {
    icon: HeartIcon,
    title: 'Made With Love',
    text: 'Our bakers pour care into every single piece we make.',
  },
  {
    icon: StoreIcon,
    title: 'Nationwide Stores',
    text: 'Over 120 branches across the country, always close to you.',
  },
]

function Features() {
  return (
    <section className="bg-navy-50/60 py-16">
      <div className="mx-auto max-w-6xl px-4 sm:px-6">
        <Reveal>
          <SectionHeading
            eyebrow="Why choose us"
            title="Baked better, served with care"
          />
        </Reveal>
        <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {FEATURES.map((f, i) => (
            <Reveal key={f.title} delay={i * 100}>
              <div className="rounded-2xl bg-white p-6 text-center shadow-sm transition hover:shadow-lg">
                <span className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 text-white">
                  <f.icon className="h-7 w-7" />
                </span>
                <h3 className="mt-4 text-base font-semibold text-navy-800">
                  {f.title}
                </h3>
                <p className="mt-2 text-sm text-slate-500">{f.text}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* Story / About                                                       */
/* ------------------------------------------------------------------ */

function Story() {
  return (
    <section id="about" className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <div className="grid items-center gap-10 lg:grid-cols-2">
        <Reveal direction="left" className="relative">
          <img
            src="/images/Screenshot_5.png"
            alt="An assortment of freshly baked breads"
            className="h-80 w-full rounded-3xl object-cover shadow-xl"
          />
          <div className="absolute -bottom-5 -right-3 hidden rounded-2xl bg-navy-800 px-5 py-4 text-white shadow-xl sm:block">
            <p className="font-script text-2xl text-brand-400">Since 1966</p>
            <p className="text-xs text-navy-50/80">Baking happiness</p>
          </div>
        </Reveal>
        <Reveal direction="right" delay={120}>
          <span className="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">
            Our story
          </span>
          <h2 className="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">
            A bakeshop tradition you can taste
          </h2>
          <p className="mt-4 text-sm leading-relaxed text-slate-600">
            For decades, we've been bringing families together over freshly baked
            cakes, breads, and delicacies. What started as a small neighborhood
            bakeshop has grown into a beloved name — but our promise has never
            changed: quality treats, made with love.
          </p>
          <p className="mt-3 text-sm leading-relaxed text-slate-600">
            Now, with our online ordering system, your favorites are just a few
            clicks away.
          </p>
          <div className="mt-6 flex flex-wrap gap-3">
            <a
              href="#best-sellers"
              className="rounded-full bg-navy-800 px-6 py-3 text-sm font-semibold text-white transition hover:bg-navy-900"
            >
              Start ordering
            </a>
            <a
              href="#stores"
              className="rounded-full border border-slate-300 px-6 py-3 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600"
            >
              Find a store
            </a>
          </div>
        </Reveal>
      </div>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* Store locator                                                       */
/* ------------------------------------------------------------------ */

function StoreLocator() {
  return (
    <section id="stores" className="bg-navy-900 py-16">
      <Reveal className="mx-auto max-w-3xl px-4 text-center sm:px-6">
        <span className="flex mx-auto h-14 w-14 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20">
          <StoreIcon className="h-7 w-7 text-brand-400" />
        </span>
        <h2 className="mt-5 text-3xl font-bold text-white sm:text-4xl">
          120+ stores, always near you
        </h2>
        <p className="mt-3 text-sm text-navy-50/80">
          Find your nearest branch or simply order online for delivery and pickup.
        </p>
        <div className="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
          <input
            type="text"
            placeholder="Enter your city or area"
            className="w-full rounded-full border border-white/20 bg-white/5 px-5 py-3 text-sm text-white placeholder:text-navy-50/50 outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-400/30 sm:w-72"
          />
          <button
            type="button"
            className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
          >
            Find a store
          </button>
        </div>
      </Reveal>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* Newsletter                                                          */
/* ------------------------------------------------------------------ */

function Newsletter() {
  return (
    <section className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <Reveal className="rounded-3xl border border-brand-100 bg-brand-50 px-8 py-12 text-center sm:px-12">
        <h2 className="text-2xl font-bold text-navy-800 sm:text-3xl">
          Get sweet deals in your inbox 🍰
        </h2>
        <p className="mt-2 text-sm text-slate-600">
          Subscribe for exclusive promos, new treats, and special occasion offers.
        </p>
        <form
          onSubmit={(e) => e.preventDefault()}
          className="mx-auto mt-6 flex max-w-md flex-col gap-3 sm:flex-row"
        >
          <input
            type="email"
            required
            placeholder="Enter your email"
            className="w-full rounded-full border border-slate-300 px-5 py-3 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
          />
          <button
            type="submit"
            className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
          >
            Subscribe
          </button>
        </form>
      </Reveal>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* Footer                                                              */
/* ------------------------------------------------------------------ */

function Footer() {
  const cols = [
    { title: 'Shop', links: ['Cakes', 'Breads', 'Pastries', 'Delicacies'] },
    { title: 'Company', links: ['About Us', 'Our Stores', 'Careers', 'Contact'] },
    { title: 'Support', links: ['Help Center', 'Delivery Info', 'Returns', 'FAQs'] },
  ]
  return (
    <footer className="bg-navy-900 text-navy-50/80">
      <div className="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.5fr_1fr_1fr_1fr]">
        <div>
          <div className="flex items-center gap-2">
            <span className="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20">
              <ChefHat className="h-6 w-6 text-brand-400" />
            </span>
            <span className="font-script text-2xl text-white">Bakery</span>
          </div>
          <p className="mt-4 max-w-xs text-sm text-navy-50/70">
            Freshly baked. Made with love. Ordered with ease. Bringing bakeshop
            happiness to your doorstep.
          </p>
          <div className="mt-5 flex gap-3">
            {['f', 'in', '𝕏'].map((s) => (
              <a
                key={s}
                href="#"
                aria-label="Social link"
                className="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-sm font-semibold text-white transition hover:bg-brand-600"
              >
                {s}
              </a>
            ))}
          </div>
        </div>

        {cols.map((col) => (
          <div key={col.title}>
            <h4 className="text-sm font-semibold text-white">{col.title}</h4>
            <ul className="mt-4 space-y-2 text-sm">
              {col.links.map((l) => (
                <li key={l}>
                  <a href="#" className="transition hover:text-brand-400">
                    {l}
                  </a>
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>
      <div className="border-t border-white/10">
        <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-navy-50/60 sm:flex-row sm:px-6">
          <p>© {2026} Bakery Ordering System. All rights reserved.</p>
          <p>Made with 🧡 for local bakeshops</p>
        </div>
      </div>
    </footer>
  )
}

/* ------------------------------------------------------------------ */
/* Shared bits                                                         */
/* ------------------------------------------------------------------ */

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

/* ------------------------------------------------------------------ */
/* Icons (inline, matching the rest of the codebase)                  */
/* ------------------------------------------------------------------ */

function ChefHat({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M7 21h10a1 1 0 0 0 1-1v-5H6v5a1 1 0 0 0 1 1Z" />
      <path d="M18.5 4.5a3.5 3.5 0 0 0-3.2 2.07 3.5 3.5 0 0 0-6.6 0A3.5 3.5 0 1 0 6 13.5h12a3.5 3.5 0 0 0 .5-9Z" />
    </svg>
  )
}

function MenuIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
      <line x1="4" y1="7" x2="20" y2="7" />
      <line x1="4" y1="12" x2="20" y2="12" />
      <line x1="4" y1="17" x2="20" y2="17" />
    </svg>
  )
}

function PlusIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
      <line x1="12" y1="5" x2="12" y2="19" />
      <line x1="5" y1="12" x2="19" y2="12" />
    </svg>
  )
}

function SparkleIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M12 2l1.8 5.2L19 9l-5.2 1.8L12 16l-1.8-5.2L5 9l5.2-1.8L12 2Z" />
    </svg>
  )
}

function BreadIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M6 5h12a4 4 0 0 1 0 8h-1v6a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-6H6a4 4 0 0 1 0-8Z" />
    </svg>
  )
}

function TruckIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M1 4h13v12H1z" />
      <path d="M14 8h4l3 3v5h-7" />
      <circle cx="6" cy="18" r="2" />
      <circle cx="18" cy="18" r="2" />
    </svg>
  )
}

function HeartIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" />
    </svg>
  )
}

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
