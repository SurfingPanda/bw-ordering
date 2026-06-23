import api from './api'

// Store-locator branches. The "Find a Store" page (Stores.jsx) reads the public
// list directly; this module adds the admin/editor list + sync used by the Site
// Editor. Admin/editor only for the write path.

export async function fetchStores() {
  const { data } = await api.get('/stores')
  return (data || []).map((s) => ({
    id: s.id,
    name: s.name || '',
    region: s.region || '',
    address: s.address || '',
    hours: s.hours || '',
    phone: s.phone || '',
    latitude: s.latitude ?? '',
    longitude: s.longitude ?? '',
  }))
}

// Update existing (by id), insert new, delete any removed id. `originalIds` is
// the id list loaded into the editor. Returns the fresh list.
export async function syncStores(stores, originalIds = []) {
  const num = (v) => (v === '' || v === null || v === undefined ? null : Number(v))
  const { data } = await api.post('/stores/sync', {
    stores: stores.map((s) => ({
      id: s.id || null,
      name: s.name,
      region: s.region,
      address: s.address,
      hours: s.hours,
      phone: s.phone,
      latitude: num(s.latitude),
      longitude: num(s.longitude),
    })),
    originalIds,
  })
  return data || []
}
