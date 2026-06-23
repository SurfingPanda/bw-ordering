import api from './api'

// Orders live in the Laravel API (MySQL) now — Supabase is only used for auth.
// The Supabase access token is attached by the axios interceptor in api.js, so
// the server knows who the user is. All money fields are recomputed server-side
// from the trusted products + vouchers tables (see backend OrderController), so
// a tampered client can only choose products + quantities, never prices.

// Insert a new order for the signed-in user. Sends only line items
// (product_id + qty + name) and an optional voucher code. Returns the row with
// the authoritative, server-computed totals. Throws on error so the UI reacts.
export async function createOrder(summary) {
  const { data } = await api.post('/orders', {
    items: (summary.items || []).map((i) => ({
      product_id: i.product_id,
      name: i.name,
      qty: i.qty,
    })),
    voucher: summary.voucher || null,
    payment_method: summary.payment_method || null,
    delivery_type: summary.delivery_type || null,
    delivery_speed: summary.delivery_speed || null,
    address: summary.address || null,
    phone: summary.phone || null,
    notes: summary.notes || null,
  })
  return data
}

// Customer: the signed-in user's own orders, newest first.
export async function fetchMyOrders() {
  const { data } = await api.get('/orders/mine')
  return data || []
}

// Admin: fetch every order, newest first.
export async function fetchAllOrders() {
  const { data } = await api.get('/orders')
  return data || []
}

// Admin: update an order's status (pending → preparing → completed / cancelled).
export async function updateOrderStatus(id, status) {
  await api.patch(`/orders/${id}/status`, { status })
}
