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
    { name: 'Classic Mocha Cake', price: '₱650', tag: 'Best Seller', img: 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?auto=format&fit=crop&w=600&q=80' },
    { name: 'Ube Chiffon Cake', price: '₱720', tag: 'New', img: 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=600&q=80' },
    { name: 'Soft Ensaymada', price: '₱45', tag: 'Popular', img: 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=600&q=80' },
    { name: 'Fresh Pandesal (12pcs)', price: '₱60', tag: 'Daily', img: 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?auto=format&fit=crop&w=600&q=80' },
    { name: 'Chocolate Cupcakes', price: '₱180', tag: 'Best Seller', img: 'https://images.unsplash.com/photo-1426869981800-95ebf51ce900?auto=format&fit=crop&w=600&q=80' },
    { name: 'Buttery Croissant', price: '₱85', tag: 'Popular', img: 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=600&q=80' },
    { name: 'Red Velvet Slice', price: '₱150', tag: 'New', img: 'https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?auto=format&fit=crop&w=600&q=80' },
    { name: 'Assorted Cookies', price: '₱220', tag: 'Popular', img: 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=600&q=80' },
  ],
  buttons: ALL_BUTTONS_VISIBLE,
}

// Read the saved landing content (merged over defaults). Returns defaults on any
// error or when nothing has been saved yet.
export async function getSiteContent() {
  try {
    const { data, error } = await supabase
      .from('site_content')
      .select('data')
      .eq('id', 1)
      .maybeSingle()
    if (error || !data?.data) return DEFAULT_CONTENT
    return { ...DEFAULT_CONTENT, ...data.data }
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
