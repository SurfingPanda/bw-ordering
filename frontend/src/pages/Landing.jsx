import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import Reveal, { StaticRevealContext } from '../components/Reveal'
import Carousel from '../components/Carousel'
import { getCachedContent, getSiteContent } from '../lib/content'
import { useSeo } from '../lib/seo'

// Goldilocks-style marketing landing page. Editable sections (announcement,
// promo banners, categories, best sellers) are loaded from Supabase via the
// admin Site Content editor, falling back to DEFAULT_CONTENT.
//
// When `content` is passed in (the admin live preview), the page is fully
// controlled by that prop and skips its own fetch, so edits show instantly.
// `preview` renders all reveal-on-scroll sections statically.
export default function Landing({ content: controlledContent, preview = false }) {
  useSeo('/', !preview)
  const isControlled = controlledContent != null
  const [fetched, setFetched] = useState(getCachedContent)

  useEffect(() => {
    if (isControlled) return
    getSiteContent().then(setFetched)
  }, [isControlled])

  const content = isControlled ? controlledContent : fetched

  const buttons = content.buttons

  const page = (
    <div className="min-h-screen bg-white text-navy-800">
      <AnnouncementBar text={content.announcement} />
      <NavBar buttons={buttons} />
      <Hero banners={content.banners} />
      <Categories items={content.categories} />
      <BestSellers products={content.bestSellers} buttons={buttons} />
      <PromoBanner buttons={buttons} />
      <Features />
      <Story buttons={buttons} />
      <StoreLocator buttons={buttons} />
      <Newsletter buttons={buttons} />
      <Footer />
    </div>
  )

  if (preview) {
    return <StaticRevealContext.Provider value={true}>{page}</StaticRevealContext.Provider>
  }
  return page
}

/* ------------------------------------------------------------------ */
/* Top bar + navigation                                                */
/* ------------------------------------------------------------------ */

function AnnouncementBar({ text }) {
  return (
    <div className="bg-navy-900 text-center text-xs font-medium tracking-wide text-white">
      <p className="px-4 py-2">{text}</p>
    </div>
  )
}

function NavBar({ buttons }) {
  const [open, setOpen] = useState(false)
  const showSignIn = buttons?.navSignIn !== false
  const showOrder = buttons?.navOrder !== false
  const links = [
    { label: 'Home', href: '#home' },
    { label: 'Products', href: '#categories' },
    { label: 'Best Sellers', href: '#best-sellers' },
    { label: 'Careers', to: '/careers' },
    { label: 'Franchise', to: '/franchise' },
  ]

  return (
    <header className="sticky top-0 z-50 border-b border-slate-100 bg-white">
      <nav className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <Logo />

        <ul className="hidden items-center gap-7 text-sm font-medium text-navy-700 lg:flex">
          {links.map((l) => (
            <li key={l.label}>
              {l.to ? (
                <Link to={l.to} className="transition hover:text-brand-600">
                  {l.label}
                </Link>
              ) : (
                <a href={l.href} className="transition hover:text-brand-600">
                  {l.label}
                </a>
              )}
            </li>
          ))}
        </ul>

        <div className="hidden items-center gap-3 lg:flex">
          {showSignIn && (
            <Link
              to="/login"
              className="text-sm font-semibold text-navy-700 transition hover:text-brand-600"
            >
              Sign In
            </Link>
          )}
          {showOrder && (
            <Link
              to="/menu"
              className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
            >
              Order Now
            </Link>
          )}
        </div>

        <button
          type="button"
          onClick={() => setOpen((o) => !o)}
          aria-label="Toggle menu"
          className="ml-2 shrink-0 text-navy-800 lg:hidden"
        >
          <MenuIcon className="h-6 w-6" />
        </button>
      </nav>

      {open && (
        <div className="border-t border-slate-100 bg-white px-4 py-3 lg:hidden">
          <ul className="flex flex-col gap-1 text-sm font-medium text-navy-700">
            {links.map((l) => (
              <li key={l.label}>
                {l.to ? (
                  <Link
                    to={l.to}
                    onClick={() => setOpen(false)}
                    className="block rounded-lg px-3 py-2 transition hover:bg-navy-50 hover:text-brand-600"
                  >
                    {l.label}
                  </Link>
                ) : (
                  <a
                    href={l.href}
                    onClick={() => setOpen(false)}
                    className="block rounded-lg px-3 py-2 transition hover:bg-navy-50 hover:text-brand-600"
                  >
                    {l.label}
                  </a>
                )}
              </li>
            ))}
          </ul>
          {(showSignIn || showOrder) && (
            <div className="mt-3 flex gap-3">
              {showSignIn && (
                <Link
                  to="/login"
                  className="flex-1 rounded-full border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-navy-700"
                >
                  Sign In
                </Link>
              )}
              {showOrder && (
                <Link
                  to="/menu"
                  className="flex-1 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white"
                >
                  Order Now
                </Link>
              )}
            </div>
          )}
        </div>
      )}
    </header>
  )
}

