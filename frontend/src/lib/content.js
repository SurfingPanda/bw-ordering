import api from './api'

// Toggleable call-to-action buttons on the landing page. The admin "Buttons"
// editor renders a switch per entry; Landing hides a button when its key is set
// to false. Keys default to visible (anything other than an explicit false).
export const LANDING_BUTTONS = [
  { key: 'navSignIn', label: 'Sign In', group: 'Navigation bar' },
  { key: 'navOrder', label: 'Order Now', group: 'Navigation bar' },
  { key: 'bestSellersMenu', label: 'See full menu', group: 'Best Sellers' },
  { key: 'promoOrder', label: 'Order a custom cake', group: 'Promo banner' },
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
  // "What's New" section: heading + a curated list of product cards (managed in
  // the Site Editor, same card shape as bestSellers). The card "+" deep-links to
  // /menu?add=<name>, so a card's name should match a real menu product.
  whatsNew: {
    eyebrow: 'Fresh off the oven',
    title: "What's New?",
    subtitle: "The latest additions to our bakeshop — try them while they're still warm.",
  },
  whatsNewProducts: [
    { name: 'Ube Chiffon Cake', price: '₱720', tag: 'New', img: 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=600&q=80' },
    { name: 'Red Velvet Slice', price: '₱150', tag: 'New', img: 'https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?auto=format&fit=crop&w=600&q=80' },
  ],
  buttons: ALL_BUTTONS_VISIBLE,
  // Editable Franchise page content (read by src/pages/Franchise.jsx).
  franchise: {
    hero: {
      eyebrow: 'Own a bakeshop',
      title: 'Partner with us',
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
  // Editable Careers page content (read by src/pages/Careers.jsx).
  careers: {
    hero: {
      title: 'Bake your career with us',
      subtitle:
        'Join a passionate team that brings freshly baked happiness to families every day. Discover a place where your talent rises.',
      image: '/images/bakery-team.jpg',
    },
    perks: [
      { icon: '\u{1F9E1}', title: 'Caring Culture', text: 'Work with a warm, supportive team that feels like family.' },
      { icon: '\u{1F4C8}', title: 'Grow With Us', text: 'Clear career paths, training, and room to rise as we expand.' },
      { icon: '\u{1F370}', title: 'Tasty Perks', text: 'Free treats, staff discounts, and meals on every shift.' },
      { icon: '\u{1F3E5}', title: 'Health Benefits', text: 'HMO coverage, paid leave, and government-mandated benefits.' },
      { icon: '\u23F0', title: 'Flexible Shifts', text: 'Schedules that respect your time and life outside work.' },
      { icon: '\u{1F389}', title: 'Fun Events', text: 'Team outings, celebrations, and performance bonuses.' },
    ],
    jobs: [
      {
        title: 'Head Baker', dept: 'Production', type: 'Full-time', location: 'Quezon City',
        description: 'Lead the baking team in producing high-quality breads, cakes, and pastries. Oversee daily production schedules, maintain quality standards, and mentor junior bakers.',
        requirements: ['5+ years of professional baking experience', 'Knowledge of bread, cake, and pastry techniques', 'Food safety certification (HACCP preferred)', 'Leadership and team management skills', 'Ability to work early morning shifts'],
      },
      {
        title: 'Pastry Chef', dept: 'Production', type: 'Full-time', location: 'Makati',
        description: 'Create and execute a wide range of pastries, desserts, and specialty cakes. Develop new recipes and seasonal offerings while maintaining consistent quality.',
        requirements: ['3+ years of pastry/baking experience', 'Culinary degree or equivalent training', 'Creativity in cake design and decoration', 'Strong attention to detail', 'Flexible schedule availability'],
      },
      {
        title: 'Store Crew', dept: 'Retail', type: 'Full-time', location: 'Multiple branches',
        description: 'Assist customers with product selection, handle transactions, and maintain store cleanliness. Ensure excellent customer experience at all times.',
        requirements: ['High school diploma or equivalent', 'Customer-oriented attitude', 'Basic math and cash handling skills', 'Willingness to learn about bakery products', 'Able to stand for extended periods'],
      },
      {
        title: 'Cashier', dept: 'Retail', type: 'Part-time', location: 'Pasig',
        description: 'Handle point-of-sale transactions efficiently and accurately. Process cash, card, and e-wallet payments while providing friendly service.',
        requirements: ['High school diploma or equivalent', 'Experience with POS systems preferred', 'Accuracy in handling cash', 'Friendly and approachable demeanor', 'Available for weekend shifts'],
      },
      {
        title: 'Delivery Rider', dept: 'Logistics', type: 'Full-time', location: 'Metro Manila',
        description: 'Deliver orders to customers safely and on time. Handle product with care to ensure quality upon arrival. Route optimization and customer communication.',
        requirements: ['Valid driver\u2019s license (motorcycle)', 'Own motorcycle with OR/CR', 'Familiarity with Metro Manila routes', 'Smartphone with data plan', 'Good communication skills'],
      },
      {
        title: 'Marketing Associate', dept: 'Corporate', type: 'Full-time', location: 'Quezon City (Hybrid)',
        description: 'Support the marketing team in planning and executing campaigns across digital and traditional channels. Manage social media content, coordinate with designers, and track campaign performance.',
        requirements: ['Bachelor\u2019s degree in Marketing, Communications, or related field', '1\u20132 years of marketing experience', 'Proficiency in social media platforms', 'Basic graphic design skills (Canva, Adobe)', 'Strong written and verbal communication'],
      },
    ],
    culture: {
      eyebrow: 'Our culture',
      title: 'Where passion meets pastry',
      text: 'At bw Superbakeshop, every team member plays a part in creating moments of joy. We believe in mentorship, fairness, and celebrating wins together \u2014 big or small.',
      image: 'https://images.unsplash.com/photo-1556217477-d325251ece38?auto=format&fit=crop&w=800&q=80',
      stat: '500+',
      statLabel: 'team members',
      highlights: [
        'Hands-on training from day one',
        'Promote-from-within philosophy',
        'Safe, inclusive workplace',
        'Recognition for great work',
      ],
    },
    departments: ['Production', 'Retail', 'Logistics', 'Corporate'],
    locations: ['Quezon City', 'Makati', 'Pasig', 'Metro Manila', 'Multiple branches', 'Quezon City (Hybrid)'],
    jobTypes: ['Full-time', 'Part-time'],
    email: 'careers@bwsuperbakeshop.com',
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
// The CMS blob lives in the Laravel API (MySQL) now — Supabase is auth-only.
export async function getSiteContent() {
  try {
    const { data } = await api.get('/site-content')
    if (!data || typeof data !== 'object' || Object.keys(data).length === 0) {
      return DEFAULT_CONTENT
    }
    const merged = { ...DEFAULT_CONTENT, ...data }
    try {
      localStorage.setItem(CACHE_KEY, JSON.stringify(data))
    } catch {
      // storage full / unavailable — caching is best-effort
    }
    return merged
  } catch {
    return DEFAULT_CONTENT
  }
}

// Admin/editor: persist the landing content (single row, id = 1).
export async function saveSiteContent(content) {
  await api.put('/site-content', content)
  try {
    localStorage.setItem(CACHE_KEY, JSON.stringify(content))
  } catch {
    // best-effort
  }
}

// Admin/editor: upload an image to the Laravel public disk, return its URL.
export async function uploadImage(file) {
  const form = new FormData()
  form.append('file', file)
  const { data } = await api.post('/uploads', form)
  return data.url
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
    calories: p.calories == null ? null : Number(p.calories),
    status: p.status || null,
    isFeatured: !!p.is_featured,
  }
}

// Menu page: every non-archived product. Products live in the Laravel API
// (MySQL) now — Supabase is only used for auth. The API returns rows in the
// same snake_case shape normalizeProduct expects.
export async function fetchMenuProducts() {
  const { data } = await api.get('/products')
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
    calories: p.calories === '' || p.calories == null ? null : Number(p.calories),
    is_featured: !!p.isFeatured,
    status: p.status || null,
  }
}

// Editor save: the Laravel API updates rows that carry an id, inserts new ones
// (DB assigns the UUID), and archives any removed product — never hard-deletes.
// `originalIds` is the id list loaded into the editor. Returns the fresh list.
export async function syncProducts(products, originalIds = []) {
  const payload = {
    products: products.map((p) => ({ id: p.id || null, ...toProductRow(p) })),
    originalIds,
  }
  const { data } = await api.post('/products/sync', payload)
  return (data || []).map(normalizeProduct)
}
