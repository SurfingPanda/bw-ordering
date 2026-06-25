import { useEffect, useMemo, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import ConfirmModal from '../components/ConfirmModal'
import {
  fetchMenuProducts,
  getCachedContent,
  getSiteContent,
  isButtonVisible,
  isButtonDisabled,
} from '../lib/content'
import { fetchActiveVouchers } from '../lib/vouchers'
import { useSeo } from '../lib/seo'

// Shopping / menu page — browse foods by category and build a cart.
// Cart state is local for now; "Proceed to checkout" sends guests to sign in.

const peso = (n) =>
  `₱${n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

const VAT_RATE = 0.12 // 12% Philippine VAT
const DELIVERY_FEE = 79
const FREE_DELIVERY_MIN = 1000

// Which categories pair well together — drives the "Best paired with" cart
// suggestions. Listed in priority order; a drink almost always pairs.
const PAIRINGS = {
  Cakes: ['Beverages', 'Desserts'],
  Cupcakes: ['Beverages', 'Cookies'],
  Cookies: ['Beverages', 'Desserts'],
  Pastries: ['Beverages', 'Breads'],
  Breads: ['Beverages', 'Sandwiches'],
  Donuts: ['Beverages', 'Cookies'],
  Pies: ['Beverages', 'Desserts'],
  Desserts: ['Beverages', 'Cakes'],
  Sandwiches: ['Beverages', 'Breads'],
  Beverages: ['Pastries', 'Cookies', 'Cakes'],
}

// Up to 3 products to suggest, given the cart and the full menu. Prefers items
// from paired categories, then fills with anything else not already added.
function pairedSuggestions(lines, menu, limit = 3) {
  const inCart = new Set(lines.map((l) => l.product.id))
  const wanted = []
  for (const { product } of lines) {
    for (const cat of PAIRINGS[product.category] || []) {
      if (!wanted.includes(cat)) wanted.push(cat)
    }
  }
  const pick = []
  const take = (predicate) => {
    for (const p of menu) {
      if (pick.length >= limit) break
      if (inCart.has(p.id) || pick.includes(p)) continue
      if (predicate(p)) pick.push(p)
    }
  }
  for (const cat of wanted) take((p) => p.category === cat)
  take(() => true) // backfill so we always show something
  return pick.slice(0, limit)
}

export default function Menu({ previewProducts, previewContent, preview = false }) {
  useSeo('/menu', !preview)
  const [searchParams, setSearchParams] = useSearchParams()
  const [fetchedMenu, setFetchedMenu] = useState([])
  // In the Site Editor preview, render the editor's live (unsaved) products.
  const menu = previewProducts ?? fetchedMenu
  const [menuLoading, setMenuLoading] = useState(!previewProducts)
  const [active, setActive] = useState('All')
  const [tag, setTag] = useState('all') // in-category filter: all | new | best_seller | bundle
  const [query, setQuery] = useState('')
  // Lazy-init from the persisted cart so it survives /checkout → "Add more
  // items" → /menu. try/catch returns {} on the server (no localStorage),
  // matching the repo's SSR-safe cache pattern. The editor preview starts empty.
  const [cart, setCart] = useState(() => {
    if (preview) return {}
    try {
      return JSON.parse(localStorage.getItem('bw_cart') || '{}') || {}
    } catch {
      return {}
    }
  }) // { [id]: qty }
  const [cartOpen, setCartOpen] = useState(false) // mobile cart drawer

  // Site content drives the "Proceed to checkout" button state (visible /
  // disabled / hidden) and the per-category badge images, set in the Site
  // Editor. Seed from the synchronous cache so first paint is correct, then
  // refresh from the API. In the editor preview `previewContent` takes over so
  // unsaved edits show live (mirrors how `menu` uses `previewProducts`).
  const [fetchedContent, setFetchedContent] = useState(() => getCachedContent())
  useEffect(() => {
    if (previewContent) return
    getSiteContent().then(setFetchedContent).catch(() => {})
  }, [previewContent])
  const content = previewContent ?? fetchedContent
  const checkoutVisible = isButtonVisible(content, 'menuCheckout')
  const checkoutDisabled = isButtonDisabled(content, 'menuCheckout')

  // Load the menu from the products table (the same source the order-pricing
  // trigger trusts). Once loaded, honor a /menu?add=<name> deep link from the
  // landing "Best Sellers" by adding that product and filtering to its category;
  // otherwise default to the "What's New" tab when any new products exist.
  useEffect(() => {
    if (previewProducts) return // preview uses the editor's products, no fetch
    let alive = true
    fetchMenuProducts()
      .then((rows) => {
        if (!alive) return
        setFetchedMenu(rows)
        setMenuLoading(false)
        const name = searchParams.get('add')
        if (name) {
          const product = rows.find((p) => p.name.toLowerCase() === name.toLowerCase())
          if (product) {
            setCart((c) => ({ ...c, [product.id]: (c[product.id] || 0) + 1 }))
            setActive(product.category)
          }
          searchParams.delete('add')
          setSearchParams(searchParams, { replace: true })
        } else if (rows.some((p) => p.status === 'new')) {
          setActive("What's New")
        }
      })
      .catch(() => alive && setMenuLoading(false))
    return () => {
      alive = false
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  // Persist the cart on every change so it survives navigating to /checkout
  // and back ("Add more items", "Back to Cart"). Skips the first run so the
  // initial value isn't needlessly rewritten.
  const firstSave = useRef(true)
  useEffect(() => {
    if (preview) return // don't touch the real cart from the editor preview
    if (firstSave.current) {
      firstSave.current = false
      return
    }
    try {
      localStorage.setItem('bw_cart', JSON.stringify(cart))
    } catch {
      // best-effort
    }
  }, [cart, preview])

  const inActiveCategory = (p) =>
    active === 'All'
      ? true
      : active === "What's New"
        ? p.status === 'new'
        : active === 'Best Sellers'
          ? p.status === 'best_seller'
          : p.category === active

  // Status filters available within the current category (only show chips that
  // actually have matching products).
  const availableTags = useMemo(() => {
    const present = new Set(menu.filter(inActiveCategory).map((p) => p.status))
    return TAGS.filter((t) => present.has(t.key))
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [menu, active])

  const visible = useMemo(() => {
    const q = query.trim().toLowerCase()
    return menu.filter((p) => {
      const matchesTag = tag === 'all' || p.status === tag
      const matches =
        !q || p.name.toLowerCase().includes(q) || (p.desc || '').toLowerCase().includes(q)
      return inActiveCategory(p) && matchesTag && matches
    })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [menu, active, tag, query])

  const lines = useMemo(
    () =>
      Object.entries(cart)
        .map(([id, qty]) => ({ product: menu.find((p) => p.id === id), qty }))
        .filter((l) => l.product),
    [cart, menu],
  )

  // Category tabs are derived from the products themselves, plus an "All" tab.
  // Each badge uses the editor-set category image (content.menuCategoryImages)
  // when present, otherwise falls back to the first product's photo.
  const categoryImages = content?.menuCategoryImages
  const categories = useMemo(() => {
    const imgs = categoryImages || {}
    const seen = new Map()
    for (const p of menu) {
      if (p.category && !seen.has(p.category)) seen.set(p.category, p.img)
    }
    // Status-based "smart" tabs — only shown when matching products exist.
    // These aren't food categories, so they render an icon on a colored badge
    // instead of a product photo.
    const hasNew = menu.some((p) => p.status === 'new')
    const hasBest = menu.some((p) => p.status === 'best_seller')
    return [
      ...(hasNew ? [{ name: "What's New", icon: 'new' }] : []),
      ...(hasBest ? [{ name: 'Best Sellers', icon: 'best' }] : []),
      ...[...seen].map(([name, img]) => ({ name, img: imgs[name] || img })),
    ]
  }, [menu, categoryImages])

  const itemCount = lines.reduce((sum, l) => sum + l.qty, 0)
  const subtotal = lines.reduce((sum, l) => sum + l.product.price * l.qty, 0)

  const add = (id) => setCart((c) => ({ ...c, [id]: (c[id] || 0) + 1 }))
  const dec = (id) =>
    setCart((c) => {
      const next = { ...c }
      if ((next[id] || 0) <= 1) delete next[id]
      else next[id] -= 1
      return next
    })
  const remove = (id) =>
    setCart((c) => {
      const next = { ...c }
      delete next[id]
      return next
    })

  const navigate = useNavigate()
  // Hand the cart off to the /checkout page via localStorage (survives refresh)
  // and navigate there — the order is actually placed from the checkout page.
  const checkout = (summary) => {
    try {
      localStorage.setItem('bw_checkout', JSON.stringify(summary))
    } catch {
      // best-effort
    }
    navigate('/checkout')
  }

  return (
    <div className="animate-page-in flex min-h-screen flex-col bg-navy-50/40 text-navy-800 lg:flex-row">
      {/* left — full-height categories sidebar (with logo on top) */}
      <CategorySidebar
        active={active}
        onChange={(name) => {
          setActive(name)
          setTag('all')
        }}
        categories={categories}
      />

      {/* right — header + content */}
      <div className="flex min-w-0 flex-1 flex-col">
        <MenuHeader itemCount={itemCount} />

        <div className="flex flex-1 flex-col lg:flex-row">
        {/* center — products */}
        <main className="flex-1 px-4 py-6 sm:px-6">
          <div className="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h1 className="text-2xl font-bold text-navy-800">
                {active === 'All' ? 'Our Menu' : active}
              </h1>
              <p className="mt-1 text-sm text-slate-500">
                Tap a treat to add it to your cart.
              </p>
            </div>
            <div className="relative w-full sm:w-72">
              <SearchIcon className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
              <input
                type="search"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Search treats…"
                className="w-full rounded-full border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
              />
            </div>
          </div>

          {availableTags.length > 0 && (
            <div className="mb-5 flex flex-wrap gap-2">
              {[{ key: 'all', label: 'All', icon: '' }, ...availableTags].map((t) => (
                <button
                  key={t.key}
                  type="button"
                  onClick={() => setTag(t.key)}
                  className={`rounded-full px-4 py-1.5 text-sm font-semibold transition ${
                    tag === t.key
                      ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md shadow-brand-500/30'
                      : 'bg-white text-navy-700 ring-1 ring-slate-200 hover:bg-slate-50'
                  }`}
                >
                  {t.icon ? `${t.icon} ` : ''}
                  {t.label}
                </button>
              ))}
            </div>
          )}

          {menuLoading ? (
            <div className="py-16 text-center text-sm text-slate-500">Loading menu…</div>
          ) : visible.length === 0 ? (
            <div className="py-16 text-center">
              <div className="text-5xl">🔍</div>
              <p className="mt-3 text-sm text-slate-500">
                No treats found{query && ` for “${query}”`}.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
              {visible.map((p) => (
                <MenuCard
                  key={p.id || p._key}
                  product={p}
                  qty={cart[p.id] || 0}
                  onAdd={add}
                  onDec={dec}
                />
              ))}
            </div>
          )}
        </main>

        {/* right — cart (desktop) */}
        <div className="hidden bg-white lg:block lg:w-96 lg:shrink-0">
          <div className="lg:sticky lg:top-16 lg:h-[calc(100vh-4rem)]">
            <Cart
              lines={lines}
              menu={menu}
              subtotal={subtotal}
              itemCount={itemCount}
              onInc={add}
              onDec={dec}
              onRemove={remove}
              onCheckout={checkout}
              checkoutVisible={checkoutVisible}
              checkoutDisabled={checkoutDisabled}
            />
          </div>
        </div>
        </div>
      </div>

      {/* floating cart button (mobile) */}
      <button
        type="button"
        onClick={() => setCartOpen(true)}
        aria-label="Open cart"
        className="fixed bottom-5 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-lg shadow-brand-500/40 transition active:scale-95 lg:hidden"
      >
        <CartIcon className="h-6 w-6" />
        {itemCount > 0 && (
          <span className="absolute -right-1 -top-1 flex h-6 min-w-6 items-center justify-center rounded-full bg-navy-900 px-1 text-xs font-bold text-white ring-2 ring-white">
            {itemCount}
          </span>
        )}
      </button>

      {/* cart drawer (mobile) */}
      {cartOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div
            className="absolute inset-0 bg-black/40"
            onClick={() => setCartOpen(false)}
          />
          <div className="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-2xl">
            <button
              type="button"
              onClick={() => setCartOpen(false)}
              aria-label="Close cart"
              className="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-navy-50 text-navy-800 transition hover:bg-navy-100"
            >
              <CloseIcon className="h-5 w-5" />
            </button>
            <Cart
              lines={lines}
              menu={menu}
              subtotal={subtotal}
              itemCount={itemCount}
              onInc={add}
              onDec={dec}
              onRemove={remove}
              onCheckout={checkout}
              checkoutVisible={checkoutVisible}
              checkoutDisabled={checkoutDisabled}
            />
          </div>
        </div>
      )}
    </div>
  )
}

function CategorySidebar({ active, onChange, categories }) {
  return (
    <aside className="sticky top-0 z-30 bg-navy-900 lg:flex lg:h-screen lg:w-60 lg:shrink-0 lg:flex-col">
      {/* logo — sits at the top of the sidebar */}
      <Link
        to="/"
        className="hidden h-16 shrink-0 items-center justify-center px-4 lg:flex"
      >
        <img src="/images/logo (1).png" alt="bw Superbakeshop" className="h-11 w-auto" />
      </Link>

      {/* categories — fill the remaining height. Scrollbar is hidden (still
          scrollable by wheel/touch) so a long list stays tidy, and items are
          compact so most fit without scrolling. */}
      <nav className="flex gap-2 overflow-x-auto p-2 [-ms-overflow-style:none] [scrollbar-width:none] lg:min-h-0 lg:flex-1 lg:flex-col lg:justify-evenly lg:gap-1 lg:overflow-x-hidden lg:overflow-y-auto [&::-webkit-scrollbar]:hidden">
        {categories.map((c) => {
          const isActive = active === c.name
          return (
            <button
              key={c.name}
              type="button"
              onClick={() => onChange(c.name)}
              className={`flex shrink-0 items-center gap-3 rounded-full p-1.5 pr-5 text-sm font-semibold transition lg:w-full ${
                isActive
                  ? 'bg-white text-navy-900 shadow-lg ring-2 ring-brand-500'
                  : 'text-white hover:bg-white/10'
              }`}
            >
              {c.icon ? (
                <span
                  className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-white shadow ring-1 ring-black/5 ${
                    c.icon === 'new'
                      ? 'bg-gradient-to-br from-emerald-400 to-teal-500'
                      : 'bg-gradient-to-br from-amber-400 to-orange-500'
                  }`}
                >
                  {c.icon === 'new' ? (
                    <SparkleIcon className="h-5 w-5" />
                  ) : (
                    <TrophyIcon className="h-5 w-5" />
                  )}
                </span>
              ) : (
                <span className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white shadow ring-1 ring-black/5">
                  <img src={c.img} alt="" loading="lazy" decoding="async" className="h-full w-full object-cover" />
                </span>
              )}
              {c.name}
            </button>
          )
        })}
      </nav>
    </aside>
  )
}

