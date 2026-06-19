import { useEffect, useMemo, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { createOrder } from '../lib/orders'
import { fetchMenuProducts } from '../lib/content'

// Shopping / menu page — browse foods by category and build a cart.
// Cart state is local for now; "Proceed to checkout" sends guests to sign in.

const peso = (n) =>
  `₱${n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

const VAT_RATE = 0.12 // 12% Philippine VAT
const DELIVERY_FEE = 79
const FREE_DELIVERY_MIN = 1000

// Demo voucher codes (case-insensitive).
const VOUCHERS = {
  BW10: { type: 'percent', value: 10, label: '10% off' },
  SAVE50: { type: 'amount', value: 50, label: '₱50 off' },
  FREEDEL: { type: 'freedel', label: 'Free delivery' },
}

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

export default function Menu() {
  const [searchParams, setSearchParams] = useSearchParams()
  const [menu, setMenu] = useState([])
  const [menuLoading, setMenuLoading] = useState(true)
  const [active, setActive] = useState('All')
  const [query, setQuery] = useState('')
  const [cart, setCart] = useState({}) // { [id]: qty }
  const [cartOpen, setCartOpen] = useState(false) // mobile cart drawer

  // Load the menu from the products table (the same source the order-pricing
  // trigger trusts). Once loaded, honor a /menu?add=<name> deep link from the
  // landing "Best Sellers" by adding that product and filtering to its category.
  useEffect(() => {
    let alive = true
    fetchMenuProducts()
      .then((rows) => {
        if (!alive) return
        setMenu(rows)
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
        }
      })
      .catch(() => alive && setMenuLoading(false))
    return () => {
      alive = false
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const visible = useMemo(() => {
    const q = query.trim().toLowerCase()
    return menu.filter((p) => {
      const inCategory = active === 'All' || p.category === active
      const matches =
        !q || p.name.toLowerCase().includes(q) || (p.desc || '').toLowerCase().includes(q)
      return inCategory && matches
    })
  }, [menu, active, query])

  const lines = useMemo(
    () =>
      Object.entries(cart)
        .map(([id, qty]) => ({ product: menu.find((p) => p.id === id), qty }))
        .filter((l) => l.product),
    [cart, menu],
  )

  // Category tabs are derived from the products themselves (first product image
  // per category is used as the badge), plus an "All" tab.
  const categories = useMemo(() => {
    const seen = new Map()
    for (const p of menu) {
      if (p.category && !seen.has(p.category)) seen.set(p.category, p.img)
    }
    return [
      { name: 'All', img: '/images/bakery-interior.jpg' },
      ...[...seen].map(([name, img]) => ({ name, img })),
    ]
  }, [menu])

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

  const [placing, setPlacing] = useState(false)
  const checkout = async (summary) => {
    setPlacing(true)
    try {
      // The DB returns the authoritative, server-computed totals.
      const order = await createOrder(summary)
      window.alert(`🧡 Thank you! Your order has been placed.\n\nTotal: ${peso(Number(order.total))}`)
      setCart({})
      setCartOpen(false)
    } catch (err) {
      window.alert(`Sorry, we couldn't place your order: ${err.message}`)
    } finally {
      setPlacing(false)
    }
  }

  return (
    <div className="animate-page-in flex min-h-screen flex-col bg-navy-50/40 text-navy-800 lg:flex-row">
      {/* left — full-height categories sidebar (with logo on top) */}
      <CategorySidebar active={active} onChange={setActive} categories={categories} />

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
                <MenuCard key={p.id} product={p} qty={cart[p.id] || 0} onAdd={add} onDec={dec} />
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
              placing={placing}
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
              placing={placing}
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
        className="hidden h-16 shrink-0 items-center gap-2 px-4 lg:flex"
      >
        <img src="/images/logo (1).png" alt="bw Superbakeshop" className="h-11 w-auto" />
        <span className="font-brand text-lg font-bold text-white">Superbakeshop</span>
      </Link>

      {/* categories — fill the remaining height */}
      <nav className="flex gap-3 overflow-x-auto p-3 lg:flex-1 lg:flex-col lg:justify-between lg:gap-2 lg:overflow-x-hidden lg:overflow-y-auto">
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
              <span className="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white shadow ring-1 ring-black/5">
                <img src={c.img} alt="" className="h-full w-full object-cover" />
              </span>
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
              <span className="hidden text-sm text-navy-700 sm:block">
                Hi, <span className="font-semibold">{user.name}</span>
              </span>
              <button
                type="button"
                onClick={handleLogout}
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
    </header>
  )
}

const STATUS_LABEL = {
  new: 'New',
  best_seller: 'Best Seller',
  sold_out: 'Sold out',
}

function MenuCard({ product, qty, onAdd, onDec }) {
  const soldOut = product.status === 'sold_out'
  const onSale = product.originalPrice != null && product.originalPrice > product.price
  return (
    <div className="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm transition hover:shadow-xl">
      <div className="relative overflow-hidden">
        <img
          src={product.img}
          alt={product.name}
          className={`h-36 w-full object-cover transition duration-300 group-hover:scale-105 ${
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
              onClick={() => onAdd(product.id)}
              className="rounded-full bg-navy-800 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-600"
            >
              Add
            </button>
          ) : (
            <div className="flex items-center gap-2">
              <QtyButton onClick={() => onDec(product.id)} label="Decrease">
                −
              </QtyButton>
              <span className="w-5 text-center text-sm font-semibold">{qty}</span>
              <QtyButton onClick={() => onAdd(product.id)} label="Increase">
                +
              </QtyButton>
            </div>
          )}
        </div>
      </div>
    </div>
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

function Cart({ lines, menu, subtotal, itemCount, onInc, onDec, onRemove, onCheckout, placing }) {
  const { user } = useAuth()
  const [code, setCode] = useState('')
  const [voucher, setVoucher] = useState(null) // { code, ...def }
  const [error, setError] = useState('')

  const applyVoucher = () => {
    const key = code.trim().toUpperCase()
    if (!key) return
    const def = VOUCHERS[key]
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

            {user ? (
              <button
                type="button"
                disabled={placing}
                onClick={() =>
                  onCheckout({
                    items: lines.map(({ product, qty }) => ({
                      product_id: product.id,
                      name: product.name,
                      qty,
                    })),
                    voucher: voucher?.code || null,
                  })
                }
                className="mt-4 block w-full rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-center text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {placing ? 'Placing order…' : 'Place Order'}
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
            )}
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
