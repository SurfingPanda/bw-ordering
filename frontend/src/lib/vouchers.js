import api from './api'

// Voucher management for the Site Editor. The order-pricing logic validates
// codes against this same table server-side, so these are the real, applied
// vouchers. Admin/editor only.

// Public: active vouchers as a { CODE: { type, value, label } } map for the
// client-side checkout preview. The server re-validates on order creation.
export async function fetchActiveVouchers() {
  const { data } = await api.get('/vouchers/active')
  const map = {}
  ;(data || []).forEach((v) => {
    map[v.code] = { type: v.type, value: Number(v.value) || 0, label: v.label || '' }
  })
  return map
}

export async function fetchVouchers() {
  const { data } = await api.get('/vouchers')
  return (data || []).map((v) => ({
    id: v.id,
    code: v.code,
    type: v.type, // percent | amount | freedel
    value: Number(v.value) || 0,
    label: v.label || '',
    active: !!v.active,
    expiresAt: v.expires_at ? String(v.expires_at).slice(0, 10) : '',
  }))
}

// Update existing (by id), insert new, delete any removed id. `originalIds` is
// the id list loaded into the editor. Returns the fresh list.
export async function syncVouchers(vouchers, originalIds = []) {
  const { data } = await api.post('/vouchers/sync', {
    vouchers: vouchers.map((v) => ({
      id: v.id || null,
      code: v.code,
      type: v.type,
      value: v.value,
      label: v.label,
      active: v.active,
      expires_at: v.expiresAt || null,
    })),
    originalIds,
  })
  return data || []
}
