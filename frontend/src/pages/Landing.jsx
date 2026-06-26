import { useEffect, useState } from 'react'
import { createPortal } from 'react-dom'
import { Link, useNavigate } from 'react-router-dom'
import Reveal, { StaticRevealContext } from '../components/Reveal'
import Carousel from '../components/Carousel'
import LazyImage from '../components/LazyImage'
import ConfirmModal from '../components/ConfirmModal'
import { useAuth } from '../context/AuthContext'
import { DEFAULT_CONTENT, buttonState, getCachedContent, getSiteContent } from '../lib/content'

// Disabled CTA styling + an onClick that swallows the click (and stops it from
// bubbling to any clickable parent, e.g. the promo banner). Spread onto a Link
// when its button state is 'disabled'.
const DISABLED_BTN_CLS = 'cursor-not-allowed opacity-60'
const swallowClick = (e) => {
  e.preventDefault()
  e.stopPropagation()
}

// Editor-set destinations may be an internal route (/menu) or a full URL.
const isExternal = (href) => /^https?:\/\//i.test(href || '')

// A CTA that links to an editor-configured internal route or external URL, with
// optional disabled (inert) state. Stops click propagation so it can sit inside
// a clickable parent (e.g. the promo banner).
function CtaLink({ to, disabled, className, children }) {
  const cls = `${className}${disabled ? ` ${DISABLED_BTN_CLS}` : ''}`
  const onClick = (e) => (disabled ? swallowClick(e) : e.stopPropagation())
  const common = {
    onClick,
    className: cls,
    'aria-disabled': disabled || undefined,
    tabIndex: disabled ? -1 : undefined,
  }
  if (isExternal(to)) {
    return (
      <a href={to} target="_blank" rel="noreferrer" {...common}>
        {children}
      </a>
    )
  }
  return (
    <Link to={to || '#'} {...common}>
      {children}
    </Link>
  )
}
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

  // When the editor disables the landing page, replace it with an "under
  // construction" screen (still shown in the editor preview so the toggle is
  // visible there). Admin/editor routes live elsewhere, so they stay reachable.
  if (content.maintenance?.enabled) {
    const uc = <UnderConstruction data={content.maintenance} social={content.social} />
    if (preview) {
      return <StaticRevealContext.Provider value={true}>{uc}</StaticRevealContext.Provider>
    }
    return uc
  }

  const page = (
    <div className="min-h-screen bg-white text-navy-800">
      <AnnouncementBar text={content.announcement} />
      <NavBar buttons={buttons} />
      <Hero banners={content.banners} />
      <WhatsNew heading={content.whatsNew} products={content.whatsNewProducts} />
      <BestSellers products={content.bestSellers} buttons={buttons} />
      <Categories items={content.categories} />
      <PromoBanner buttons={buttons} data={content.customCake} />
      <StoreLocator buttons={buttons} />
      <Newsletter buttons={buttons} data={content.newsletter} />
      <Footer social={content.social} footer={content.footer} />
    </div>
  )

  if (preview) {
    return <StaticRevealContext.Provider value={true}>{page}</StaticRevealContext.Provider>
  }
  return page
}

// Bakery treats that gently float in the backdrop. Position + per-item timing
// are tuned for variety; all are decorative (aria-hidden).
const FLOATING_TREATS = [
  { emoji: '🥐', cls: 'left-[8%] top-[18%] text-5xl', delay: '0s', dur: '6s' },
  { emoji: '🧁', cls: 'right-[10%] top-[22%] text-4xl', delay: '1.2s', dur: '7s' },
  { emoji: '🍞', cls: 'left-[14%] bottom-[16%] text-5xl', delay: '0.6s', dur: '6.5s' },
  { emoji: '🎂', cls: 'right-[14%] bottom-[20%] text-4xl', delay: '2s', dur: '8s' },
  { emoji: '🍩', cls: 'left-[44%] top-[9%] text-3xl', delay: '1.6s', dur: '7.5s' },
  { emoji: '🥖', cls: 'right-[6%] top-[55%] text-4xl', delay: '0.3s', dur: '6.8s' },
]