function MenuHeader({ itemCount }) {
  const { user, isAdmin, isEditor, logout } = useAuth()
  const navigate = useNavigate()
  const [confirmLogout, setConfirmLogout] = useState(false)

  const handleLogout = async () => {
    await logout()
    navigate('/')
  }

  return (
    <header className="z-20 border-b border-slate-100 bg-white/90 backdrop-blur lg:sticky lg:top-0">
      <div className="flex h-16 items-center justify-between px-4 sm:px-6">
        <h2 className="text-lg font-bold text-navy-800">Order Online</h2>
        <div className="flex items-center gap-4">
          {user ? (
            <>
              {isAdmin && (
                <Link
                  to="/admin"
                  className="rounded-full bg-navy-800 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-600"
                >
                  Admin
                </Link>
              )}
              {!isAdmin && isEditor && (
                <Link
                  to="/admin/content"
                  className="rounded-full bg-navy-800 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-600"
                >
                  Edit Site
                </Link>
              )}
              <Link
                to="/my-orders"
                className="text-sm font-medium text-navy-700 transition hover:text-brand-600"
              >
                My Orders
              </Link>
              <span className="hidden text-sm text-navy-700 sm:block">
                Hi, <span className="font-semibold">{user.name}</span>
              </span>
              <button
                type="button"
                onClick={() => setConfirmLogout(true)}
                className="text-sm font-medium text-navy-700 transition hover:text-brand-600"
              >
                Logout
              </button>
            </>
          ) : (
            <Link
              to="/"
              className="text-sm font-medium text-navy-700 transition hover:text-brand-600"
            >
              ← Back to home
            </Link>
          )}
          {/* cart icon shown on desktop only; mobile uses the floating button */}
          <div className="relative hidden h-10 w-10 items-center justify-center rounded-full bg-navy-50 text-navy-800 lg:flex">
            <CartIcon className="h-5 w-5" />
            {itemCount > 0 && (
              <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-500 px-1 text-[0.65rem] font-bold text-white">
                {itemCount}
              </span>
            )}
          </div>
        </div>
      </div>

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

const STATUS_LABEL = {
  new: 'New',
  best_seller: 'Best Seller',
  bundle: 'Bundle',
  sold_out: 'Sold out',
}

// In-category status filters (chips above the product grid).
const TAGS = [
  { key: 'new', label: 'New', icon: '✨' },
  { key: 'best_seller', label: 'Best Seller', icon: '🏆' },
  { key: 'bundle', label: 'Bundle', icon: '🎁' },
]

function MenuCard({ product, qty, onAdd, onDec }) {
  const [open, setOpen] = useState(false)
  const soldOut = product.status === 'sold_out'
  const onSale = product.originalPrice != null && product.originalPrice > product.price
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
        className="group flex cursor-pointer flex-col overflow-hidden rounded-2xl bg-white shadow-sm outline-none transition hover:shadow-xl focus-visible:ring-2 focus-visible:ring-brand-500"
      >
        <div className="relative overflow-hidden">
          <img
            src={product.img}
            alt={product.name}
            loading="lazy"
            decoding="async"
            className={`h-56 w-full object-cover transition duration-300 group-hover:scale-105 ${
              soldOut ? 'opacity-60 grayscale' : ''
            }`}
          />
          {product.status && (
            <span
              className={`absolute left-2 top-2 rounded-full px-2 py-0.5 text-[0.6rem] font-semibold uppercase tracking-wide ${
                soldOut ? 'bg-slate-700/90 text-white' : 'bg-white/90 text-brand-600'
              }`}
            >
              {STATUS_LABEL[product.status] || product.status}
            </span>
          )}
        </div>
        <div className="flex flex-1 flex-col p-4">
          <h3 className="text-sm font-semibold text-navy-800">{product.name}</h3>
          <p className="mt-1 line-clamp-2 text-xs text-slate-500">{product.desc}</p>
          {(product.calories != null || product.features?.length > 0) && (
            <div className="mt-2 flex flex-wrap items-center gap-1.5">
              {product.calories != null && (
                <span className="inline-flex items-center rounded-full bg-navy-50 px-2 py-0.5 text-[10px] font-semibold text-navy-700">
                  {product.calories} cal
                </span>
              )}
              {product.features?.map((a) => (
                <span
                  key={a}
                  title={`Contains ${a}`}
                  className="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700"
                >
                  {a}
                </span>
              ))}
            </div>
          )}
          <div className="mt-auto flex items-center justify-between pt-3">
            <span className="flex items-baseline gap-1.5">
              <span className="text-lg font-bold text-brand-600">{peso(product.price)}</span>
              {onSale && (
                <span className="text-xs text-slate-400 line-through">{peso(product.originalPrice)}</span>
              )}
            </span>
            {soldOut ? (
              <span className="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-400">
                Sold out
              </span>
            ) : qty === 0 ? (
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation()
                  onAdd(product.id)
                }}
                className="rounded-full bg-navy-800 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-600"
              >
                Add
              </button>
            ) : (
              <div className="flex items-center gap-2">
                <QtyButton
                  onClick={(e) => {
                    e.stopPropagation()
                    onDec(product.id)
                  }}
                  label="Decrease"
                >
                  −
                </QtyButton>
                <span className="w-5 text-center text-sm font-semibold">{qty}</span>
                <QtyButton
                  onClick={(e) => {
                    e.stopPropagation()
                    onAdd(product.id)
                  }}
                  label="Increase"
                >
                  +
                </QtyButton>
              </div>
            )}
          </div>
        </div>
      </div>
      {open && (
        <ProductMenuModal
          product={product}
          qty={qty}
          onAdd={onAdd}
          onDec={onDec}
          onClose={() => setOpen(false)}
        />
      )}
    </>
  )
}

