import { useMemo, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { createOrder } from '../lib/orders'

// Shopping / menu page — browse foods by category and build a cart.
// Cart state is local for now; "Proceed to checkout" sends guests to sign in.

const CATEGORIES = [
  { name: 'All', emoji: '🍽️' },
  { name: 'Cakes', emoji: '🍰' },
  { name: 'Breads', emoji: '🥖' },
  { name: 'Pastries', emoji: '🥐' },
  { name: 'Cupcakes', emoji: '🧁' },
  { name: 'Cookies', emoji: '🍪' },
  { name: 'Donuts', emoji: '🍩' },
  { name: 'Pies', emoji: '🥧' },
  { name: 'Desserts', emoji: '🍮' },
  { name: 'Sandwiches', emoji: '🥪' },
  { name: 'Beverages', emoji: '🥤' },
]

const MENU = [
  { id: 1, name: 'Classic Mocha Cake', category: 'Cakes', price: 650, img: 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?auto=format&fit=crop&w=600&q=80', desc: 'Moist chocolate sponge layered with mocha cream.' },
  { id: 2, name: 'Ube Chiffon Cake', category: 'Cakes', price: 720, img: 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=600&q=80', desc: 'Soft purple yam chiffon with sweet glaze.' },
  { id: 3, name: 'Red Velvet Slice', category: 'Cakes', price: 150, img: 'https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?auto=format&fit=crop&w=600&q=80', desc: 'Velvety cocoa cake with cream cheese frosting.' },
  { id: 4, name: 'Soft Ensaymada', category: 'Pastries', price: 45, img: 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=600&q=80', desc: 'Buttery brioche topped with cheese and sugar.' },
  { id: 5, name: 'Buttery Croissant', category: 'Pastries', price: 85, img: 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=600&q=80', desc: 'Flaky, golden, freshly baked each morning.' },
  { id: 6, name: 'Fresh Pandesal (12pcs)', category: 'Breads', price: 60, img: 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?auto=format&fit=crop&w=600&q=80', desc: 'The classic Filipino breakfast roll.' },
  { id: 7, name: 'Wheat Loaf', category: 'Breads', price: 95, img: 'https://images.unsplash.com/photo-1598373182133-52452f7691ef?auto=format&fit=crop&w=600&q=80', desc: 'Wholesome sliced wheat bread.' },
  { id: 8, name: 'Chocolate Cupcakes', category: 'Cupcakes', price: 180, img: 'https://images.unsplash.com/photo-1426869981800-95ebf51ce900?auto=format&fit=crop&w=600&q=80', desc: 'Box of 6 rich chocolate cupcakes.' },
  { id: 9, name: 'Vanilla Cupcakes', category: 'Cupcakes', price: 170, img: 'https://images.unsplash.com/photo-1599785209707-a456fc1337bb?auto=format&fit=crop&w=600&q=80', desc: 'Box of 6 classic vanilla cupcakes.' },
  { id: 10, name: 'Assorted Cookies', category: 'Cookies', price: 220, img: 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=600&q=80', desc: 'A dozen freshly baked cookies.' },
  { id: 11, name: 'Chocolate Chip Cookies', category: 'Cookies', price: 240, img: 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?auto=format&fit=crop&w=600&q=80', desc: 'Gooey chocolate chip, baked to order.' },
  { id: 12, name: 'Leche Flan', category: 'Desserts', price: 130, img: 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=600&q=80', desc: 'Silky caramel custard, a Filipino favorite.' },
  { id: 13, name: 'Buko Pandan Cup', category: 'Desserts', price: 95, img: 'https://images.unsplash.com/photo-1551024506-0bccd828d307?auto=format&fit=crop&w=600&q=80', desc: 'Young coconut and pandan jelly in cream.' },
  { id: 14, name: 'Hopia (Box of 8)', category: 'Desserts', price: 120, img: 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=600&q=80', desc: 'Flaky mung bean filled pastry.' },
  { id: 15, name: 'Iced Coffee', category: 'Beverages', price: 110, img: 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=600&q=80', desc: 'Chilled brewed coffee over ice.' },
  { id: 16, name: 'Hot Chocolate', category: 'Beverages', price: 90, img: 'https://images.unsplash.com/photo-1542990253-0d0f5be5f0ed?auto=format&fit=crop&w=600&q=80', desc: 'Rich, creamy hot cocoa.' },
  { id: 17, name: 'Fresh Milk Tea', category: 'Beverages', price: 120, img: 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=600&q=80', desc: 'Classic milk tea with chewy pearls.' },
  { id: 18, name: 'Glazed Donut', category: 'Donuts', price: 55, img: 'https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=600&q=80', desc: 'Soft ring donut with sweet glaze.' },
  { id: 19, name: 'Chocolate Donut', category: 'Donuts', price: 60, img: 'https://images.unsplash.com/photo-1527515637462-cff94eecc1ac?auto=format&fit=crop&w=600&q=80', desc: 'Glazed donut dipped in chocolate.' },
  { id: 20, name: 'Apple Pie', category: 'Pies', price: 240, img: 'https://images.unsplash.com/photo-1535920527002-b35e96722eb9?auto=format&fit=crop&w=600&q=80', desc: 'Buttery crust with cinnamon apples.' },
  { id: 21, name: 'Buko Pie', category: 'Pies', price: 220, img: 'https://images.unsplash.com/photo-1621743478914-cc8a86d7e7b5?auto=format&fit=crop&w=600&q=80', desc: 'Creamy young coconut pie.' },
  { id: 22, name: 'Ham & Cheese Sandwich', category: 'Sandwiches', price: 90, img: 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=600&q=80', desc: 'Toasted sandwich with ham and cheese.' },
  { id: 23, name: 'Clubhouse Sandwich', category: 'Sandwiches', price: 130, img: 'https://images.unsplash.com/photo-1567234669003-dce7a7a88821?auto=format&fit=crop&w=600&q=80', desc: 'Triple-decker with chicken and egg.' },
]

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

export default function Menu() {
  const [active, setActive] = useState('All')
  const [query, setQuery] = useState('')
  const [cart, setCart] = useState({}) // { [id]: qty }
  const [cartOpen, setCartOpen] = useState(false) // mobile cart drawer

  const visible = useMemo(() => {
    const q = query.trim().toLowerCase()
    return MENU.filter((p) => {
      const inCategory = active === 'All' || p.category === active
      const matches =
        !q || p.name.toLowerCase().includes(q) || p.desc.toLowerCase().includes(q)
      return inCategory && matches
    })
  }, [active, query])

  const lines = useMemo(
    () =>
      Object.entries(cart)
        .map(([id, qty]) => ({ product: MENU.find((p) => p.id === Number(id)), qty }))
        .filter((l) => l.product),
    [cart],
  )

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
      await createOrder(summary)
      window.alert('🧡 Thank you! Your order has been placed.')
      setCart({})
      setCartOpen(false)
    } catch (err) {
      window.alert(`Sorry, we couldn't place your order: ${err.message}`)
    } finally {
      setPlacing(false)
    }
  }

  return (
    <div className="flex min-h-screen flex-col bg-navy-50/40 text-navy-800 lg:flex-row">
      {/* left — full-height categories sidebar (with logo on top) */}
      <CategorySidebar active={active} onChange={setActive} />

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
          {visible.length === 0 ? (
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

function CategorySidebar({ active, onChange }) {
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
        {CATEGORIES.map((c) => {
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
              <span className="flex h-11 w-11 items-center justify-center rounded-full bg-white text-2xl shadow ring-1 ring-black/5">
                {c.emoji}
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
  const { user, isAdmin, logout } = useAuth()
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

function MenuCard({ product, qty, onAdd, onDec }) {
  return (
    <div className="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm transition hover:shadow-xl">
      <div className="overflow-hidden">
        <img
          src={product.img}
          alt={product.name}
          className="h-36 w-full object-cover transition duration-300 group-hover:scale-105"
        />
      </div>
      <div className="flex flex-1 flex-col p-4">
        <h3 className="text-sm font-semibold text-navy-800">{product.name}</h3>
        <p className="mt-1 line-clamp-2 text-xs text-slate-500">{product.desc}</p>
        <div className="mt-auto flex items-center justify-between pt-3">
          <span className="text-lg font-bold text-brand-600">{peso(product.price)}</span>
          {qty === 0 ? (
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

function Cart({ lines, subtotal, itemCount, onInc, onDec, onRemove, onCheckout, placing }) {
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
              </li>
            ))}
          </ul>

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
                      name: product.name,
                      qty,
                      price: product.price,
                    })),
                    subtotal,
                    discount,
                    delivery,
                    vat,
                    total,
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