// Crumbs/chips flung off the logo on each hammer strike. --dx/--dy set the
// fling direction; the keyframe times it to the 1.2s strike cycle.
const DEBRIS = [
  { cls: 'bg-brand-400', dx: '-46px', dy: '-54px' },
  { cls: 'bg-amber-200', dx: '42px', dy: '-58px' },
  { cls: 'bg-white', dx: '-60px', dy: '-28px' },
  { cls: 'bg-brand-200', dx: '58px', dy: '-24px' },
  { cls: 'bg-amber-300', dx: '0px', dy: '-68px' },
]

// Shown in place of the landing page when maintenance mode is enabled.
function UnderConstruction({ data, social }) {
  const d = { ...DEFAULT_CONTENT.maintenance, ...(data || {}) }
  // Only the social networks that actually have a URL are shown here.
  const socials = SOCIAL_META.map((m) => ({ ...m, href: (social?.[m.key] || '').trim() })).filter(
    (s) => s.href,
  )
  return (
    <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-navy-900 px-6 text-center text-white animate-bg-pan">
      {/* drifting glow blobs behind the content */}
      <div
        aria-hidden="true"
        className="pointer-events-none absolute -left-24 top-8 h-72 w-72 rounded-full bg-brand-500/20 blur-3xl animate-drift"
      />
      <div
        aria-hidden="true"
        className="pointer-events-none absolute -right-20 bottom-8 h-80 w-80 rounded-full bg-brand-400/15 blur-3xl animate-drift-slow"
      />

      {/* floating bakery treats */}
      {FLOATING_TREATS.map((t, i) => (
        <span
          key={i}
          aria-hidden="true"
          style={{ animationDelay: t.delay, animationDuration: t.dur }}
          className={`pointer-events-none absolute select-none opacity-20 animate-bakery-float ${t.cls}`}
        >
          {t.emoji}
        </span>
      ))}

      <div className="relative z-10 flex flex-col items-center">
      <div className="animate-pop-in">
        <div className="relative animate-float">
          <img
            src="/images/logo (1).png"
            alt="bw Superbakeshop"
            className="h-44 w-auto drop-shadow-2xl animate-logo-hit sm:h-56"
          />
          {/* spark flashes where the hammer lands */}
          <span
            aria-hidden="true"
            className="pointer-events-none absolute left-1/2 top-1 -translate-x-1/2 text-4xl animate-spark"
          >
            💥
          </span>
          {/* crumbs flung off on each strike */}
          {DEBRIS.map((p, i) => (
            <span
              key={i}
              aria-hidden="true"
              style={{ '--dx': p.dx, '--dy': p.dy }}
              className={`pointer-events-none absolute left-1/2 top-2 h-2 w-2 -translate-x-1/2 rounded-full animate-debris ${p.cls}`}
            />
          ))}
          {/* hammer swings down onto the logo */}
          <span
            aria-hidden="true"
            className="pointer-events-none absolute -top-10 left-1/2 -translate-x-1/2 text-5xl animate-hammer sm:text-6xl"
          >
            🔨
          </span>
        </div>
      </div>
      <span
        className="mt-10 inline-block text-6xl animate-wiggle"
        role="img"
        aria-label="Under construction"
      >
        🚧
      </span>
      <h1 className="mt-8 font-brand text-4xl font-bold sm:text-5xl">{d.title}</h1>
      <p className="mt-4 max-w-md text-base leading-relaxed text-navy-50/70">{d.message}</p>

      {socials.length > 0 && (
        <div className="mt-8 flex gap-3">
          {socials.map((s) => (
            <a
              key={s.label}
              href={s.href}
              {...(isExternal(s.href) && { target: '_blank', rel: 'noopener noreferrer' })}
              aria-label={s.label}
              className="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-base font-semibold text-white transition hover:scale-110 hover:bg-brand-600"
            >
              {s.icon}
            </a>
          ))}
        </div>
      )}
      </div>
    </div>
  )
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
  const [confirmLogout, setConfirmLogout] = useState(false)
  const { user, isAdmin, isEditor, isHr, logout } = useAuth()
  const navigate = useNavigate()
  const signInState = buttonState(buttons, 'navSignIn')
  const orderState = buttonState(buttons, 'navOrder')
  const showSignIn = signInState !== 'off'
  const showOrder = orderState !== 'off'
  const signInOff = signInState === 'disabled'
  const orderOff = orderState === 'disabled'
  const links = [
    { label: 'Store', to: '/stores' },
    { label: 'Menu', to: '/menu' },
    { label: 'Partner with us', to: '/franchise' },
  ]

  // Where the signed-in account link goes, mirroring the post-login routing.
  const firstName = (user?.name || '').split(' ')[0] || 'Account'
  const accountRoute = isAdmin
    ? '/admin'
    : isEditor
      ? '/admin/content'
      : isHr
        ? '/admin/careers'
        : '/dashboard'

  const handleLogout = async () => {
    await logout()
    setOpen(false)
    navigate('/')
  }

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
          {user ? (
            <>
              <Link
                to={accountRoute}
                className="text-sm font-semibold text-navy-700 transition hover:text-brand-600"
              >
                Hi, {firstName}
              </Link>
              <button
                type="button"
                onClick={() => setConfirmLogout(true)}
                className="text-sm font-semibold text-navy-700 transition hover:text-brand-600"
              >
                Sign Out
              </button>
            </>
          ) : (
            showSignIn && (
              <Link
                to="/login"
                aria-disabled={signInOff}
                tabIndex={signInOff ? -1 : undefined}
                onClick={signInOff ? swallowClick : undefined}
                className={`text-sm font-semibold text-navy-700 transition hover:text-brand-600 ${signInOff ? DISABLED_BTN_CLS : ''}`}
              >
                Sign In
              </Link>
            )
          )}
          {showOrder && (
            <Link
              to="/menu"
              aria-disabled={orderOff}
              tabIndex={orderOff ? -1 : undefined}
              onClick={orderOff ? swallowClick : undefined}
              className={`rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 ${orderOff ? DISABLED_BTN_CLS : ''}`}
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
          {(user || showSignIn || showOrder) && (
            <div className="mt-3 flex gap-3">
              {user ? (
                <>
                  <Link
                    to={accountRoute}
                    onClick={() => setOpen(false)}
                    className="flex-1 rounded-full border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-navy-700"
                  >
                    Hi, {firstName}
                  </Link>
                  <button
                    type="button"
                    onClick={() => {
                      setOpen(false)
                      setConfirmLogout(true)
                    }}
                    className="flex-1 rounded-full border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-navy-700"
                  >
                    Sign Out
                  </button>
                </>
              ) : (
                showSignIn && (
                  <Link
                    to="/login"
                    aria-disabled={signInOff}
                    tabIndex={signInOff ? -1 : undefined}
                    onClick={signInOff ? swallowClick : undefined}
                    className={`flex-1 rounded-full border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-navy-700 ${signInOff ? DISABLED_BTN_CLS : ''}`}
                  >
                    Sign In
                  </Link>
                )
              )}
              {showOrder && (
                <Link
                  to="/menu"
                  aria-disabled={orderOff}
                  tabIndex={orderOff ? -1 : undefined}
                  onClick={orderOff ? swallowClick : undefined}
                  className={`flex-1 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white ${orderOff ? DISABLED_BTN_CLS : ''}`}
                >
                  Order Now
                </Link>
              )}
            </div>
          )}
        </div>
      )}

      {confirmLogout && (
        <ConfirmModal
          title="Log out?"
          message="You’ll be signed out of your account."
          confirmLabel="Log out"
          loadingLabel="Logging out"
          onConfirm={handleLogout}
          onCancel={() => setConfirmLogout(false)}
        />
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
        arrows={false}
        dots={false}
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
                  loading="lazy"
                  decoding="async"
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
        {(() => {
          const state = buttonState(buttons, 'bestSellersMenu')
          if (state === 'off') return null
          const off = state === 'disabled'
          return (
            <div className="mt-10 text-center">
              <Link
                to={`/menu?category=${encodeURIComponent('Best Sellers')}`}
                aria-disabled={off}
                tabIndex={off ? -1 : undefined}
                onClick={off ? swallowClick : undefined}
                className={`inline-block rounded-full bg-gradient-to-r from-navy-700 to-navy-800 px-8 py-3 text-sm font-semibold text-white shadow-md shadow-navy-800/30 transition hover:from-navy-800 hover:to-navy-900 ${off ? DISABLED_BTN_CLS : ''}`}
              >
                See Best Sellers
              </Link>
            </div>
          )
        })()}
      </div>
    </section>
  )
}

function ProductCard({ product }) {
  const [open, setOpen] = useState(false)
  return (
    <>
      <div
        role="button"
        tabIndex={0}
        onClick={() => setOpen(true)}
        onKeyDown={(e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault()
            setOpen(true)
          }
        }}
        aria-label={`View ${product.name}`}
        className="group cursor-pointer overflow-hidden rounded-2xl bg-white shadow-sm outline-none transition hover:shadow-xl focus-visible:ring-2 focus-visible:ring-brand-500"
      >
        <div className="relative overflow-hidden">
          <LazyImage
            src={product.img}
            alt={product.name}
            wrapperClassName="h-40 w-full transition duration-300 group-hover:scale-105"
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
              onClick={(e) => e.stopPropagation()}
              aria-label={`Add ${product.name} to cart`}
              className="flex h-9 w-9 items-center justify-center rounded-full bg-navy-800 text-white transition hover:bg-brand-600"
            >
              <PlusIcon className="h-5 w-5" />
            </Link>
          </div>
        </div>
      </div>
      {open && <ProductModal product={product} onClose={() => setOpen(false)} />}
    </>
  )
}

