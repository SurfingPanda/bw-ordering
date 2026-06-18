import { supabase } from './supabase'

// Insert a new order for the signed-in user. `summary` carries the line items
// and computed totals from the cart. Throws on error so the UI can react.
export async function createOrder(summary) {
  const {
    data: { user },
  } = await supabase.auth.getUser()
  if (!user) throw new Error('You must be signed in to place an order.')

  const meta = user.user_metadata || {}
  const { data, error } = await supabase
    .from('orders')
    .insert({
      user_id: user.id,
      customer_name: meta.full_name || meta.name || user.email?.split('@')[0] || 'Customer',
      customer_email: user.email,
      items: summary.items,
      subtotal: summary.subtotal,
      discount: summary.discount,
      delivery: summary.delivery,
      vat: summary.vat,
      total: summary.total,
      voucher: summary.voucher || null,
      status: 'pending',
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
