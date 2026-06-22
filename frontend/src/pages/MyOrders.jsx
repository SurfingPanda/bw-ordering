import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { fetchMyOrders } from '../lib/orders'

// Customer-facing order history. Reached from the Menu header ("My Orders")
// behind ProtectedRoute, so `user` is always present here.

const peso = (n) =>
  `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

const STATUS_STYLES = {
  pending: 'bg-amber-100 text-amber-700',
  preparing: 'bg-blue-100 text-blue-700',
  completed: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
}

export default function MyOrders() {
  const { user } = useAuth()
  const [orders, setOrders] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let alive = true
    fetchMyOrders()
      .then((data) => alive && setOrders(data))
      .catch((err) => alive && setError(err.message))
      .finally(() => alive && setLoading(false))
    return () => {
      alive = false
    }
  }, [])

  return (
    <div className="min-h-screen bg-navy-50/40 text-navy-800">
      <header className="sticky top-0 z-20 border-b border-slate-100 bg-white">
        <div className="mx-auto flex h-16 max-w-4xl items-center justify-between px-4 sm:px-6">
          <Link to="/" className="flex items-center gap-2">
            <img src="/images/logo (1).png" alt="bw Superbakeshop" className="h-9 w-auto" />
          </Link>
          <Link
            to="/menu"
            className="text-sm font-medium text-navy-700 transition hover:text-brand-600"
          >
            ← Back to menu
          </Link>
        </div>
      </header>

      <main className="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <h1 className="text-2xl font-bold text-navy-800">My Orders</h1>
        <p className="mt-1 text-sm text-slate-500">
          {user?.name ? `Hi, ${user.name} — ` : ''}your past and current orders.
        </p>

        <div className="mt-6">
          {loading ? (
            <p className="py-16 text-center text-sm text-slate-500">Loading…</p>
          ) : error ? (
            <div className="rounded-2xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">
              <p className="font-semibold">Couldn&apos;t load your orders.</p>
              <p className="mt-1">{error}</p>
            </div>
          ) : orders.length === 0 ? (
            <div className="rounded-2xl border border-slate-100 bg-white p-10 text-center shadow-sm">
              <p className="text-sm text-slate-500">You haven&apos;t placed any orders yet.</p>
              <Link
                to="/menu"
                className="mt-4 inline-block rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
              >
                Browse the menu
              </Link>
            </div>
          ) : (
            <div className="space-y-4">
              {orders.map((o) => (
                <OrderCard key={o.id} order={o} />
              ))}
            </div>
          )}
        </div>
      </main>
    </div>
  )
}

function OrderCard({ order }) {
  const [open, setOpen] = useState(false)
  const items = Array.isArray(order.items) ? order.items : []
  const count = items.reduce((sum, i) => sum + (i.qty || 0), 0)
  const date = order.created_at ? new Date(order.created_at).toLocaleString() : ''

  return (
    <div className="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <div className="flex items-center gap-2">
            <h3 className="font-semibold text-navy-800">
              Order #{String(order.id).slice(0, 8)}
            </h3>
            <span
              className={`rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ${
                STATUS_STYLES[order.status] || 'bg-slate-100 text-slate-600'
              }`}
            >
              {order.status}
            </span>
          </div>
          <p className="mt-1 text-xs text-slate-400">{date}</p>
        </div>
        <div className="text-right">
          <p className="text-lg font-bold text-brand-600">{peso(order.total)}</p>
          <p className="text-xs text-slate-500">
            {count} item{count === 1 ? '' : 's'}
          </p>
        </div>
      </div>

      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="mt-4 text-sm font-medium text-brand-600 hover:underline"
      >
        {open ? 'Hide details' : 'View details'}
      </button>

      {open && (
        <div className="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
          {items.map((i, idx) => (
            <div key={idx} className="flex justify-between text-slate-600">
              <span>
                {i.qty}× {i.name}
              </span>
              <span>{peso((i.price || 0) * (i.qty || 0))}</span>
            </div>
          ))}
          <div className="mt-2 space-y-1 border-t border-dashed border-slate-200 pt-2 text-xs text-slate-500">
            <Line label="Subtotal" value={peso(order.subtotal)} />
            {Number(order.discount) > 0 && (
              <Line
                label={`Discount${order.voucher ? ` (${order.voucher})` : ''}`}
                value={`−${peso(order.discount)}`}
              />
            )}
            <Line
              label="Delivery"
              value={Number(order.delivery) === 0 ? 'FREE' : peso(order.delivery)}
            />
            <Line label="VAT (12%)" value={peso(order.vat)} />
            <div className="flex justify-between pt-1 text-sm font-bold text-navy-800">
              <span>Total</span>
              <span>{peso(order.total)}</span>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

function Line({ label, value }) {
  return (
    <div className="flex justify-between">
      <span>{label}</span>
      <span>{value}</span>
    </div>
  )
}