// Product detail modal opened by clicking a ProductCard. Shows the image,
// description, allergens, price and an "Order now" deep-link into the menu.
function ProductModal({ product, onClose }) {
  useEffect(() => {
    const onKey = (e) => e.key === 'Escape' && onClose()
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [onClose])

  if (typeof document === 'undefined') return null

  return createPortal(
    <div
      className="fixed inset-0 z-[60] flex items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm"
      onClick={onClose}
      role="dialog"
      aria-modal="true"
      aria-label={product.name}
    >
      <div
        className="relative max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-3xl bg-white shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        <button
          type="button"
          onClick={onClose}
          aria-label="Close"
          className="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-navy-800 shadow transition hover:bg-white"
        >
          <CloseIcon className="h-5 w-5" />
        </button>
        <div className="grid md:grid-cols-2">
          <LazyImage
            src={product.img}
            alt={product.name}
            wrapperClassName="h-64 w-full md:h-full md:min-h-[28rem]"
          />
          <div className="flex flex-col p-8 sm:p-10">
            {product.tag && (
              <span className="w-fit rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-600">
                {product.tag}
              </span>
            )}
            <h3 className="mt-4 text-3xl font-bold text-navy-800">{product.name}</h3>
            {product.desc && (
              <p className="mt-4 text-base leading-relaxed text-slate-600">{product.desc}</p>
            )}
            {product.calories != null && (
              <span className="mt-4 w-fit rounded-full bg-navy-50 px-3 py-1 text-sm font-semibold text-navy-700">
                {product.calories} cal
              </span>
            )}
            {product.allergens?.length > 0 && (
              <div className="mt-6">
                <p className="text-xs font-semibold uppercase tracking-wide text-navy-700">
                  Allergens
                </p>
                <div className="mt-2 flex flex-wrap gap-2">
                  {product.allergens.map((a) => (
                    <span
                      key={a}
                      className="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-sm font-medium text-amber-700"
                    >
                      {a}
                    </span>
                  ))}
                </div>
              </div>
            )}
            <div className="mt-auto flex flex-wrap items-center justify-between gap-4 pt-8">
              <span className="text-3xl font-bold text-brand-600">{product.price}</span>
              <Link
                to={`/menu?add=${encodeURIComponent(product.name)}`}
                className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-3.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
              >
                Order now
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>,
    document.body,
  )
}

/* ------------------------------------------------------------------ */
/* Promo banner                                                        */
/* ------------------------------------------------------------------ */

function PromoBanner({ buttons, data }) {
  const navigate = useNavigate()
  const c = { ...DEFAULT_CONTENT.customCake, ...(data || {}) }
  const goToBanner = () => {
    if (isExternal(c.bannerLink)) window.open(c.bannerLink, '_blank', 'noopener')
    else navigate(c.bannerLink || '/menu')
  }
  return (
    <Reveal as="section" className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <div
        id="custom-cake"
        role="button"
        tabIndex={0}
        onClick={goToBanner}
        onKeyDown={(e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault()
            goToBanner()
          }
        }}
        aria-label="Order custom cakes"
        className="relative min-h-[300px] cursor-pointer rounded-3xl bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-12 text-white shadow-xl outline-none transition hover:shadow-2xl focus-visible:ring-2 focus-visible:ring-white/70 sm:px-12"
      >
        {c.image && (
          <img
            src={c.image}
            alt={c.alt}
            loading="lazy"
            decoding="async"
            className="pointer-events-none absolute bottom-0 right-0 hidden w-[58%] max-w-[680px] drop-shadow-2xl sm:block"
          />
        )}
        <div className="relative z-10 max-w-md">
          {c.eyebrow && <p className="font-script text-2xl text-white/90">{c.eyebrow}</p>}
          <h2 className="mt-2 text-3xl font-bold sm:text-4xl">{c.title}</h2>
          {c.subtitle && <p className="mt-3 text-sm text-white/90">{c.subtitle}</p>}
          {(() => {
            const state = buttonState(buttons, 'promoOrder')
            if (state === 'off') return null
            return (
              <CtaLink
                to={c.buttonLink || '/register'}
                disabled={state === 'disabled'}
                className="mt-6 inline-block rounded-full bg-white px-7 py-3 text-sm font-semibold text-brand-600 shadow-md transition hover:bg-navy-50"
              >
                {c.buttonLabel || 'Order a custom cake'}
              </CtaLink>
            )
          })()}
        </div>
      </div>
    </Reveal>
  )
}

