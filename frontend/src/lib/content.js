import { supabase } from './supabase'

// Toggleable call-to-action buttons on the landing page. The admin "Buttons"
// editor renders a switch per entry; Landing hides a button when its key is set
// to false. Keys default to visible (anything other than an explicit false).
export const LANDING_BUTTONS = [
  { key: 'navSignIn', label: 'Sign In', group: 'Navigation bar' },
  { key: 'navOrder', label: 'Order Now', group: 'Navigation bar' },
  { key: 'bestSellersMenu', label: 'See full menu', group: 'Best Sellers' },
  { key: 'promoOrder', label: 'Order a custom cake', group: 'Promo banner' },
  { key: 'storyStart', label: 'Start ordering', group: 'Our Story' },
  { key: 'storyFindStore', label: 'Find a store', group: 'Our Story' },
  { key: 'storeLocatorFind', label: 'Find a store (+ search box)', group: 'Store locator' },
  { key: 'newsletterSubscribe', label: 'Subscribe form', group: 'Newsletter' },
]

// True unless the button has been explicitly switched off. Unknown keys (e.g.
// buttons added after a save) default to visible.
export function isButtonVisible(content, key) {
  return content?.buttons?.[key] !== false
}

const ALL_BUTTONS_VISIBLE = Object.fromEntries(LANDING_BUTTONS.map((b) => [b.key, true]))