function Logo() {
  return (
    <Link to="/" className="flex min-w-0 items-center gap-2">
      <img
        src="/images/logo (1).png"
        alt="bw Superbakeshop"
        className="h-9 w-auto shrink-0 sm:h-11"
      />
      <span className="truncate font-brand text-lg font-bold text-brand-500 sm:text-2xl">
        Superbakeshop
      </span>
    </Link>
  )
}

/* ------------------------------------------------------------------ */
/* Hero                                                                */
/* ------------------------------------------------------------------ */

function Hero({ banners }) {
  return (
    <section id="home" className="bg-navy-900">
      <Carousel
        slides={banners.map((s, i) => (
          <HeroSlide key={s.img + i} slide={s} />
        ))}
      />
    </section>
  )
}

function HeroSlide({ slide }) {
  return (
    <Link to="/menu" className="block">
      <img
        src={slide.img}
        alt={slide.alt}
        className="aspect-[12/5] max-h-[800px] w-full object-cover object-top"
      />
    </Link>
  )
}

/* ------------------------------------------------------------------ */
/* Categories                                                          */
/* ------------------------------------------------------------------ */

function Categories({ items }) {
  return (
    <section id="categories" className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <Reveal>
        <SectionHeading
          eyebrow="Shop by category"
          title="What are you craving today?"
          subtitle="Browse our full range of freshly baked goodies for every occasion."
        />
      </Reveal>
      <div className="mt-10 grid grid-cols-2 gap-6 sm:grid-cols-3">
        {items.map((c, i) => (
          <Reveal key={c.name + i} delay={i * 80}>
            <a
              href="#best-sellers"
              className="group flex flex-col items-center gap-4 rounded-3xl border border-slate-100 bg-white p-8 text-center shadow-sm transition hover:-translate-y-1 hover:border-brand-200 hover:shadow-lg"
            >
              <span className="h-28 w-28 overflow-hidden rounded-full ring-1 ring-slate-100 transition group-hover:ring-brand-200">
                <img
                  src={c.img}
                  alt={c.name}
                  className="h-full w-full object-cover transition duration-300 group-hover:scale-110"
                />
              </span>
              <span className="text-lg font-semibold text-navy-700">{c.name}</span>
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

function BestSellers({ products, buttons }) {
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
          {products.map((p, i) => (
            <Reveal key={p.name + i} delay={(i % 4) * 80}>
              <ProductCard product={p} />
            </Reveal>
          ))}
        </div>
        {buttons?.bestSellersMenu !== false && (
          <div className="mt-10 text-center">
            <Link
              to="/menu"
              className="inline-block rounded-full bg-gradient-to-r from-navy-700 to-navy-800 px-8 py-3 text-sm font-semibold text-white shadow-md shadow-navy-800/30 transition hover:from-navy-800 hover:to-navy-900"
            >
              See full menu
            </Link>
          </div>
        )}
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
        {(product.calories != null || product.allergens?.length) && (
          <div className="mt-2 flex flex-wrap items-center gap-1.5">
            {product.calories != null && (
              <span className="inline-flex items-center rounded-full bg-navy-50 px-2 py-0.5 text-[10px] font-semibold text-navy-700">
                {product.calories} cal
              </span>
            )}
            {product.allergens?.map((a) => (
              <span
                key={a}
                className="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700"
                title={`Contains ${a}`}
              >
                {a}
              </span>
            ))}
          </div>
        )}
        <div className="mt-3 flex items-center justify-between">
          <span className="text-lg font-bold text-brand-600">{product.price}</span>
          <Link
            to={`/menu?add=${encodeURIComponent(product.name)}`}
            aria-label={`Add ${product.name} to cart`}
            className="flex h-9 w-9 items-center justify-center rounded-full bg-navy-800 text-white transition hover:bg-brand-600"
          >
            <PlusIcon className="h-5 w-5" />
          </Link>
        </div>
      </div>
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* Promo banner                                                        */
/* ------------------------------------------------------------------ */

function PromoBanner({ buttons }) {
  return (
    <Reveal as="section" className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <div className="relative min-h-[300px] rounded-3xl bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-12 text-white shadow-xl sm:px-12">
        <img
          src="/images/custom-cakes.png"
          alt="Custom tiered celebration cakes — wedding, themed, and princess designs"
          className="pointer-events-none absolute bottom-0 right-0 hidden w-[58%] max-w-[680px] drop-shadow-2xl sm:block"
        />
        <div className="relative z-10 max-w-md">
          <p className="font-script text-2xl text-white/90">Celebrate every moment</p>
          <h2 className="mt-2 text-3xl font-bold sm:text-4xl">
            Custom cakes for birthdays & special occasions
          </h2>
          <p className="mt-3 text-sm text-white/90">
            Make it unforgettable with a personalized cake, baked fresh and
            decorated just the way you want it.
          </p>
          {buttons?.promoOrder !== false && (
            <Link
              to="/register"
              className="mt-6 inline-block rounded-full bg-white px-7 py-3 text-sm font-semibold text-brand-600 shadow-md transition hover:bg-navy-50"
            >
              Order a custom cake
            </Link>
          )}
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

function Story({ buttons }) {
  return (
    <section id="about" className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <div className="grid items-center gap-10 lg:grid-cols-2">
        <Reveal direction="left" className="relative">
          <img
            src="/images/476076311_1024913309666031_7008467077346838017_n.jpg"
            alt="An assortment of freshly baked breads"
            className="h-80 w-full rounded-3xl bg-white object-contain shadow-xl"
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
          {(buttons?.storyStart !== false || buttons?.storyFindStore !== false) && (
            <div className="mt-6 flex flex-wrap gap-3">
              {buttons?.storyStart !== false && (
                <a
                  href="#best-sellers"
                  className="rounded-full bg-navy-800 px-6 py-3 text-sm font-semibold text-white transition hover:bg-navy-900"
                >
                  Start ordering
                </a>
              )}
              {buttons?.storyFindStore !== false && (
                <Link
                  to="/stores"
                  className="rounded-full border border-slate-300 px-6 py-3 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600"
                >
                  Find a store
                </Link>
              )}
            </div>
          )}
        </Reveal>
      </div>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* Store locator                                                       */
/* ------------------------------------------------------------------ */

function StoreLocator({ buttons }) {
  return (
    <section id="stores" className="bg-navy-900 py-16">
      <Reveal className="mx-auto max-w-3xl px-4 text-center sm:px-6">
        <span className="flex mx-auto h-14 w-14 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20">
          <StoreIcon className="h-7 w-7 text-brand-400" />
        </span>
        <h2 className="mt-5 text-3xl font-bold text-white sm:text-4xl">
          60+ stores, always near you
        </h2>
        <p className="mt-3 text-sm text-navy-50/80">
          Find your nearest branch or simply order online for delivery and pickup.
        </p>
        {buttons?.storeLocatorFind !== false && (
          <div className="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
            <input
              type="text"
              placeholder="Enter your city or area"
              className="w-full rounded-full border border-white/20 bg-white/5 px-5 py-3 text-sm text-white placeholder:text-navy-50/50 outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-400/30 sm:w-72"
            />
            <Link
              to="/stores"
              className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
            >
              Find a store
            </Link>
          </div>
        )}
      </Reveal>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* Newsletter                                                          */
/* ------------------------------------------------------------------ */

function Newsletter({ buttons }) {
  return (
    <section className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <Reveal className="rounded-3xl border border-brand-100 bg-brand-50 px-8 py-12 text-center sm:px-12">
        <h2 className="text-2xl font-bold text-navy-800 sm:text-3xl">
          Get sweet deals in your inbox 🍰
        </h2>
        <p className="mt-2 text-sm text-slate-600">
          Subscribe for exclusive promos, new treats, and special occasion offers.
        </p>
        {buttons?.newsletterSubscribe !== false && (
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
              className="rounded-full bg-gradient-to-r from-navy-700 to-navy-800 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-navy-800/30 transition hover:from-navy-800 hover:to-navy-900"
            >
              Subscribe
            </button>
          </form>
        )}
      </Reveal>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* Footer                                                              */
/* ------------------------------------------------------------------ */

// Social links. Facebook is live; the others are placeholders until provided.
const SOCIALS = [
  { label: 'Facebook', icon: 'f', href: 'https://www.facebook.com/bwsuperbakeshop' },
  { label: 'LinkedIn', icon: 'in', href: '#' },
  { label: 'X (Twitter)', icon: '𝕏', href: '#' },
]

// Footer link labels that map to real routes (others are placeholders).
const FOOTER_ROUTES = {
  Cakes: '/menu',
  Breads: '/menu',
  Pastries: '/menu',
  Delicacies: '/menu',
  Careers: '/careers',
  'Our Stores': '/stores',
}

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
            <img
              src="/images/logo (1).png"
              alt="bw Superbakeshop"
              className="h-12 w-auto"
            />
            <span className="font-brand text-2xl font-bold text-white">Superbakeshop</span>
          </div>
          <p className="mt-4 max-w-xs text-sm text-navy-50/70">
            Freshly baked. Made with love. Ordered with ease. Bringing bakeshop
            happiness to your doorstep.
          </p>
          <div className="mt-5 flex gap-3">
            {SOCIALS.map((s) => {
              const external = s.href !== '#'
              return (
                <a
                  key={s.label}
                  href={s.href}
                  aria-label={s.label}
                  {...(external && { target: '_blank', rel: 'noopener noreferrer' })}
                  className="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-sm font-semibold text-white transition hover:bg-brand-600"
                >
                  {s.icon}
                </a>
              )
            })}
          </div>
        </div>

        {cols.map((col) => (
          <div key={col.title}>
            <h4 className="text-sm font-semibold text-white">{col.title}</h4>
            <ul className="mt-4 space-y-2 text-sm">
              {col.links.map((l) => {
                const route = FOOTER_ROUTES[l]
                return (
                  <li key={l}>
                    {route ? (
                      <Link to={route} className="transition hover:text-brand-400">
                        {l}
                      </Link>
                    ) : (
                      <a href="#" className="transition hover:text-brand-400">
                        {l}
                      </a>
                    )}
                  </li>
                )
              })}
            </ul>
          </div>
        ))}
      </div>
      <div className="border-t border-white/10">
        <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-navy-50/60 sm:flex-row sm:px-6">
          <p>© {2026} BW Superbakeshop Ordering. All rights reserved.</p>
          
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
