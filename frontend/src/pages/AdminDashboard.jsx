import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { fetchAllOrders, updateOrderStatus } from '../lib/orders'

const peso = (n) =>
  `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

const ORDER_NAV = [
  { key: 'all', label: 'All Orders', Icon: ListIcon },
  { key: 'pending', label: 'Pending', Icon: ClockIcon },
  { key: 'preparing', label: 'Preparing', Icon: UtensilsIcon },
  { key: 'completed', label: 'Completed', Icon: CheckIcon },
  { key: 'cancelled', label: 'Cancelled', Icon: BanIcon },
]

const STATUS_STYLES = {
  pending: 'bg-amber-100 text-amber-700',
  preparing: 'bg-blue-100 text-blue-700',
  completed: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
}

const PAYMENT_LABEL = { qrph: 'QRPH', cash: 'Cash on Pickup', cod: 'Cash on Delivery', gcash: 'GCash' }

export default function AdminDashboard() {
  const { user, logout } = useAuth()
  const [orders, setOrders] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [view, setView] = useState('reports') // 'reports' | order-status key
  const [query, setQuery] = useState('')

  const load = async () => {
    setLoading(true)
    setError('')
    try {
      setOrders(await fetchAllOrders())
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
  }, [])

  const changeStatus = async (id, status) => {
    setOrders((os) => os.map((o) => (o.id === id ? { ...o, status } : o)))
    try {
      await updateOrderStatus(id, status)
    } catch (err) {
      window.alert(`Could not update status: ${err.message}`)
      load()
    }
  }

  const counts = useMemo(() => {
    const c = { all: orders.length, pending: 0, preparing: 0, completed: 0, cancelled: 0 }
    orders.forEach((o) => {
      if (c[o.status] !== undefined) c[o.status] += 1
    })
    return c
  }, [orders])

  const revenue = useMemo(
    () =>
      orders
        .filter((o) => o.status !== 'cancelled')
        .reduce((sum, o) => sum + Number(o.total || 0), 0),
    [orders],
  )

  const visible = useMemo(() => {
    const q = query.trim().toLowerCase()
    return orders.filter((o) => {
      const byStatus = view === 'all' || o.status === view
      const byText =
        !q ||
        o.customer_name?.toLowerCase().includes(q) ||
        o.customer_email?.toLowerCase().includes(q)
      return byStatus && byText
    })
  }, [orders, view, query])

  const today = new Date().toLocaleDateString('en-PH', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
  })

  const isReports = view === 'reports'
  const title = isReports ? 'Reports' : ORDER_NAV.find((n) => n.key === view)?.label || 'Orders'

  return (
    <div className="flex min-h-screen flex-col bg-navy-50/40 text-navy-800 lg:flex-row">
      <Sidebar
        user={user}
        onLogout={logout}
        view={view}
        onView={setView}
        counts={counts}
        revenue={revenue}
        today={today}
      />

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-20 border-b border-slate-200 bg-white">
          <div className="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
              <h1 className="text-xl font-bold text-navy-800">{title}</h1>
              <p className="text-xs text-slate-500">
                {isReports
                  ? 'Sales overview and insights'
                  : `${visible.length} order${visible.length === 1 ? '' : 's'} shown`}
              </p>
            </div>
            <div className="flex items-center gap-2">
              {!isReports && (
                <input
                  type="search"
                  value={query}
                  onChange={(e) => setQuery(e.target.value)}
                  placeholder="Search customer or email"
                  className="w-full rounded-full border border-slate-300 bg-white px-4 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 sm:w-64"
                />
              )}
              <button
                onClick={load}
                className="shrink-0 rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600"
              >
                Refresh
              </button>
            </div>
          </div>
        </header>

        <main className="flex-1 px-4 py-6 sm:px-6">
          {loading ? (
            <p className="py-16 text-center text-sm text-slate-500">Loading…</p>
          ) : error ? (
            <div className="rounded-2xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">
              <p className="font-semibold">Couldn&apos;t load orders.</p>
              <p className="mt-1">{error}</p>
              <p className="mt-3 text-red-600/80">
                Make sure the <code>orders</code> table exists in Supabase (see{' '}
                <code>frontend/supabase/orders.sql</code>) and that your email is in{' '}
                <code>VITE_ADMIN_EMAILS</code>.
              </p>
            </div>
          ) : isReports ? (
            <Reports orders={orders} counts={counts} revenue={revenue} />
          ) : visible.length === 0 ? (
            <div className="py-16 text-center text-sm text-slate-500">No orders here.</div>
          ) : (
            <div className="space-y-4">
              {visible.map((o) => (
                <OrderRow key={o.id} order={o} onStatus={changeStatus} />
              ))}
            </div>
          )}
        </main>
      </div>
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* sidebar                                                             */
/* ------------------------------------------------------------------ */
function Sidebar({ user, onLogout, view, onView, counts, revenue, today }) {
  return (
    <aside className="sticky top-0 z-30 flex shrink-0 flex-col bg-navy-900 text-white lg:h-screen lg:w-64">
      <div className="flex h-16 items-center gap-2 border-b border-white/10 px-5">
        <img src="/images/logo (1).png" alt="bw Superbakeshop" className="h-9 w-auto" />
        <span className="font-brand text-lg font-bold text-white">Superbakeshop</span>
      </div>

      <div className="border-b border-white/10 px-5 py-4">
        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-400">
          Cashier Panel
        </p>
        <p className="mt-1 text-xs text-navy-50/60">{today}</p>
      </div>

      <nav className="flex gap-2 overflow-x-auto p-3 lg:flex-1 lg:flex-col lg:gap-1 lg:overflow-y-auto">
        <p className="hidden px-3 pb-1 pt-2 text-[0.65rem] font-semibold uppercase tracking-wider text-navy-50/40 lg:block">
          Overview
        </p>
        <NavItem
          Icon={ChartIcon}
          label="Reports"
          active={view === 'reports'}
          onClick={() => onView('reports')}
        />
        <Link
          to="/admin/content"
          className="flex shrink-0 items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-navy-50/70 transition hover:bg-white/5 hover:text-white lg:w-full"
        >
          <EditIcon className="h-5 w-5 shrink-0" />
          <span>Site Content</span>
        </Link>
        <Link
          to="/admin/careers"
          className="flex shrink-0 items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-navy-50/70 transition hover:bg-white/5 hover:text-white lg:w-full"
        >
          <CareersIcon className="h-5 w-5 shrink-0" />
          <span>Careers / HR</span>
        </Link>

        <p className="hidden px-3 pb-1 pt-4 text-[0.65rem] font-semibold uppercase tracking-wider text-navy-50/40 lg:block">
          Orders
        </p>
        {ORDER_NAV.map((n) => (
          <NavItem
            key={n.key}
            Icon={n.Icon}
            label={n.label}
            count={counts[n.key] ?? 0}
            active={view === n.key}
            onClick={() => onView(n.key)}
          />
        ))}
      </nav>

      <div className="hidden border-t border-white/10 px-5 py-4 lg:block">
        <p className="text-xs text-navy-50/60">Revenue (excl. cancelled)</p>
        <p className="mt-0.5 text-2xl font-bold text-brand-400">{peso(revenue)}</p>
      </div>

      <div className="border-t border-white/10 px-5 py-4">
        <div className="flex items-center gap-3">
          <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-bold">
            {(user?.email || 'A').charAt(0).toUpperCase()}
          </span>
          <span className="min-w-0">
            <span className="block truncate text-sm font-semibold">{user?.name || 'Admin'}</span>
            <span className="block truncate text-xs text-navy-50/60">{user?.email}</span>
          </span>
        </div>
        <div className="mt-3 flex gap-2">
          <Link
            to="/dashboard"
            className="flex-1 rounded-lg bg-white/10 py-2 text-center text-xs font-semibold transition hover:bg-white/20"
          >
            Storefront
          </Link>
          <button
            onClick={onLogout}
            className="flex-1 rounded-lg bg-white/10 py-2 text-center text-xs font-semibold transition hover:bg-brand-600"
          >
            Logout
          </button>
        </div>
      </div>
    </aside>
  )
}

function NavItem({ Icon, label, count, active, onClick }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`flex shrink-0 items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition lg:w-full ${
        active
          ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-lg shadow-brand-500/30'
          : 'text-navy-50/70 hover:bg-white/5 hover:text-white'
      }`}
    >
      <Icon className="h-5 w-5 shrink-0" />
      <span>{label}</span>
      {count !== undefined && (
        <span
          className={`ml-auto rounded-full px-2 py-0.5 text-xs font-bold ${
            active ? 'bg-white/25 text-white' : 'bg-white/10 text-navy-50/70'
          }`}
        >
          {count}
        </span>
      )}
    </button>
  )
}

/* ------------------------------------------------------------------ */
/* reports                                                            */
/* ------------------------------------------------------------------ */
function Reports({ orders, counts, revenue }) {
  const nonCancelled = orders.filter((o) => o.status !== 'cancelled')
  const avgOrder = nonCancelled.length ? revenue / nonCancelled.length : 0

  const todayStr = new Date().toDateString()
  const todays = orders.filter(
    (o) => o.created_at && new Date(o.created_at).toDateString() === todayStr,
  )
  const todayRevenue = todays
    .filter((o) => o.status !== 'cancelled')
    .reduce((sum, o) => sum + Number(o.total || 0), 0)

  // top selling items
  const itemMap = {}
  orders.forEach((o) =>
    (Array.isArray(o.items) ? o.items : []).forEach((i) => {
      const m = (itemMap[i.name] = itemMap[i.name] || { qty: 0, rev: 0 })
      m.qty += i.qty || 0
      m.rev += (i.price || 0) * (i.qty || 0)
    }),
  )
  const topItems = Object.entries(itemMap)
    .map(([name, v]) => ({ name, ...v }))
    .sort((a, b) => b.qty - a.qty)
    .slice(0, 6)

  const breakdown = [
    { key: 'pending', label: 'Pending', color: 'bg-amber-400' },
    { key: 'preparing', label: 'Preparing', color: 'bg-blue-400' },
    { key: 'completed', label: 'Completed', color: 'bg-green-500' },
    { key: 'cancelled', label: 'Cancelled', color: 'bg-red-400' },
  ]
  const maxStatus = Math.max(1, ...breakdown.map((b) => counts[b.key] || 0))

  return (
    <div className="space-y-6">
      {/* KPI cards */}
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Kpi label="Total Orders" value={counts.all} />
        <Kpi label="Revenue" value={peso(revenue)} accent />
        <Kpi label="Avg. Order Value" value={peso(avgOrder)} />
        <Kpi label="Completed" value={counts.completed} />
      </div>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Kpi label="Today's Orders" value={todays.length} />
        <Kpi label="Today's Revenue" value={peso(todayRevenue)} accent />
        <Kpi label="Pending" value={counts.pending} />
        <Kpi label="Preparing" value={counts.preparing} />
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* status breakdown */}
        <Card title="Orders by Status">
          <div className="space-y-4">
            {breakdown.map((b) => {
              const value = counts[b.key] || 0
              return (
                <div key={b.key}>
                  <div className="mb-1 flex justify-between text-sm">
                    <span className="text-slate-600">{b.label}</span>
                    <span className="font-semibold text-navy-800">{value}</span>
                  </div>
                  <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                    <div
                      className={`h-full rounded-full ${b.color}`}
                      style={{ width: `${(value / maxStatus) * 100}%` }}
                    />
                  </div>
                </div>
              )
            })}
          </div>
        </Card>

        {/* top sellers */}
        <Card title="Top Selling Items">
          {topItems.length === 0 ? (
            <p className="py-8 text-center text-sm text-slate-400">No sales yet.</p>
          ) : (
            <div className="divide-y divide-slate-100">
              {topItems.map((it, i) => (
                <div key={it.name} className="flex items-center gap-3 py-2.5">
                  <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-navy-50 text-xs font-bold text-navy-700">
                    {i + 1}
                  </span>
                  <span className="min-w-0 flex-1 truncate text-sm font-medium text-navy-800">
                    {it.name}
                  </span>
                  <span className="text-sm text-slate-500">{it.qty} sold</span>
                  <span className="w-20 text-right text-sm font-semibold text-brand-600">
                    {peso(it.rev)}
                  </span>
                </div>
              ))}
            </div>
          )}
        </Card>
      </div>
    </div>
  )
}

function Kpi({ label, value, accent }) {
  return (
    <div className="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
      <p className="text-xs font-medium uppercase tracking-wide text-slate-400">{label}</p>
      <p className={`mt-1 text-2xl font-bold ${accent ? 'text-brand-600' : 'text-navy-800'}`}>
        {value}
      </p>
    </div>
  )
}

function Card({ title, children }) {
  return (
    <div className="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
      <h3 className="mb-4 text-sm font-bold text-navy-800">{title}</h3>
      {children}
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* order row                                                          */
/* ------------------------------------------------------------------ */
function OrderRow({ order, onStatus }) {
  const [open, setOpen] = useState(false)
  const items = Array.isArray(order.items) ? order.items : []
  const count = items.reduce((sum, i) => sum + (i.qty || 0), 0)
  const date = order.created_at ? new Date(order.created_at).toLocaleString() : ''

  return (
    <div className="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <p className="text-base font-bold uppercase tracking-wide text-green-600">
            Order #{String(order.id).slice(0, 8).toUpperCase()}
          </p>
          <div className="mt-0.5 flex items-center gap-2">
            <h3 className="font-semibold text-navy-800">{order.customer_name || 'Customer'}</h3>
            <span
              className={`rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ${
                STATUS_STYLES[order.status] || 'bg-slate-100 text-slate-600'
              }`}
            >
              {order.status}
            </span>
          </div>
          <p className="text-xs text-slate-500">{order.customer_email}</p>
          {order.customer_phone && (
            <p className="text-xs text-slate-500">{order.customer_phone}</p>
          )}
          <p className="mt-1 text-xs text-slate-400">{date}</p>
          <div className="mt-1.5 flex flex-wrap gap-1.5">
            <span className="rounded-full bg-navy-50 px-2 py-0.5 text-[0.65rem] font-semibold text-navy-700">
              {order.delivery_type === 'pickup' ? '🏪 Pickup' : '🚚 Delivery'}
              {order.delivery_type !== 'pickup' && order.delivery_speed
                ? ` · ${order.delivery_speed}`
                : ''}
            </span>
            {order.payment_method && (
              <span className="rounded-full bg-navy-50 px-2 py-0.5 text-[0.65rem] font-semibold text-navy-700">
                {PAYMENT_LABEL[order.payment_method] || order.payment_method}
              </span>
            )}
            {order.payment_status && (
              <span
                className={`rounded-full px-2 py-0.5 text-[0.65rem] font-semibold ${
                  order.payment_status === 'paid'
                    ? 'bg-green-100 text-green-700'
                    : 'bg-amber-100 text-amber-700'
                }`}
              >
                {order.payment_status === 'paid' ? 'Paid' : 'Unpaid'}
              </span>
            )}
          </div>
        </div>
        <div className="text-right">
          <p className="text-lg font-bold text-brand-600">{peso(order.total)}</p>
          <p className="text-xs text-slate-500">
            {count} item{count === 1 ? '' : 's'}
          </p>
        </div>
      </div>

      <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
        <button
          onClick={() => setOpen((v) => !v)}
          className="text-sm font-medium text-brand-600 hover:underline"
        >
          {open ? 'Hide items' : 'View items'}
        </button>
        <div className="flex items-center gap-2">
          <span className="text-xs text-slate-400">Set status:</span>
          <select
            value={order.status}
            onChange={(e) => onStatus(order.id, e.target.value)}
            className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-brand-500"
          >
            <option value="pending">Pending</option>
            <option value="preparing">Preparing</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      {open && (
        <div className="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
          {(order.address || order.notes) && (
            <div className="mb-2 rounded-lg bg-navy-50/60 p-3 text-xs text-slate-600">
              {order.address && (
                <p>
                  <span className="font-semibold text-navy-700">📍 Address:</span> {order.address}
                </p>
              )}
              {order.notes && (
                <p className="mt-1">
                  <span className="font-semibold text-navy-700">📝 Notes:</span> {order.notes}
                </p>
              )}
            </div>
          )}
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

/* ------------------------------------------------------------------ */
/* icons                                                              */
/* ------------------------------------------------------------------ */
function base(props) {
  return {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 2,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    'aria-hidden': true,
    ...props,
  }
}
function ChartIcon(p) {
  return (
    <svg {...base(p)}>
      <line x1="12" y1="20" x2="12" y2="10" />
      <line x1="18" y1="20" x2="18" y2="4" />
      <line x1="6" y1="20" x2="6" y2="16" />
    </svg>
  )
}
function CareersIcon(p) {
  return (
    <svg {...base(p)}>
      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
      <circle cx="9" cy="7" r="4" />
      <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
      <path d="M16 3.13a4 4 0 0 1 0 7.75" />
    </svg>
  )
}
function EditIcon(p) {
  return (
    <svg {...base(p)}>
      <path d="M12 20h9" />
      <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
    </svg>
  )
}
function ListIcon(p) {
  return (
    <svg {...base(p)}>
      <line x1="8" y1="6" x2="21" y2="6" />
      <line x1="8" y1="12" x2="21" y2="12" />
      <line x1="8" y1="18" x2="21" y2="18" />
      <line x1="3" y1="6" x2="3.01" y2="6" />
      <line x1="3" y1="12" x2="3.01" y2="12" />
      <line x1="3" y1="18" x2="3.01" y2="18" />
    </svg>
  )
}
function ClockIcon(p) {
  return (
    <svg {...base(p)}>
      <circle cx="12" cy="12" r="9" />
      <polyline points="12 7 12 12 15 14" />
    </svg>
  )
}
function UtensilsIcon(p) {
  return (
    <svg {...base(p)}>
      <path d="M3 2v7a3 3 0 0 0 3 3v10" />
      <path d="M9 2v7a3 3 0 0 1-3 3" />
      <path d="M6 2v6" />
      <path d="M18 2c-1.7 0-3 1.8-3 4s1.3 4 3 4v11" />
    </svg>
  )
}
function CheckIcon(p) {
  return (
    <svg {...base(p)}>
      <circle cx="12" cy="12" r="9" />
      <polyline points="8.5 12 11 14.5 15.5 9.5" />
    </svg>
  )
}
function BanIcon(p) {
  return (
    <svg {...base(p)}>
      <circle cx="12" cy="12" r="9" />
      <line x1="5.6" y1="5.6" x2="18.4" y2="18.4" />
    </svg>
  )
}
