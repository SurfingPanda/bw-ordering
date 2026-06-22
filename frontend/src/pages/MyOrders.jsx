import { Fragment, useEffect, useMemo, useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { fetchMyOrders } from '../lib/orders'
import { fetchMenuProducts } from '../lib/content'

// Customer-facing order history. Reached from the Menu header ("My Orders")
// behind ProtectedRoute, so `user` is always present here.

const peso = (n) =>
  `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

const STATUS_LABEL = {
  pending: 'Order Placed',
  preparing: 'Preparing',
  completed: 'Delivered',
  cancelled: 'Cancelled',
}
const STATUS_STYLES = {
  pending: 'bg-amber-100 text-amber-700',
  preparing: 'bg-orange-100 text-orange-700',
  completed: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
}

const TABS = [
  { key: 'all', label: 'All Orders' },
  { key: 'active', label: 'Active' },
  { key: 'completed', label: 'Completed' },
  { key: 'cancelled', label: 'Cancelled' },
]

const TRACK = [
  { label: 'Order Placed', icon: '🧾' },
  { label: 'Preparing', icon: '👨‍🍳' },
  { label: 'Out for Delivery', icon: '🛵' },
  { label: 'Delivered', icon: '📦' },
]
const trackIndex = (status) =>
  ({ pending: 0, preparing: 1, completed: 3 }[status] ?? 0)

const isActiveStatus = (s) => s === 'pending' || s === 'preparing'

export default function MyOrders() {
  const navigate = useNavigate()
  const location = useLocation()

  const [orders, setOrders] = useState([])
  const [imgMap, setImgMap] = useState({})
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [tab, setTab] = useState('all')
  const [query, setQuery] = useState('')

  const justPlaced = location.state?.placed

  useEffect(() => {
    let alive = true
    Promise.all([fetchMyOrders(), fetchMenuProducts().catch(() => [])])
      .then(([os, products]) => {
        if (!alive) return
        setOrders(os)
        const m = {}
        products.forEach((p) => {
          if (p.id) m[p.id] = p.img
          if (p.name) m[p.name.toLowerCase()] = p.img
        })
        setImgMap(m)
      })
      .catch((err) => alive && setError(err.message))
      .finally(() => alive && setLoading(false))
    return () => {
      alive = false
    }
  }, [])

  const points = useMemo(
    () =>
      orders
        .filter((o) => o.status !== 'cancelled')
        .reduce((s, o) => s + Math.floor(Number(o.total || 0) / 10), 0),
    [orders],
  )

  const counts = useMemo(() => {
    const c = { all: orders.length, active: 0, completed: 0, cancelled: 0 }
    orders.forEach((o) => {
      if (isActiveStatus(o.status)) c.active += 1
      else if (o.status === 'completed') c.completed += 1
      else if (o.status === 'cancelled') c.cancelled += 1
    })
    return c
  }, [orders])

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase()
    return orders.filter((o) => {
      const byTab =
        tab === 'all' ? true : tab === 'active' ? isActiveStatus(o.status) : o.status === tab
      const items = Array.isArray(o.items) ? o.items : []
      const byText =
        !q ||
        String(o.id).toLowerCase().includes(q) ||
        items.some((i) => i.name?.toLowerCase().includes(q))
      return byTab && byText
    })
  }, [orders, tab, query])

  const active = filtered.filter((o) => isActiveStatus(o.status))
  const past = filtered.filter((o) => !isActiveStatus(o.status))

  const reorder = (order) => {
    const cart = {}
    ;(Array.isArray(order.items) ? order.items : []).forEach((i) => {
      if (i.product_id) cart[i.product_id] = (cart[i.product_id] || 0) + i.qty
    })
    try {
      localStorage.setItem('bw_cart', JSON.stringify(cart))
    } catch {
      // best-effort
    }
    navigate('/menu')
  }

  return (
    <div className="min-h-screen bg-navy-50/40 text-navy-800">
      <header className="border-b border-slate-100 bg-white">
        <div className="mx-auto flex max-w-5xl flex-col gap-4 px-4 py-6 sm:px-6">
          <div className="flex items-start justify-between gap-4">
            <div className="flex items-center gap-3">
              <Link to="/" className="shrink-0">
                <img src="/images/logo (1).png" alt="bw Superbakeshop" className="h-10 w-auto" />
              </Link>
              <div>
                <h1 className="text-2xl font-bold text-navy-800">My Orders</h1>
                <p className="text-sm text-slate-500">Track and reorder your favorite treats.</p>
              </div>
            </div>
            <div className="flex flex-col items-end gap-2">
              <Link
                to="/menu"
                className="text-sm font-medium text-slate-500 transition hover:text-brand-600"
              >
                ← Back to menu
              </Link>
              <span className="flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-amber-700">
                <span className="text-base">⭐</span>
                <span className="text-xs font-semibold uppercase tracking-wide">BW Points</span>
                <span className="text-sm font-bold">{points.toLocaleString()}</span>
              </span>
            </div>
          </div>

          {/* filter tabs */}
          <div className="flex flex-wrap gap-2">
            {TABS.map((t) => (
              <button
                key={t.key}
                type="button"
                onClick={() => setTab(t.key)}
                className={`flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition ${
                  tab === t.key
                    ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md shadow-brand-500/30'
                    : 'bg-slate-100 text-navy-700 hover:bg-slate-200'
                }`}
              >
                {t.label}
                {t.key !== 'all' && counts[t.key] > 0 && (
                  <span
                    className={`rounded-full px-1.5 text-xs ${
                      tab === t.key ? 'bg-white/25' : 'bg-white text-navy-700'
                    }`}
                  >
                    {counts[t.key]}
                  </span>
                )}
              </button>
            ))}
          </div>

          {/* search */}
          <div className="relative">
            <SearchIcon className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Search orders by order number or item"
              className="w-full rounded-full border border-slate-300 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
            />
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        {justPlaced && (
          <div className="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            🎉 Your order has been placed! Track its progress below.
          </div>
        )}

        {loading ? (
          <p className="py-16 text-center text-sm text-slate-500">Loading…</p>
        ) : error ? (
          <div className="rounded-2xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">
            <p className="font-semibold">Couldn&apos;t load your orders.</p>
            <p className="mt-1">{error}</p>
          </div>
        ) : filtered.length === 0 ? (
          <EmptyState query={query} />
        ) : (
          <div className="space-y-8">
            {active.length > 0 && (
              <Group title="Active Orders" count={active.length}>
                {active.map((o) => (
                  <ActiveOrderCard key={o.id} order={o} imgMap={imgMap} onReorder={reorder} />
                ))}
              </Group>
            )}
            {past.length > 0 && (
              <Group title="Past Orders" count={past.length}>
                {past.map((o) => (
                  <PastOrderCard key={o.id} order={o} imgMap={imgMap} onReorder={reorder} />
                ))}
              </Group>
            )}
          </div>
        )}

        <p className="mt-10 text-center text-xs text-slate-400">
          Can&apos;t find your order?{' '}
          <a href="mailto:support@bwsuperbakeshop.com" className="font-semibold text-brand-600 hover:underline">
            Contact Support
          </a>
        </p>
      </main>
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* pieces                                                             */
/* ------------------------------------------------------------------ */

function Group({ title, count, children }) {
  return (
    <section>
      <h2 className="mb-3 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-navy-700">
        {title}
        <span className="rounded-full bg-brand-100 px-2 py-0.5 text-xs text-brand-700">{count}</span>
      </h2>
      <div className="space-y-4">{children}</div>
    </section>
  )
}

function orderRef(id) {
  return `#${String(id).slice(0, 8).toUpperCase()}`
}
function orderDate(created) {
  if (!created) return ''
  return new Date(created).toLocaleString('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
function imgFor(item, imgMap) {
  return imgMap[item.product_id] || imgMap[item.name?.toLowerCase()] || ''
}

function Thumbs({ items, imgMap }) {
  const shown = items.slice(0, 2)
  const extra = items.length - shown.length
  return (
    <div className="flex items-center">
      {shown.map((i, idx) => (
        <span
          key={idx}
          className="-ml-2 h-12 w-12 overflow-hidden rounded-xl border-2 border-white bg-slate-100 shadow-sm first:ml-0"
        >
          {imgFor(i, imgMap) ? (
            <img src={imgFor(i, imgMap)} alt={i.name} className="h-full w-full object-cover" />
          ) : (
            <span className="flex h-full w-full items-center justify-center text-lg">🍞</span>
          )}
        </span>
      ))}
      {extra > 0 && (
        <span className="-ml-2 flex h-12 w-12 items-center justify-center rounded-xl border-2 border-white bg-navy-800 text-xs font-bold text-white shadow-sm">
          +{extra}
        </span>
      )}
    </div>
  )
}

function Totals({ order }) {
  const itemCount = (Array.isArray(order.items) ? order.items : []).reduce(
    (s, i) => s + (i.qty || 0),
    0,
  )
  return (
    <div className="text-sm">
      <p className="mb-1 font-semibold text-navy-800">
        {itemCount} item{itemCount === 1 ? '' : 's'}
      </p>
      <dl className="space-y-0.5 text-xs text-slate-500">
        <Row label="Subtotal" value={peso(order.subtotal)} />
        {Number(order.discount) > 0 && (
          <Row label={`Discount${order.voucher ? ` (${order.voucher})` : ''}`} value={`−${peso(order.discount)}`} />
        )}
        <Row label="Delivery" value={Number(order.delivery) === 0 ? 'FREE' : peso(order.delivery)} />
        <Row label="VAT (12%)" value={peso(order.vat)} />
      </dl>
    </div>
  )
}

function Row({ label, value }) {
  return (
    <div className="flex justify-between gap-6">
      <dt>{label}</dt>
      <dd className="text-navy-700">{value}</dd>
    </div>
  )
}

function Tracker({ status }) {
  const idx = trackIndex(status)
  return (
    <div className="flex items-center">
      {TRACK.map((step, i) => {
        const done = i <= idx
        return (
          <Fragment key={step.label}>
            <div className="flex shrink-0 flex-col items-center gap-1">
              <span
                className={`flex h-8 w-8 items-center justify-center rounded-full text-sm ${
                  done ? 'bg-brand-500 text-white shadow-sm shadow-brand-500/40' : 'bg-slate-100 text-slate-300'
                }`}
              >
                {step.icon}
              </span>
              <span className={`text-[0.6rem] font-medium ${done ? 'text-navy-700' : 'text-slate-400'}`}>
                {step.label}
              </span>
            </div>
            {i < TRACK.length - 1 && (
              <span className={`mx-1 mb-4 h-0.5 flex-1 rounded-full ${i < idx ? 'bg-brand-500' : 'bg-slate-200'}`} />
            )}
          </Fragment>
        )
      })}
    </div>
  )
}

function StatusBadge({ status }) {
  return (
    <span
      className={`rounded-full px-3 py-1 text-xs font-semibold ${
        STATUS_STYLES[status] || 'bg-slate-100 text-slate-600'
      }`}
    >
      {STATUS_LABEL[status] || status}
    </span>
  )
}

function ActiveOrderCard({ order, imgMap, onReorder }) {
  const [open, setOpen] = useState(false)
  const items = Array.isArray(order.items) ? order.items : []
  return (
    <div className="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div>
          <h3 className="font-bold text-navy-800">Order {orderRef(order.id)}</h3>
          <p className="text-xs text-slate-400">{orderDate(order.created_at)}</p>
        </div>
        <StatusBadge status={order.status} />
      </div>

      <div className="my-5">
        <Tracker status={order.status} />
      </div>

      <div className="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-4">
        <div className="flex items-center gap-4">
          <Thumbs items={items} imgMap={imgMap} />
          <Totals order={order} />
        </div>
        <div className="text-right">
          <p className="text-xs text-slate-400">Total</p>
          <p className="text-xl font-bold text-brand-600">{peso(order.total)}</p>
        </div>
      </div>

      {open && <ItemList items={items} imgMap={imgMap} />}

      <div className="mt-4 flex flex-wrap items-center gap-2">
        <button
          type="button"
          onClick={() => setOpen((v) => !v)}
          className="text-sm font-medium text-brand-600 hover:underline"
        >
          {open ? 'Hide details' : 'View Details'}
        </button>
        <div className="ml-auto flex gap-2">
          <button
            type="button"
            onClick={() => onReorder(order)}
            className="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600"
          >
            Reorder
          </button>
        </div>
      </div>
    </div>
  )
}

function PastOrderCard({ order, imgMap, onReorder }) {
  const [open, setOpen] = useState(false)
  const items = Array.isArray(order.items) ? order.items : []
  const itemCount = items.reduce((s, i) => s + (i.qty || 0), 0)
  return (
    <div className="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center gap-4">
          <Thumbs items={items} imgMap={imgMap} />
          <div>
            <div className="flex items-center gap-2">
              <h3 className="font-bold text-navy-800">Order {orderRef(order.id)}</h3>
              <StatusBadge status={order.status} />
            </div>
            <p className="text-xs text-slate-400">{orderDate(order.created_at)}</p>
            <p className="mt-0.5 text-xs text-slate-500">
              {itemCount} item{itemCount === 1 ? '' : 's'}
            </p>
          </div>
        </div>
        <div className="flex items-center gap-4">
          <div className="text-right">
            <p className="text-xs text-slate-400">Total</p>
            <p className="text-lg font-bold text-brand-600">{peso(order.total)}</p>
          </div>
          <button
            type="button"
            onClick={() => onReorder(order)}
            className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-xs font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
          >
            Order Again
          </button>
        </div>
      </div>

      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="mt-3 text-sm font-medium text-brand-600 hover:underline"
      >
        {open ? 'Hide details' : 'View Details'}
      </button>
      {open && <ItemList items={items} imgMap={imgMap} order={order} />}
    </div>
  )
}

function ItemList({ items, imgMap, order }) {
  return (
    <div className="mt-3 space-y-2 border-t border-slate-100 pt-3 text-sm">
      {items.map((i, idx) => (
        <div key={idx} className="flex items-center gap-3">
          <span className="h-9 w-9 shrink-0 overflow-hidden rounded-lg bg-slate-100">
            {imgFor(i, imgMap) ? (
              <img src={imgFor(i, imgMap)} alt={i.name} className="h-full w-full object-cover" />
            ) : (
              <span className="flex h-full w-full items-center justify-center">🍞</span>
            )}
          </span>
          <span className="flex-1 text-slate-600">
            {i.qty}× {i.name}
          </span>
          <span className="font-medium text-navy-800">{peso((i.price || 0) * (i.qty || 0))}</span>
        </div>
      ))}
      {order && (
        <div className="mt-2 flex justify-between border-t border-dashed border-slate-200 pt-2 text-sm font-bold text-navy-800">
          <span>Total</span>
          <span>{peso(order.total)}</span>
        </div>
      )}
    </div>
  )
}

function EmptyState({ query }) {
  return (
    <div className="rounded-2xl border border-slate-100 bg-white p-10 text-center shadow-sm">
      <p className="text-sm text-slate-500">
        {query ? 'No orders match your search.' : "You haven't placed any orders here yet."}
      </p>
      <Link
        to="/menu"
        className="mt-4 inline-block rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
      >
        Browse the menu
      </Link>
    </div>
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