/* ------------------------------------------------------------------ */
/* What's new                                                          */
/* ------------------------------------------------------------------ */

// Curated "What's New" cards, managed in the Site Editor (heading +
// whatsNewProducts list). Renders nothing when no cards are configured.
function WhatsNew({ heading, products = [] }) {
  const h = heading || DEFAULT_CONTENT.whatsNew

  if (!products.length) return null

  return (
    <section id="whats-new" className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <Reveal>
        <SectionHeading eyebrow={h.eyebrow} title={h.title} subtitle={h.subtitle} />
      </Reveal>
      <div className="mt-10 grid grid-cols-2 gap-5 md:grid-cols-4">
        {products.map((p, i) => (
          <Reveal key={p.name + i} delay={(i % 4) * 80}>
            <ProductCard product={p} />
          </Reveal>
        ))}
      </div>
      <Reveal className="mt-10 text-center">
        <Link
          to={`/menu?category=${encodeURIComponent("What's New")}`}
          className="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
        >
          See What&apos;s New
          <span aria-hidden="true">→</span>
        </Link>
      </Reveal>
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
        {(() => {
          const state = buttonState(buttons, 'storeLocatorFind')
          if (state === 'off') return null
          const off = state === 'disabled'
          return (
            <div className={`mt-7 flex flex-col justify-center gap-3 sm:flex-row ${off ? DISABLED_BTN_CLS : ''}`}>
              <input
                type="text"
                placeholder="Enter your city or area"
                disabled={off}
                className="w-full rounded-full border border-white/20 bg-white/5 px-5 py-3 text-sm text-white placeholder:text-navy-50/50 outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-400/30 disabled:cursor-not-allowed sm:w-72"
              />
              <Link
                to="/stores"
                aria-disabled={off}
                tabIndex={off ? -1 : undefined}
                onClick={off ? swallowClick : undefined}
                className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
              >
                Find a store
              </Link>
            </div>
          )
        })()}
      </Reveal>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* Newsletter                                                          */
/* ------------------------------------------------------------------ */

function Newsletter({ buttons, data }) {
  const n = { ...DEFAULT_CONTENT.newsletter, ...(data || {}) }
  return (
    <section id="newsletter" className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <Reveal className="rounded-3xl border border-brand-100 bg-brand-50 px-8 py-12 text-center sm:px-12">
        <h2 className="text-2xl font-bold text-navy-800 sm:text-3xl">{n.title}</h2>
        {n.subtitle && <p className="mt-2 text-sm text-slate-600">{n.subtitle}</p>}
        {(() => {
          const state = buttonState(buttons, 'newsletterSubscribe')
          if (state === 'off') return null
          const off = state === 'disabled'
          return (
            <form
              onSubmit={(e) => e.preventDefault()}
              className={`mx-auto mt-6 flex max-w-md flex-col gap-3 sm:flex-row ${off ? DISABLED_BTN_CLS : ''}`}
            >
              <input
                type="email"
                required
                disabled={off}
                placeholder={n.placeholder}
                className="w-full rounded-full border border-slate-300 px-5 py-3 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed"
              />
              <button
                type="submit"
                disabled={off}
                className="rounded-full bg-gradient-to-r from-navy-700 to-navy-800 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-navy-800/30 transition hover:from-navy-800 hover:to-navy-900 disabled:cursor-not-allowed"
              >
                {n.buttonLabel}
              </button>
            </form>
          )
        })()}
      </Reveal>
    </section>
  )
}

/* ------------------------------------------------------------------ */
/* Footer                                                              */
/* ------------------------------------------------------------------ */

// Social icon metadata; the actual URLs come from the editable site content
// (content.social), set in the Site Editor → Social Links section.
const SOCIAL_META = [
  { key: 'facebook', label: 'Facebook', icon: 'f' },
  { key: 'linkedin', label: 'LinkedIn', icon: 'in' },
  { key: 'x', label: 'X (Twitter)', icon: '𝕏' },
]

function Footer({ social, footer }) {
  const f = { ...DEFAULT_CONTENT.footer, ...(footer || {}) }
  // All three social icons always render; a network left blank in the editor is
  // inert (links nowhere) rather than hidden.
  const socials = SOCIAL_META.map((m) => ({ ...m, href: (social?.[m.key] || '').trim() }))
  const columns = f.columns || []
  return (
    <footer id="site-footer" className="bg-navy-900 text-navy-50/80">
      <div className="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.5fr_2.5fr]">
        <div>
          <div className="flex items-center gap-2">
            {f.logo && <img src={f.logo} alt="bw Superbakeshop" className="h-12 w-auto" />}
            {f.brand && (
              <span className="font-brand text-2xl font-bold text-white">{f.brand}</span>
            )}
          </div>
          <p className="mt-4 max-w-xs text-sm text-navy-50/70">{f.description}</p>
          <div className="mt-5 flex gap-3">
            {socials.map((s) => {
              const external = isExternal(s.href)
              return (
                <a
                  key={s.label}
                  href={s.href || '#'}
                  aria-label={s.label}
                  {...(external && { target: '_blank', rel: 'noopener noreferrer' })}
                  {...(!s.href && { onClick: swallowClick, 'aria-disabled': true })}
                  className="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-sm font-semibold text-white transition hover:bg-brand-600"
                >
                  {s.icon}
                </a>
              )
            })}
          </div>
        </div>

        <div className="grid grid-cols-2 gap-8 sm:grid-cols-3">
          {columns.map((col, ci) => (
            <div key={ci}>
              <h4 className="text-sm font-semibold text-white">{col.title}</h4>
              <ul className="mt-4 space-y-2 text-sm">
                {(col.links || []).map((l, li) => (
                  <li key={li}>
                    <FooterLink label={l.label} url={l.url} />
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>
      <div className="border-t border-white/10">
        <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-navy-50/60 sm:flex-row sm:px-6">
          <p>{f.copyright}</p>
        </div>
      </div>
    </footer>
  )
}

// A single footer column link. The editor-set url may be an internal route
// (/menu), a full external URL, or blank — a blank url renders an inert
// placeholder (links nowhere) rather than being hidden.
function FooterLink({ label, url }) {
  const cls = 'transition hover:text-brand-600'
  if (!url) {
    return (
      <a href="#" onClick={swallowClick} aria-disabled className={cls}>
        {label}
      </a>
    )
  }
  if (isExternal(url)) {
    return (
      <a href={url} target="_blank" rel="noreferrer" className={cls}>
        {label}
      </a>
    )
  }
  return (
    <Link to={url} className={cls}>
      {label}
    </Link>
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

function CloseIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <line x1="6" y1="6" x2="18" y2="18" />
      <line x1="6" y1="18" x2="18" y2="6" />
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