// Product detail modal opened by clicking a MenuCard.
function ProductMenuModal({ product, qty, onAdd, onDec, onClose }) {
  useEffect(() => {
    const onKey = (e) => e.key === 'Escape' && onClose()
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [onClose])

  const soldOut = product.status === 'sold_out'
  const onSale = product.originalPrice != null && product.originalPrice > product.price

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
        className="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white shadow-2xl"
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
          <img
            src={product.img}
            alt={product.name}
            loading="lazy"
            decoding="async"
            className={`h-64 w-full object-cover md:h-full md:min-h-[24rem] ${
              soldOut ? 'opacity-60 grayscale' : ''
            }`}
          />
          <div className="flex flex-col p-8">
            {product.status && (
              <span className="w-fit rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-600">
                {STATUS_LABEL[product.status] || product.status}
              </span>
            )}
            <h3 className="mt-3 text-2xl font-bold text-navy-800">{product.name}</h3>
            {product.desc && (
              <p className="mt-3 text-sm leading-relaxed text-slate-600">{product.desc}</p>
            )}
            {product.calories != null && (
              <span className="mt-4 w-fit rounded-full bg-navy-50 px-3 py-1 text-sm font-semibold text-navy-700">
                {product.calories} cal
              </span>
            )}
            {product.features?.length > 0 && (
              <div className="mt-4">
                <p className="text-xs font-semibold uppercase tracking-wide text-navy-700">Allergens</p>
                <div className="mt-2 flex flex-wrap gap-2">
                  {product.features.map((a) => (
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
              <span className="flex items-baseline gap-2">
                <span className="text-2xl font-bold text-brand-600">{peso(product.price)}</span>
                {onSale && (
                  <span className="text-sm text-slate-400 line-through">{peso(product.originalPrice)}</span>
                )}
              </span>
              {soldOut ? (
                <span className="rounded-full bg-slate-100 px-6 py-3 text-sm font-semibold text-slate-400">
                  Sold out
                </span>
              ) : qty === 0 ? (
                <button
                  type="button"
                  onClick={() => onAdd(product.id)}
                  className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
                >
                  Add to cart
                </button>
              ) : (
                <div className="flex items-center gap-3">
                  <QtyButton onClick={() => onDec(product.id)} label="Decrease">
                    −
                  </QtyButton>
                  <span className="w-6 text-center text-base font-semibold">{qty}</span>
                  <QtyButton onClick={() => onAdd(product.id)} label="Increase">
                    +
                  </QtyButton>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>,
    document.body,
  )
}

function QtyButton({ children, onClick, label }) {
  return (
    <button
      type="button"
      onClick={onClick}
      aria-label={label}
      className="flex h-7 w-7 items-center justify-center rounded-full bg-navy-100 text-base font-bold text-navy-800 transition hover:bg-brand-500 hover:text-white"
    >
      {children}
    </button>
  )
}

function Cart({
  lines,
  menu,
  subtotal,
  itemCount,
  onInc,
  onDec,
  onRemove,
  onCheckout,
  checkoutVisible = true,
  checkoutDisabled = false,
}) {
  const { user } = useAuth()
  const [code, setCode] = useState('')
  const [voucher, setVoucher] = useState(null) // { code, ...def }
  const [error, setError] = useState('')
  const [voucherDefs, setVoucherDefs] = useState({})

  useEffect(() => {
    fetchActiveVouchers().then(setVoucherDefs).catch(() => {})
  }, [])

  const applyVoucher = () => {
    const key = code.trim().toUpperCase()
    if (!key) return
    const def = voucherDefs[key]
    if (!def) {
      setVoucher(null)
      setError('Invalid voucher code')
      return
    }
    setVoucher({ code: key, ...def })
    setError('')
    setCode('')
  }

  const removeVoucher = () => {
    setVoucher(null)
    setError('')
  }

  // discount from the voucher
  let discount = 0
  if (voucher?.type === 'percent') discount = (subtotal * voucher.value) / 100
  else if (voucher?.type === 'amount') discount = Math.min(voucher.value, subtotal)

  const discounted = subtotal - discount
  const freeDelivery = subtotal >= FREE_DELIVERY_MIN || voucher?.type === 'freedel'
  const delivery = freeDelivery ? 0 : DELIVERY_FEE
  const vat = discounted * VAT_RATE
  const total = discounted + vat + delivery

  const suggestions = useMemo(() => pairedSuggestions(lines, menu), [lines, menu])

  return (
    <div className="flex h-full flex-col border-t border-slate-200 lg:border-l lg:border-t-0">
      <div className="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
        <CartIcon className="h-5 w-5 text-brand-500" />
        <h2 className="text-lg font-bold text-navy-800">Your Cart</h2>
        {itemCount > 0 && (
          <span className="ml-auto rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-600">
            {itemCount} item{itemCount > 1 ? 's' : ''}
          </span>
        )}
      </div>

      {lines.length === 0 ? (
        <div className="flex flex-1 flex-col items-center justify-center px-6 py-12 text-center">
          <div className="text-5xl">🛒</div>
          <p className="mt-3 text-sm text-slate-500">
            Your cart is empty.
            <br />
            Add some treats to get started!
          </p>
        </div>
      ) : (
        <>
          <ul className="flex-1 space-y-3 overflow-y-auto px-5 py-4">
            {lines.map(({ product, qty }) => (
              <li key={product.id} className="flex items-center gap-3">
                <img
                  src={product.img}
                  alt={product.name}
                  loading="lazy"
                  decoding="async"
                  className="h-12 w-12 shrink-0 rounded-lg object-cover"
                />
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-medium text-navy-800">{product.name}</p>
                  <p className="text-xs text-slate-500">{peso(product.price)} each</p>
                </div>
                <div className="flex items-center gap-1.5">
                  <QtyButton onClick={() => onDec(product.id)} label="Decrease">
                    −
                  </QtyButton>
                  <span className="w-4 text-center text-sm font-semibold">{qty}</span>
                  <QtyButton onClick={() => onInc(product.id)} label="Increase">
                    +
                  </QtyButton>
                </div>
                <button
                  type="button"
                  onClick={() => onRemove(product.id)}
                  aria-label={`Remove ${product.name}`}
                  title="Remove item"
                  className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                >
                  <TrashIcon className="h-4 w-4" />
                </button>
              </li>
            ))}
          </ul>

          {suggestions.length > 0 && (
            <div className="border-t border-slate-100 px-5 py-4">
              <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                Best paired with
              </p>
              <div className="flex gap-3 overflow-x-auto pb-1">
                {suggestions.map((p) => (
                  <div
                    key={p.id}
                    className="flex w-28 shrink-0 flex-col rounded-xl border border-slate-100 p-2"
                  >
                    <img
                      src={p.img}
                      alt={p.name}
                      loading="lazy"
                      decoding="async"
                      className="h-16 w-full rounded-lg object-cover"
                    />
                    <p className="mt-1.5 truncate text-xs font-medium text-navy-800">{p.name}</p>
                    <p className="text-xs font-semibold text-brand-600">{peso(p.price)}</p>
                    <button
                      type="button"
                      onClick={() => onInc(p.id)}
                      className="mt-1.5 rounded-full bg-navy-50 py-1 text-xs font-semibold text-navy-800 transition hover:bg-brand-500 hover:text-white"
                    >
                      + Add
                    </button>
                  </div>
                ))}
              </div>
            </div>
          )}

          <div className="border-t border-slate-100 px-5 py-4">
            {/* voucher */}
            <div className="mb-4">
              {voucher ? (
                <div className="flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm">
                  <span className="font-medium text-green-700">
                    🎟️ {voucher.code} — {voucher.label}
                  </span>
                  <button
                    type="button"
                    onClick={removeVoucher}
                    className="text-xs font-semibold text-slate-400 transition hover:text-red-600"
                  >
                    Remove
                  </button>
                </div>
              ) : (
                <>
                  <div className="flex gap-2">
                    <input
                      value={code}
                      onChange={(e) => setCode(e.target.value)}
                      onKeyDown={(e) => e.key === 'Enter' && applyVoucher()}
                      placeholder="Voucher code"
                      className="w-full rounded-full border border-slate-300 px-4 py-2 text-sm uppercase text-navy-800 outline-none transition placeholder:normal-case focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                    />
                    <button
                      type="button"
                      onClick={applyVoucher}
                      className="shrink-0 rounded-full bg-navy-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600"
                    >
                      Apply
                    </button>
                  </div>
                  {error && <p className="mt-1.5 text-xs text-red-600">{error}</p>}
                </>
              )}
            </div>

            <div className="space-y-1.5 text-sm">
              <div className="flex justify-between text-slate-600">
                <span>Subtotal</span>
                <span className="font-semibold text-navy-800">{peso(subtotal)}</span>
              </div>
              {discount > 0 && (
                <div className="flex justify-between text-green-600">
                  <span>Discount</span>
                  <span className="font-semibold">−{peso(discount)}</span>
                </div>
              )}
              <div className="flex justify-between text-slate-600">
                <span>Delivery</span>
                <span className={freeDelivery ? 'font-semibold text-green-600' : 'text-slate-500'}>
                  {freeDelivery ? 'FREE' : peso(DELIVERY_FEE)}
                </span>
              </div>
              <div className="flex justify-between text-slate-600">
                <span>VAT (12%)</span>
                <span>{peso(vat)}</span>
              </div>
              <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-navy-800">
                <span>Total</span>
                <span>{peso(total)}</span>
              </div>
              {!freeDelivery && (
                <p className="pt-1 text-xs text-brand-600">
                  Add {peso(FREE_DELIVERY_MIN - subtotal)} more for free delivery 🚚
                </p>
              )}
            </div>

            {/* Checkout CTA — the Site Editor's Buttons section can set this to
                visible (working), disabled (shown but inert), or hidden. */}
            {checkoutVisible &&
              (user ? (
                <button
                  type="button"
                  disabled={checkoutDisabled}
                  onClick={() =>
                    onCheckout({
                      items: lines.map(({ product, qty }) => ({
                        product_id: product.id,
                        name: product.name,
                        qty,
                        img: product.img,
                        price: product.price,
                      })),
                      voucher: voucher?.code || null,
                    })
                  }
                  className="mt-4 block w-full rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-center text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  Proceed to Checkout
                </button>
              ) : checkoutDisabled ? (
                <button
                  type="button"
                  disabled
                  className="mt-4 block w-full cursor-not-allowed rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-center text-sm font-semibold text-white opacity-60 shadow-md shadow-brand-500/30"
                >
                  Proceed to Checkout
                </button>
              ) : (
                <>
                  <Link
                    to="/login"
                    className="mt-4 block w-full rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-center text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
                  >
                    Proceed to Checkout
                  </Link>
                  <p className="mt-2 text-center text-xs text-slate-400">
                    Sign in to complete your order
                  </p>
                </>
              ))}
          </div>
        </>
      )}
    </div>
  )
}

function CartIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="9" cy="21" r="1" />
      <circle cx="20" cy="21" r="1" />
      <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
    </svg>
  )
}

function SparkleIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M12 2l1.9 5.6a3 3 0 0 0 1.9 1.9L21.4 11.4l-5.6 1.9a3 3 0 0 0-1.9 1.9L12 20.8l-1.9-5.6a3 3 0 0 0-1.9-1.9L2.6 11.4l5.6-1.9a3 3 0 0 0 1.9-1.9L12 2z" />
    </svg>
  )
}

function TrophyIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M6 4h12v4a6 6 0 0 1-12 0V4z" />
      <path d="M6 6H3v1a3 3 0 0 0 3 3" />
      <path d="M18 6h3v1a3 3 0 0 1-3 3" />
      <line x1="12" y1="14" x2="12" y2="18" />
      <path d="M8 21h8" />
      <path d="M10 18h4v3h-4z" />
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

function CloseIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <line x1="18" y1="6" x2="6" y2="18" />
      <line x1="6" y1="6" x2="18" y2="18" />
    </svg>
  )
}

function TrashIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <polyline points="3 6 5 6 21 6" />
      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
      <line x1="10" y1="11" x2="10" y2="17" />
      <line x1="14" y1="11" x2="14" y2="17" />
    </svg>
  )
}
