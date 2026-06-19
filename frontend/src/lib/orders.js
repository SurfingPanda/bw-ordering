import { supabase } from './supabase'

// Insert a new order for the signed-in user. We only send the line items
// (product_id + qty) and an optional voucher code — all money fields are
// recomputed server-side from trusted prices by the `orders_compute_totals`
// trigger (see frontend/supabase/orders_integrity.sql), so a tampered client
// can't dictate prices, discounts, or totals. The returned row carries the
// authoritative, server-computed totals. Throws on error so the UI can react.
export async function createOrder(summary) {
  const {
    data: { user },
  } = await supabase.auth.getUser()
  if (!user) throw new Error('You must be signed in to place an order.')

  const meta = user.user_metadata || {}
  const items = (summary.items || []).map((i) => ({
    product_id: i.product_id,
    name: i.name,
    qty: i.qty,
  }))

  const { data, error } = await supabase
    .from('orders')
    .insert({
      user_id: user.id,
      customer_name: meta.full_name || meta.name || user.email?.split('@')[0] || 'Customer',
      customer_email: user.email,
      items,
      voucher: summary.voucher || null,
      // subtotal / discount / delivery / vat / total / status are set by the
      // DB trigger from the trusted catalogue — intentionally not sent here.
    })
    .select()
    .single()

  if (error) throw error
  return data
}

// Admin: fetch every order, newest first.
export async function fetchAllOrders() {
  const { data, error } = await supabase
    .from('orders')
    .select('*')
    .order('created_at', { ascending: false })

  if (error) throw error
  return data || []
}

// Admin: update an order's status (pending → preparing → completed / cancelled).
export async function updateOrderStatus(id, status) {
  const { error } = await supabase.from('orders').update({ status }).eq('id', id)
  if (error) throw error
}