// Editable landing-page content. These defaults render the site even before an
// admin saves anything (or if Supabase isn't set up yet).
export const DEFAULT_CONTENT = {
  announcement:
    '🚚 Free delivery on orders over ₱1,000  •  Freshly baked every morning  •  Order now and taste the love!',
  banners: [
    { img: '/images/Gemini_Generated_Image_wrt1thwrt1thwrt1.png', alt: 'For the Best Dad — cake promo' },
    { img: '/images/Gemini_Generated_Image_wugzcgwugzcgwugz.png', alt: 'For the Best Mom — cake promo' },
  ],
  categories: [
    { name: 'Cakes', img: 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=300&h=300&q=80' },
    { name: 'Breads', img: 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=300&h=300&q=80' },
    { name: 'Pastries', img: 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=300&h=300&q=80' },
    { name: 'Delicacies', img: 'https://images.unsplash.com/photo-1551024506-0bccd828d307?auto=format&fit=crop&w=300&h=300&q=80' },
    { name: 'Cupcakes', img: 'https://images.unsplash.com/photo-1426869981800-95ebf51ce900?auto=format&fit=crop&w=300&h=300&q=80' },
    { name: 'Cookies', img: 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=300&h=300&q=80' },
  ],
  bestSellers: [
    { name: 'Classic Mocha Cake', price: '₱650', tag: 'Best Seller', img: 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?auto=format&fit=crop&w=600&q=80', calories: 420, allergens: ['Gluten', 'Eggs', 'Milk'] },
    { name: 'Ube Chiffon Cake', price: '₱720', tag: 'New', img: 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=600&q=80', calories: 380, allergens: ['Gluten', 'Eggs', 'Milk'] },
    { name: 'Soft Ensaymada', price: '₱45', tag: 'Popular', img: 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=600&q=80', calories: 290, allergens: ['Gluten', 'Eggs', 'Milk'] },
    { name: 'Fresh Pandesal (12pcs)', price: '₱60', tag: 'Daily', img: 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?auto=format&fit=crop&w=600&q=80', calories: 140, allergens: ['Gluten', 'Soy'] },
    { name: 'Chocolate Cupcakes', price: '₱180', tag: 'Best Seller', img: 'https://images.unsplash.com/photo-1426869981800-95ebf51ce900?auto=format&fit=crop&w=600&q=80', calories: 300, allergens: ['Gluten', 'Eggs', 'Milk'] },
    { name: 'Buttery Croissant', price: '₱85', tag: 'Popular', img: 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=600&q=80', calories: 270, allergens: ['Gluten', 'Milk'] },
    { name: 'Red Velvet Slice', price: '₱150', tag: 'New', img: 'https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?auto=format&fit=crop&w=600&q=80', calories: 410, allergens: ['Gluten', 'Eggs', 'Milk'] },
    { name: 'Assorted Cookies', price: '₱220', tag: 'Popular', img: 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=600&q=80', calories: 150, allergens: ['Gluten', 'Eggs', 'Milk'] },
  ],
  buttons: ALL_BUTTONS_VISIBLE,
  // Editable Franchise page content (read by src/pages/Franchise.jsx).
  franchise: {
    hero: {
      eyebrow: 'Own a bakeshop',
      title: 'Bring bw Superbakeshop to your city',
      subtitle:
        'Partner with a trusted, decades-old brand and turn your community’s love for fresh bread and cakes into a thriving business.',
    },
    email: 'franchise@bwsuperbakeshop.com',
    perks: [
      { icon: '🧡', title: 'A Trusted Name', text: 'Partner with an established bakeshop brand and a loyal, ever-growing customer base.' },
      { icon: '👨‍🍳', title: 'Training & Support', text: 'Hands-on training, proven recipes, and day-to-day operations guidance from our team.' },
      { icon: '🚚', title: 'Supply Chain', text: 'A reliable ingredient and equipment supply chain so you can focus on serving customers.' },
      { icon: '📣', title: 'Marketing Power', text: 'Ready-made campaigns, branded materials, and nationwide promotions to launch you fast.' },
      { icon: '📍', title: 'Site Selection', text: 'We help you scout, evaluate, and secure the right location for your branch.' },
      { icon: '📈', title: 'Proven Model', text: 'A time-tested business system designed for healthy margins and repeat customers.' },
    ],
    steps: [
      { n: '01', title: 'Inquire', text: 'Send us your details and preferred location. We’ll share the franchise kit.' },
      { n: '02', title: 'Discovery call', text: 'Meet our franchising team to discuss investment, requirements, and timelines.' },
      { n: '03', title: 'Sign & set up', text: 'Finalize the agreement, secure your site, and begin store build-out and training.' },
      { n: '04', title: 'Grand opening', text: 'Launch your branch with full marketing and operations support behind you.' },
    ],
    packages: [
      { name: 'Kiosk', price: '₱1.2M – 1.8M', blurb: 'A compact counter for malls and transit hubs — fast to open, high foot traffic.', features: ['25–40 sqm space', 'Core bestseller menu', 'Equipment & signage', '2-week crew training'], featured: false },
      { name: 'Inline Store', price: '₱2.5M – 3.5M', blurb: 'The flagship bakeshop experience with full product range and seating.', features: ['60–100 sqm space', 'Full menu + custom cakes', 'Bake-on-site setup', 'Dedicated launch support'], featured: true },
      { name: 'Master Franchise', price: 'Let’s talk', blurb: 'Develop multiple branches across an entire region or province.', features: ['Territory rights', 'Multi-store rollout plan', 'Priority supply allocation', 'Executive business reviews'], featured: false },
    ],
  },
}

// Local cache of the last-loaded content, so the first paint on refresh already
// reflects saved settings (e.g. hidden buttons) instead of flashing the
// defaults while the network request is in flight.
const CACHE_KEY = 'bw_site_content'

// Synchronous best-effort read of the cached content for the initial render.
// Falls back to defaults when nothing is cached or storage is unavailable.
export function getCachedContent() {
  try {
    const raw = localStorage.getItem(CACHE_KEY)
    if (!raw) return DEFAULT_CONTENT
    return { ...DEFAULT_CONTENT, ...JSON.parse(raw) }
  } catch {
    return DEFAULT_CONTENT
  }
}

// Read the saved landing content (merged over defaults). Returns defaults on any
// error or when nothing has been saved yet. Successful reads update the cache.
export async function getSiteContent() {
  try {
    const { data, error } = await supabase
      .from('site_content')
      .select('data')
      .eq('id', 1)
      .maybeSingle()
    if (error || !data?.data) return DEFAULT_CONTENT
    const merged = { ...DEFAULT_CONTENT, ...data.data }
    try {
      localStorage.setItem(CACHE_KEY, JSON.stringify(data.data))
    } catch {
      // storage full / unavailable — caching is best-effort
    }
    return merged
  } catch {
    return DEFAULT_CONTENT
  }
}

// Admin: persist the landing content (single row, id = 1).
export async function saveSiteContent(content) {
  const { error } = await supabase
    .from('site_content')
    .upsert({ id: 1, data: content, updated_at: new Date().toISOString() })
  if (error) throw error
  try {
    localStorage.setItem(CACHE_KEY, JSON.stringify(content))
  } catch {
    // best-effort
  }
}

// Admin: upload an image to the public "site-images" bucket, return its URL.
export async function uploadImage(file) {
  const safe = file.name.replace(/[^a-zA-Z0-9.\-_]/g, '_')
  const path = `${Date.now()}-${safe}`
  const { error } = await supabase.storage.from('site-images').upload(path, file, {
    cacheControl: '3600',
    upsert: false,
  })
  if (error) throw error
  return supabase.storage.from('site-images').getPublicUrl(path).data.publicUrl
}

/* ------------------------------------------------------------------------- */
/* Products (menu)                                                           */
/* The existing `products` table is the source of truth shared by the Menu   */
/* page, this CMS, and the order-pricing trigger. Real schema (UUID id):     */
/*   name, description, price, original_price, image_path, features (jsonb),  */
/*   is_featured, status ('new'|'best_seller'|'sold_out'|null), category,     */
/*   archived_at (soft-delete).                                               */
/* ------------------------------------------------------------------------- */

// Map a DB row to the shape the UI uses. Numeric columns come back as strings.
function normalizeProduct(p) {
  return {
    id: p.id,
    name: p.name,
    category: p.category || 'Other',
    price: Number(p.price) || 0,
    originalPrice: p.original_price == null ? null : Number(p.original_price),
    img: p.image_path || '',
    desc: p.description || '',
    features: Array.isArray(p.features) ? p.features : [],
    status: p.status || null,
    isFeatured: !!p.is_featured,
  }
}

// Menu page: every non-archived product.
export async function fetchMenuProducts() {
  const { data, error } = await supabase
    .from('products')
    .select('*')
    .is('archived_at', null)
    .order('category', { ascending: true })
    .order('name', { ascending: true })
  if (error) throw error
  return (data || []).map(normalizeProduct)
}

// Editor: same set (non-archived), for management.
export async function fetchAllProducts() {
  return fetchMenuProducts()
}

// Map a UI product back to DB columns.
function toProductRow(p) {
  return {
    name: p.name,
    category: p.category || null,
    price: Number(p.price) || 0,
    original_price:
      p.originalPrice === '' || p.originalPrice == null ? null : Number(p.originalPrice),
    description: p.desc || null,
    image_path: p.img || null,
    features: Array.isArray(p.features) ? p.features : [],
    is_featured: !!p.isFeatured,
    status: p.status || null,
  }
}

// Editor save: update existing products, insert new ones (DB assigns the UUID),
// and soft-delete (archive) any that were removed — never hard-delete from the
// shared catalogue. `originalIds` is the id list loaded into the editor.
export async function syncProducts(products, originalIds = []) {
  const updates = products.filter((p) => p.id).map((p) => ({ id: p.id, ...toProductRow(p) }))
  const inserts = products.filter((p) => !p.id).map(toProductRow)

  if (updates.length) {
    const { error } = await supabase.from('products').upsert(updates, { onConflict: 'id' })
    if (error) throw error
  }
  if (inserts.length) {
    const { error } = await supabase.from('products').insert(inserts)
    if (error) throw error
  }

  const kept = new Set(products.filter((p) => p.id).map((p) => p.id))
  const removed = originalIds.filter((id) => !kept.has(id))
  if (removed.length) {
    const { error } = await supabase
      .from('products')
      .update({ archived_at: new Date().toISOString() })
      .in('id', removed)
    if (error) throw error
  }
}
