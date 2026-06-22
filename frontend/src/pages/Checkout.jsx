import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { createOrder } from '../lib/orders'

// Multi-section checkout page reached from the Menu cart ("Proceed to
// Checkout"). The cart summary is handed over via localStorage (key
// `bw_checkout`) so it survives a refresh. Totals shown here are a preview;
// the server recomputes the authoritative totals on createOrder.

const peso = (n) =>
  `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

const VAT_RATE = 0.12
const DELIVERY_FEE = 79
const EXPRESS_FEE = 149
const FREE_DELIVERY_MIN = 1000

const VOUCHERS = {
  BW10: { type: 'percent', value: 10, label: '10% off' },
  SAVE50: { type: 'amount', value: 50, label: '₱50 off' },
  FREEDEL: { type: 'freedel', label: 'Free delivery' },
}

const STEPS = ['Cart', 'Delivery', 'Details', 'Payment', 'Confirmation']

function readCheckout() {
  if (typeof localStorage === 'undefined') return null
  try {
    return JSON.parse(localStorage.getItem('bw_checkout') || 'null')
  } catch {
    return null
  }
}

export default function Checkout() {
  const { user } = useAuth()

  const [payload] = useState(readCheckout)
  const items = useMemo(() => payload?.items || [], [payload])

  const [mode, setMode] = useState('delivery') // 'delivery' | 'pickup'
  const [speed, setSpeed] = useState('standard') // 'standard' | 'express'
  const [name, setName] = useState(user?.name || '')
  const [phone, setPhone] = useState(user?.contact_number || '')
  const [email, setEmail] = useState(user?.email || '')
  const [notes, setNotes] = useState('')

  const [code, setCode] = useState('')
  const [voucher, setVoucher] = useState(payload?.voucher ? { code: payload.voucher } : null)
  const [voucherError, setVoucherError] = useState('')

  const [placing, setPlacing] = useState(false)
  const [error, setError] = useState('')

  // Wizard step: 'form' (Delivery + Details) → 'payment' → 'done' (Confirmation).
  const [step, setStep] = useState('form')
  const [payMethod, setPayMethod] = useState('cod') // cod | gcash | card
  const [placedOrder, setPlacedOrder] = useState(null)
  const stepIndex = step === 'payment' ? 3 : step === 'done' ? 4 : 1

  const subtotal = useMemo(
    () => items.reduce((s, i) => s + Number(i.price || 0) * i.qty, 0),
    [items],
  )

  const def = voucher ? VOUCHERS[voucher.code] : null
  let discount = 0
  if (def?.type === 'percent') discount = (subtotal * def.value) / 100
  else if (def?.type === 'amount') discount = Math.min(def.value, subtotal)

  const discounted = subtotal - discount
  const freeDelivery = subtotal >= FREE_DELIVERY_MIN || def?.type === 'freedel'
  let delivery = 0
  if (mode === 'delivery') {
    delivery = speed === 'express' ? EXPRESS_FEE : freeDelivery ? 0 : DELIVERY_FEE
  }
  const vat = discounted * VAT_RATE
  const total = discounted + vat + delivery
  const points = Math.floor(discounted / 10)
  const awayFromFree = Math.max(0, FREE_DELIVERY_MIN - subtotal)

  const applyVoucher = () => {
    const key = code.trim().toUpperCase()
    if (!key) return
    if (!VOUCHERS[key]) {
      setVoucher(null)
      setVoucherError('Invalid voucher code')
      return
    }
    setVoucher({ code: key })
    setVoucherError('')
    setCode('')
  }

  const goToPayment = () => {
    if (!name.trim() || !phone.trim() || !email.trim()) {
      setError('Please fill in your name, mobile number, and email.')
      return
    }
    setError('')
    setStep('payment')
    if (typeof window !== 'undefined') window.scrollTo({ top: 0 })
  }

  const placeOrder = async () => {
    setError('')
    setPlacing(true)
    try {
      const order = await createOrder({
        items: items.map((i) => ({ product_id: i.product_id, name: i.name, qty: i.qty })),
        voucher: voucher?.code || null,
      })
      try {
        localStorage.removeItem('bw_checkout')
        localStorage.removeItem('bw_cart')
      } catch {
        // best-effort
      }
      setPlacedOrder(order)
      setStep('done')
      if (typeof window !== 'undefined') window.scrollTo({ top: 0 })
    } catch (err) {
      setError(err.message || "Sorry, we couldn't place your order.")
    } finally {
      setPlacing(false)
    }
  }

  if (!items.length) {
    return (
      <div className="flex min-h-screen flex-col items-center justify-center gap-4 bg-navy-50/40 px-4 text-center">
        <p className="text-lg font-semibold text-navy-800">Your cart is empty.</p>
        <p className="text-sm text-slate-500">Add some treats before checking out.</p>
        <Link
          to="/menu"
          className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
        >
          Browse the menu
        </Link>
      </div>
    )
  }

  const itemCount = items.reduce((s, i) => s + i.qty, 0)

  return (
    <div className="min-h-screen bg-navy-50/40 text-navy-800">
      {/* stepper bar */}
      <header className="sticky top-0 z-20 border-b border-slate-200 bg-white">
        <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
          <div className="flex min-w-0 items-center gap-4">
            <Link to="/" className="shrink-0">
              <img src="/images/logo (1).png" alt="bw Superbakeshop" className="h-9 w-auto" />
            </Link>
            <Stepper current={stepIndex} />
          </div>
          {step !== 'done' && (
            <Link
              to="/menu"
              className="flex shrink-0 items-center gap-2 rounded-full bg-navy-800 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-600"
            >
              <CartIcon className="h-4 w-4" />
              Cart ({itemCount})
            </Link>
          )}
        </div>
      </header>

      {step === 'done' ? (
        <Confirmation order={placedOrder} payMethod={payMethod} />
      ) : (
      <main className="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_22rem]">
        {/* ---- left column ---- */}
        <div className="space-y-6">
          {step === 'form' && (
          <>
          {/* 1. Delivery or Pickup */}
          <Section step="1" title="Delivery or Pickup">
            <div className="grid grid-cols-2 gap-3">
              <ModeCard
                active={mode === 'delivery'}
                onClick={() => setMode('delivery')}
                icon="🚚"
                title="Delivery"
                subtitle="We'll deliver to your door"
              />
              <ModeCard
                active={mode === 'pickup'}
                onClick={() => setMode('pickup')}
                icon="🏪"
                title="Pickup"
                subtitle="Pick up at a BW branch"
              />
            </div>

            {mode === 'delivery' ? (
              <>
                <div className="mt-5 rounded-xl border border-slate-200 p-4">
                  <div className="flex items-start justify-between gap-3">
                    <div className="flex items-start gap-3">
                      <span className="mt-0.5 text-brand-500">
                        <PinIcon className="h-5 w-5" />
                      </span>
                      <div>
                        <p className="text-sm font-semibold text-navy-800">Delivery address</p>
                        <p className="text-xs text-slate-500">
                          Add the address where we should deliver your order.
                        </p>
                      </div>
                    </div>
                    <button className="text-xs font-semibold text-brand-600 hover:underline">
                      Change
                    </button>
                  </div>
                </div>

                <p className="mt-5 mb-2 text-sm font-semibold text-navy-800">Delivery option</p>
                <div className="grid gap-3 sm:grid-cols-2">
                  <OptionCard
                    active={speed === 'standard'}
                    onClick={() => setSpeed('standard')}
                    title="Standard Delivery"
                    note="30–45 mins"
                    price={freeDelivery ? 'FREE' : peso(DELIVERY_FEE)}
                  />
                  <OptionCard
                    active={speed === 'express'}
                    onClick={() => setSpeed('express')}
                    title="Express Delivery"
                    note="15–25 mins"
                    price={peso(EXPRESS_FEE)}
                  />
                </div>

                {awayFromFree > 0 ? (
                  <div className="mt-4 rounded-xl border border-green-200 bg-green-50 p-3">
                    <p className="text-xs font-medium text-green-700">
                      🎉 You&apos;re only {peso(awayFromFree)} away from FREE delivery!
                    </p>
                    <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-green-100">
                      <div
                        className="h-full rounded-full bg-green-500"
                        style={{ width: `${Math.min(100, (subtotal / FREE_DELIVERY_MIN) * 100)}%` }}
                      />
                    </div>
                  </div>
                ) : (
                  <div className="mt-4 rounded-xl border border-green-200 bg-green-50 p-3">
                    <p className="text-xs font-medium text-green-700">
                      🎉 You&apos;ve unlocked FREE standard delivery!
                    </p>
                  </div>
                )}
              </>
            ) : (
              <div className="mt-5 rounded-xl border border-slate-200 p-4 text-sm text-slate-600">
                Pick up your order at your nearest <span className="font-semibold">BW Superbakeshop</span>{' '}
                branch — no delivery fee. Find a store on the{' '}
                <Link to="/stores" className="font-semibold text-brand-600 hover:underline">
                  store locator
                </Link>
                .
              </div>
            )}
          </Section>

          {/* 2. Customer Details */}
          <Section step="2" title="Customer Details">
            <div className="grid gap-4 sm:grid-cols-2">
              <FieldInput label="Full Name" value={name} onChange={setName} placeholder="Juan Dela Cruz" />
              <FieldInput
                label="Mobile Number"
                value={phone}
                onChange={setPhone}
                placeholder="0917 123 4567"
                type="tel"
              />
              <div className="sm:col-span-2">
                <FieldInput
                  label="Email Address"
                  value={email}
                  onChange={setEmail}
                  placeholder="you@email.com"
                  type="email"
                />
              </div>
            </div>
          </Section>

          {/* 3. Order Notes */}
          <Section step="3" title="Order Notes (Optional)">
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              rows={3}
              placeholder="Any special requests? (e.g. message on the cake, allergies, gate code)"
              className="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
            />
          </Section>

          {error && (
            <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {error}
            </div>
          )}

          <button
            type="button"
            onClick={goToPayment}
            className="flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
          >
            Continue to Payment
            <ArrowIcon className="h-4 w-4" />
          </button>
          <Link to="/menu" className="block text-center text-sm font-medium text-slate-500 hover:text-brand-600">
            ← Back to Cart
          </Link>
          </>
          )}

          {step === 'payment' && (
          <>
          {/* 4. Payment */}
          <Section step="4" title="Payment Method">
            <div className="space-y-3">
              <PayCard
                active={payMethod === 'cod'}
                onClick={() => setPayMethod('cod')}
                icon="💵"
                title={mode === 'pickup' ? 'Pay at Store' : 'Cash on Delivery'}
                subtitle={mode === 'pickup' ? 'Pay when you pick up your order' : 'Pay the rider when your order arrives'}
              />
              <PayCard
                active={payMethod === 'gcash'}
                onClick={() => setPayMethod('gcash')}
                icon="📱"
                title="GCash"
                subtitle="Pay via GCash e-wallet"
              />
              <PayCard
                active={payMethod === 'qrph'}
                onClick={() => setPayMethod('qrph')}
                icon="🔳"
                title="QRPH"
                subtitle="Scan with any bank or e-wallet app"
              />
            </div>

            {payMethod === 'qrph' && (
              <div className="mt-4 flex flex-col items-center gap-3 rounded-xl border border-slate-200 p-5 text-center">
                <div className="flex h-40 w-40 items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-white text-6xl text-navy-800">
                  🔳
                </div>
                <p className="text-sm font-semibold text-navy-800">Scan to pay {peso(total)}</p>
                <p className="text-xs text-slate-500">
                  Open your bank or e-wallet app, scan this QRPH code, then place your order.
                  (Demo — no real charge is made.)
                </p>
              </div>
            )}

            {payMethod === 'gcash' && (
              <p className="mt-4 rounded-xl border border-slate-200 p-4 text-sm text-slate-600">
                You&apos;ll be prompted to confirm the payment in your GCash app. (Demo — no real charge.)
              </p>
            )}
          </Section>

          {error && (
            <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {error}
            </div>
          )}

          <button
            type="button"
            onClick={placeOrder}
            disabled={placing}
            className="flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:cursor-not-allowed disabled:opacity-60"
          >
            {placing ? 'Placing order…' : `Place Order · ${peso(total)}`}
            {!placing && <ArrowIcon className="h-4 w-4" />}
          </button>
          <button
            type="button"
            onClick={() => setStep('form')}
            className="block w-full text-center text-sm font-medium text-slate-500 hover:text-brand-600"
          >
            ← Back to details
          </button>
          </>
          )}
        </div>

        {/* ---- right column: order summary ---- */}
        <aside className="space-y-4 lg:sticky lg:top-24 lg:self-start">
          <div className="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <div className="flex items-center justify-between">
              <h2 className="text-base font-bold text-navy-800">Order Summary</h2>
              <span className="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-600">
                {itemCount} item{itemCount === 1 ? '' : 's'}
              </span>
            </div>

            <ul className="mt-4 space-y-3">
              {items.map((i) => (
                <li key={i.product_id} className="flex items-center gap-3">
                  <span className="relative h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                    {i.img && <img src={i.img} alt={i.name} className="h-full w-full object-cover" />}
                    <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-navy-800 px-1 text-[0.6rem] font-bold text-white">
                      {i.qty}
                    </span>
                  </span>
                  <span className="min-w-0 flex-1 truncate text-sm font-medium text-navy-800">
                    {i.name}
                  </span>
                  <span className="text-sm font-semibold text-navy-800">
                    {peso(Number(i.price || 0) * i.qty)}
                  </span>
                </li>
              ))}
            </ul>

            <Link
              to="/menu"
              className="mt-3 inline-block text-xs font-semibold text-brand-600 hover:underline"
            >
              + Add more items
            </Link>

            {/* voucher */}
            <div className="mt-4 border-t border-slate-100 pt-4">
              <div className="flex gap-2">
                <input
                  value={code}
                  onChange={(e) => setCode(e.target.value)}
                  placeholder="Voucher code"
                  className="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
                <button
                  type="button"
                  onClick={applyVoucher}
                  className="shrink-0 rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600"
                >
                  Apply
                </button>
              </div>
              {voucherError && <p className="mt-1 text-xs text-red-600">{voucherError}</p>}
              {voucher && !voucherError && (
                <p className="mt-1 text-xs font-medium text-green-600">
                  🎟️ {voucher.code} applied
                  <button onClick={() => setVoucher(null)} className="ml-2 text-slate-400 hover:text-slate-600">
                    remove
                  </button>
                </p>
              )}
            </div>

            {/* totals */}
            <div className="mt-4 space-y-1.5 border-t border-slate-100 pt-4 text-sm">
              <Row label="Subtotal" value={peso(subtotal)} />
              {discount > 0 && <Row label="Discount" value={`−${peso(discount)}`} green />}
              <Row
                label={mode === 'pickup' ? 'Pickup' : 'Delivery Fee'}
                value={delivery === 0 ? 'FREE' : peso(delivery)}
                green={delivery === 0}
              />
              <Row label="VAT (12%)" value={peso(vat)} />
              <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-navy-800">
                <span>Total</span>
                <span>{peso(total)}</span>
              </div>
              <div className="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                ⭐ Earn {points} point{points === 1 ? '' : 's'} with this order!
              </div>
            </div>
          </div>

          {/* trust badges */}
          <div className="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
            <Trust icon="🔒" title="Safe & Secure" subtitle="Your details are protected" />
            <Trust icon="🥖" title="100% Fresh" subtitle="Baked fresh on order" />
            <Trust icon="💛" title="Quality You Can Trust" subtitle="Since 1966" last />
          </div>
        </aside>
      </main>
      )}
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* pieces                                                             */
/* ------------------------------------------------------------------ */

function Stepper({ current }) {
  return (
    <ol className="flex items-center gap-1 overflow-x-auto text-xs">
      {STEPS.map((label, i) => {
        const done = i < current
        const active = i === current
        return (
          <li key={label} className="flex shrink-0 items-center gap-1">
            <span
              className={`flex h-6 w-6 items-center justify-center rounded-full text-[0.65rem] font-bold ${
                active
                  ? 'bg-brand-500 text-white'
                  : done
                    ? 'bg-green-500 text-white'
                    : 'bg-slate-200 text-slate-500'
              }`}
            >
              {done ? '✓' : i + 1}
            </span>
            <span
              className={`hidden font-semibold sm:inline ${
                active ? 'text-navy-800' : 'text-slate-400'
              }`}
            >
              {label}
            </span>
            {i < STEPS.length - 1 && <span className="mx-1 h-px w-4 bg-slate-200 sm:w-6" />}
          </li>
        )
      })}
    </ol>
  )
}

function PayCard({ active, onClick, icon, title, subtitle }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`flex w-full items-center gap-3 rounded-xl border p-4 text-left transition ${
        active ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-500/20' : 'border-slate-200 hover:border-brand-200'
      }`}
    >
      <span className="text-2xl">{icon}</span>
      <span className="flex-1">
        <span className="block text-sm font-semibold text-navy-800">{title}</span>
        <span className="block text-xs text-slate-500">{subtitle}</span>
      </span>
      <span
        className={`flex h-5 w-5 items-center justify-center rounded-full border-2 ${
          active ? 'border-brand-500 bg-brand-500 text-white' : 'border-slate-300'
        }`}
      >
        {active && <span className="text-[0.6rem]">✓</span>}
      </span>
    </button>
  )
}

const PAY_LABEL = { cod: 'Cash on Delivery', gcash: 'GCash', qrph: 'QRPH' }

function Confirmation({ order, payMethod }) {
  const ref = order ? String(order.id).slice(0, 8).toUpperCase() : '—'
  return (
    <main className="mx-auto max-w-xl px-4 py-12 sm:px-6">
      <div className="rounded-3xl border border-slate-100 bg-white p-8 text-center shadow-sm sm:p-10">
        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-3xl">
          ✓
        </div>
        <h1 className="mt-5 text-2xl font-bold text-navy-800">Order placed!</h1>
        <p className="mt-2 text-sm text-slate-500">
          Thank you — we&apos;ve received your order and our bakers are on it. 🧡
        </p>

        <dl className="mx-auto mt-6 max-w-xs space-y-2 rounded-2xl bg-navy-50/60 p-5 text-sm">
          <div className="flex justify-between">
            <dt className="text-slate-500">Order #</dt>
            <dd className="font-semibold text-navy-800">{ref}</dd>
          </div>
          <div className="flex justify-between">
            <dt className="text-slate-500">Status</dt>
            <dd className="font-semibold capitalize text-amber-600">{order?.status || 'pending'}</dd>
          </div>
          <div className="flex justify-between">
            <dt className="text-slate-500">Payment</dt>
            <dd className="font-semibold text-navy-800">{PAY_LABEL[payMethod] || 'Cash'}</dd>
          </div>
          <div className="flex justify-between border-t border-slate-200 pt-2 text-base">
            <dt className="font-bold text-navy-800">Total</dt>
            <dd className="font-bold text-brand-600">{peso(order?.total)}</dd>
          </div>
        </dl>

        <div className="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-center">
          <Link
            to="/my-orders"
            className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
          >
            View my orders
          </Link>
          <Link
            to="/menu"
            className="rounded-full border border-slate-300 px-7 py-3 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600"
          >
            Order more
          </Link>
        </div>
      </div>
    </main>
  )
}

function Section({ step, title, children }) {
  return (
    <section className="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
      <h2 className="mb-4 flex items-center gap-2 text-base font-bold text-navy-800">
        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-xs text-white">
          {step}
        </span>
        {title}
      </h2>
      {children}
    </section>
  )
}

function ModeCard({ active, onClick, icon, title, subtitle }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`flex flex-col items-start gap-1 rounded-xl border p-4 text-left transition ${
        active
          ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-500/20'
          : 'border-slate-200 hover:border-brand-200'
      }`}
    >
      <span className="text-2xl">{icon}</span>
      <span className="text-sm font-semibold text-navy-800">{title}</span>
      <span className="text-xs text-slate-500">{subtitle}</span>
    </button>
  )
}

function OptionCard({ active, onClick, title, note, price }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`flex items-center justify-between rounded-xl border p-3 text-left transition ${
        active ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-500/20' : 'border-slate-200 hover:border-brand-200'
      }`}
    >
      <span>
        <span className="block text-sm font-semibold text-navy-800">{title}</span>
        <span className="block text-xs text-slate-500">{note}</span>
      </span>
      <span className="text-sm font-bold text-brand-600">{price}</span>
    </button>
  )
}

function FieldInput({ label, value, onChange, placeholder, type = 'text' }) {
  return (
    <label className="block">
      <span className="mb-1 block text-xs font-medium text-slate-500">{label}</span>
      <input
        type={type}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
    </label>
  )
}

function Row({ label, value, green }) {
  return (
    <div className="flex justify-between text-slate-600">
      <span>{label}</span>
      <span className={green ? 'font-semibold text-green-600' : ''}>{value}</span>
    </div>
  )
}

function Trust({ icon, title, subtitle, last }) {
  return (
    <div className={`flex items-center gap-3 ${last ? '' : 'border-b border-slate-100 pb-3 mb-3'}`}>
      <span className="text-lg">{icon}</span>
      <span>
        <span className="block text-sm font-semibold text-navy-800">{title}</span>
        <span className="block text-xs text-slate-500">{subtitle}</span>
      </span>
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

function PinIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z" />
      <circle cx="12" cy="10" r="3" />
    </svg>
  )
}

function ArrowIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <line x1="5" y1="12" x2="19" y2="12" />
      <polyline points="12 5 19 12 12 19" />
    </svg>
  )
}
