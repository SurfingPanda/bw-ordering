import { useEffect, useLayoutEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import ConfirmModal from '../components/ConfirmModal'
import Landing from './Landing'
import Franchise from './Franchise'
import Login from './Login'
import Menu from './Menu'
import Stores from './Stores'
import {
  DEFAULT_CONTENT,
  LANDING_BUTTONS,
  buttonState,
  fetchAllProducts,
  getSiteContent,
  saveSiteContent,
  syncProducts,
  uploadImage,
} from '../lib/content'
import { fetchVouchers, syncVouchers } from '../lib/vouchers'
import { fetchStores, syncStores } from '../lib/stores'
import { buildQrphPayload } from '../lib/qrph'
import QRCode from 'qrcode'

// Admin "Site Content" editor — full-width CMS layout with a section sidebar.
// Edits the landing page content, the Menu products, and the Franchise page.

// Sidebar sections, organised into collapsible groups.
const SECTION_GROUPS = [
  {
    key: 'landing',
    label: 'Landing Page',
    items: [
      { key: 'announcement', label: 'Announcement', Icon: MegaphoneIcon },
      { key: 'banners', label: 'Promo Banners', Icon: ImageIcon },
      { key: 'whatsNew', label: "What's New", Icon: SparkleIcon },
      { key: 'categories', label: 'Categories', Icon: GridIcon },
      { key: 'bestSellers', label: 'Best Sellers', Icon: StarIcon },
      { key: 'customCake', label: 'Custom Cake', Icon: CakeIcon },
      { key: 'newsletter', label: 'Sweet Deals', Icon: MailIcon },
      { key: 'stores', label: 'Find a Store', Icon: PinIcon },
      { key: 'franchise', label: 'Franchise', Icon: BriefcaseIcon },
    ],
  },
  {
    key: 'shop',
    label: 'Shop & Menu',
    items: [
      { key: 'products', label: 'Products', Icon: TagIcon },
      { key: 'menuCategories', label: 'Menu Categories', Icon: GridIcon },
      { key: 'vouchers', label: 'Vouchers', Icon: TicketIcon },
      { key: 'payment', label: 'Payment QR', Icon: QrIcon },
    ],
  },
  {
    key: 'pages',
    label: 'Other Pages',
    items: [
      { key: 'authPanel', label: 'Login Page', Icon: LoginIcon },
      { key: 'social', label: 'Social Links', Icon: ShareIcon },
      { key: 'buttons', label: 'Buttons', Icon: ToggleIcon },
    ],
  },
]

// Flat list (used to resolve the active section's label).
const SECTIONS = SECTION_GROUPS.flatMap((g) => g.items)

// Which group a section key belongs to (so we can keep its group open).
const GROUP_OF = Object.fromEntries(
  SECTION_GROUPS.flatMap((g) => g.items.map((it) => [it.key, g.key])),
)

// LANDING_BUTTONS grouped by their `group` field, preserving first-seen order,
// for the "Buttons" editor's sectioned switch list.
const BUTTON_GROUPS = LANDING_BUTTONS.reduce((acc, b) => {
  const entry = acc.find(([g]) => g === b.group)
  if (entry) entry[1].push(b)
  else acc.push([b.group, [b]])
  return acc
}, [])

export default function AdminContent() {
  const { logout, isAdmin, user, changePassword } = useAuth()
  const [content, setContent] = useState(null)
  const [products, setProducts] = useState([])
  const [originalIds, setOriginalIds] = useState([])
  const [vouchers, setVouchers] = useState([])
  const [voucherIds, setVoucherIds] = useState([])
  const [voucherFilter, setVoucherFilter] = useState('all')
  const [stores, setStores] = useState([])
  const [storeIds, setStoreIds] = useState([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [confirmReset, setConfirmReset] = useState(false)
  const [confirmLogout, setConfirmLogout] = useState(false)
  const [profileMenuOpen, setProfileMenuOpen] = useState(false)
  const [showChangePassword, setShowChangePassword] = useState(false)
  const profileRef = useRef(null)
  const [message, setMessage] = useState('')
  const [active, setActive] = useState('announcement')
  // Collapsible sidebar groups — the active section's group starts open.
  const [openGroups, setOpenGroups] = useState(() => new Set([GROUP_OF.announcement]))
  const toggleGroup = (key) =>
    setOpenGroups((prev) => {
      const next = new Set(prev)
      if (next.has(key)) next.delete(key)
      else next.add(key)
      return next
    })

  // Close the profile dropdown on outside click or Escape.
  useEffect(() => {
    if (!profileMenuOpen) return
    const onPointer = (e) => {
      if (profileRef.current && !profileRef.current.contains(e.target)) setProfileMenuOpen(false)
    }
    const onKey = (e) => e.key === 'Escape' && setProfileMenuOpen(false)
    document.addEventListener('mousedown', onPointer)
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('mousedown', onPointer)
      document.removeEventListener('keydown', onKey)
    }
  }, [profileMenuOpen])

  useEffect(() => {
    Promise.all([
      getSiteContent(),
      fetchAllProducts().catch(() => []),
      fetchVouchers().catch(() => []),
      fetchStores().catch(() => []),
    ]).then(([c, p, v, s]) => {
      setContent(c)
      setProducts(p)
      setOriginalIds(p.map((x) => x.id))
      setVouchers(v)
      setVoucherIds(v.map((x) => x.id))
      setStores(s)
      setStoreIds(s.map((x) => x.id))
      setLoading(false)
    })
  }, [])

  const save = async () => {
    setSaving(true)
    setMessage('')
    try {
      // Landing/franchise content lives in the site_content blob; products live
      // in the shared products table (also used for order pricing) — save both.
      await saveSiteContent(content)
      await syncProducts(products, originalIds)
      const freshVouchers = await syncVouchers(vouchers, voucherIds)
      setVouchers(
        freshVouchers.map((v) => ({
          id: v.id,
          code: v.code,
          type: v.type,
          value: Number(v.value) || 0,
          label: v.label || '',
          active: !!v.active,
          expiresAt: v.expires_at ? String(v.expires_at).slice(0, 10) : '',
        })),
      )
      setVoucherIds(freshVouchers.map((v) => v.id))
      const freshStores = await syncStores(stores, storeIds)
      setStores(
        freshStores.map((s) => ({
          id: s.id,
          name: s.name || '',
          region: s.region || '',
          address: s.address || '',
          hours: s.hours || '',
          phone: s.phone || '',
          latitude: s.latitude ?? '',
          longitude: s.longitude ?? '',
        })),
      )
      setStoreIds(freshStores.map((s) => s.id))
      setMessage('saved')
    } catch (err) {
      setMessage(`error:${err.message}`)
    } finally {
      setSaving(false)
    }
  }

  const resetDefaults = () => setConfirmReset(true)

  const doReset = () => {
    setContent(structuredClone(DEFAULT_CONTENT))
    setMessage('')
    setConfirmReset(false)
  }

  const setField = (key, value) => setContent((c) => ({ ...c, [key]: value }))
  const setButton = (key, state) =>
    setContent((c) => ({ ...c, buttons: { ...(c.buttons || {}), [key]: state } }))
  const updateItem = (key, idx, patch) =>
    setContent((c) => ({
      ...c,
      [key]: c[key].map((it, i) => (i === idx ? { ...it, ...patch } : it)),
    }))
  const addItem = (key, blank) => setContent((c) => ({ ...c, [key]: [...c[key], blank] }))
  const removeItem = (key, idx) =>
    setContent((c) => ({ ...c, [key]: c[key].filter((_, i) => i !== idx) }))
  const move = (key, idx, dir) =>
    setContent((c) => {
      const arr = [...c[key]]
      const j = idx + dir
      if (j < 0 || j >= arr.length) return c
      ;[arr[idx], arr[j]] = [arr[j], arr[idx]]
      return { ...c, [key]: arr }
    })

  // Products (the shared catalogue table; edited locally, synced on Save). New
  // products have no id yet (the DB assigns the UUID) — `_key` is a stable React
  // key until then. Removed products get archived (soft-deleted) on save.
  const updateProduct = (idx, patch) =>
    setProducts((ps) => ps.map((p, i) => (i === idx ? { ...p, ...patch } : p)))
  const addProduct = () =>
    setProducts((ps) => [
      ...ps,
      {
        _key: crypto.randomUUID(),
        name: 'New product',
        category: 'Bread',
        price: 0,
        originalPrice: null,
        img: '',
        desc: '',
        features: [],
        calories: null,
        isFeatured: false,
        status: null,
      },
    ])
  const removeProduct = (idx) => setProducts((ps) => ps.filter((_, i) => i !== idx))

  // Vouchers (own table; edited locally, synced on Save). New vouchers have no
  // id yet — `_key` is a stable React key until the DB assigns one.
  const updateVoucher = (idx, patch) =>
    setVouchers((vs) => vs.map((v, i) => (i === idx ? { ...v, ...patch } : v)))
  const addVoucher = () =>
    setVouchers((vs) => [
      ...vs,
      {
        _key: crypto.randomUUID(),
        code: '',
        type: 'percent',
        value: 10,
        label: '',
        active: true,
        expiresAt: '',
      },
    ])
  const removeVoucher = (idx) => setVouchers((vs) => vs.filter((_, i) => i !== idx))

  // Stores (locator branches; own table, edited locally, synced on Save). New
  // stores have no id yet — `_key` is a stable React key until the DB assigns one.
  const updateStore = (idx, patch) =>
    setStores((ss) => ss.map((s, i) => (i === idx ? { ...s, ...patch } : s)))
  const addStore = () =>
    setStores((ss) => [
      ...ss,
      {
        _key: crypto.randomUUID(),
        name: '',
        region: 'Metro Manila',
        address: '',
        hours: 'Mon–Sun, 7:00 AM – 9:00 PM',
        phone: '',
        latitude: '',
        longitude: '',
      },
    ])
  const removeStore = (idx) => setStores((ss) => ss.filter((_, i) => i !== idx))

  // Franchise content (nested under content.franchise).
  const fr = content?.franchise || DEFAULT_CONTENT.franchise
  const wn = content?.whatsNew || DEFAULT_CONTENT.whatsNew
  const setWhatsNew = (patch) =>
    setContent((c) => ({ ...c, whatsNew: { ...(c.whatsNew || DEFAULT_CONTENT.whatsNew), ...patch } }))

  const ap = content?.authPanel || DEFAULT_CONTENT.authPanel
  const setAuthPanel = (patch) =>
    setContent((c) => ({ ...c, authPanel: { ...(c.authPanel || DEFAULT_CONTENT.authPanel), ...patch } }))

  const cc = content?.customCake || DEFAULT_CONTENT.customCake
  const setCustomCake = (patch) =>
    setContent((c) => ({ ...c, customCake: { ...(c.customCake || DEFAULT_CONTENT.customCake), ...patch } }))

  const nl = content?.newsletter || DEFAULT_CONTENT.newsletter
  const setNewsletter = (patch) =>
    setContent((c) => ({ ...c, newsletter: { ...(c.newsletter || DEFAULT_CONTENT.newsletter), ...patch } }))

  const pm = content?.payment || DEFAULT_CONTENT.payment
  const setPayment = (patch) =>
    setContent((c) => ({ ...c, payment: { ...(c.payment || DEFAULT_CONTENT.payment), ...patch } }))

  const so = content?.social || DEFAULT_CONTENT.social
  const setSocial = (patch) =>
    setContent((c) => ({ ...c, social: { ...(c.social || DEFAULT_CONTENT.social), ...patch } }))

  // Editor-declared categories (may have no products yet) live in the CMS blob.
  const declaredCategories = content?.menuCategories || []
  const setDeclaredCategories = (updater) =>
    setContent((c) => ({
      ...c,
      menuCategories: updater(c?.menuCategories || []),
    }))

  // Optional per-category images (keyed by category name) for the menu sidebar.
  const menuCategoryImages = content?.menuCategoryImages || {}
  const setMenuCategoryImage = (name, url) =>
    setContent((c) => {
      const imgs = { ...(c?.menuCategoryImages || {}) }
      if (url) imgs[name] = url
      else delete imgs[name]
      return { ...c, menuCategoryImages: imgs }
    })
  // Keep a category's image with it when renaming/merging (don't clobber an
  // image already on the target) or drop it when the category is deleted.
  const renameMenuCategoryImage = (from, to) =>
    setContent((c) => {
      const imgs = { ...(c?.menuCategoryImages || {}) }
      if (imgs[from] != null && from !== to) {
        if (!imgs[to]) imgs[to] = imgs[from]
        delete imgs[from]
      }
      return { ...c, menuCategoryImages: imgs }
    })
  const deleteMenuCategoryImage = (name) =>
    setContent((c) => {
      const imgs = { ...(c?.menuCategoryImages || {}) }
      delete imgs[name]
      return { ...c, menuCategoryImages: imgs }
    })

  // Distinct categories — from products + declared — powers the category
  // combobox so editors reuse names (avoids Bread/Breads-style duplicates).
  const categoryOptions = [
    ...new Set([...products.map((p) => p.category).filter(Boolean), ...declaredCategories]),
  ].sort()

  const setFranchise = (patch) =>
    setContent((c) => ({ ...c, franchise: { ...(c.franchise || DEFAULT_CONTENT.franchise), ...patch } }))
  const setFranchiseHero = (patch) => setFranchise({ hero: { ...fr.hero, ...patch } })
  const updateFrList = (listKey, idx, patch) =>
    setFranchise({ [listKey]: (fr[listKey] || []).map((it, i) => (i === idx ? { ...it, ...patch } : it)) })
  const addFrItem = (listKey, blank) => setFranchise({ [listKey]: [...(fr[listKey] || []), blank] })
  const removeFrItem = (listKey, idx) =>
    setFranchise({ [listKey]: (fr[listKey] || []).filter((_, i) => i !== idx) })

  if (loading || !content) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-navy-50/40 text-slate-500">
        Loading content…
      </div>
    )
  }

  const counts = {
    banners: content.banners.length,
    categories: content.categories.length,
    bestSellers: content.bestSellers.length,
    products: products.length,
    vouchers: vouchers.length,
    stores: stores.length,
  }
  const activeLabel = SECTIONS.find((s) => s.key === active)?.label

  return (
    <div className="flex min-h-screen flex-col bg-navy-50/40 text-navy-800 lg:flex-row">
      {/* sidebar */}
      <aside className="sticky top-0 z-30 flex shrink-0 flex-col bg-navy-900 text-white lg:h-screen lg:w-64">
        <div className="flex h-16 items-center gap-2 border-b border-white/10 px-5">
          <img src="/images/logo (1).png" alt="bw Superbakeshop" className="h-9 w-auto" />
          <span className="font-brand text-lg font-bold text-white">Superbakeshop</span>
        </div>
        <div className="border-b border-white/10 px-5 py-4">
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-400">
            Site Editor
          </p>
          <p className="mt-1 text-xs text-navy-50/60">Manage your landing page</p>
        </div>

        <nav className="flex flex-col gap-1 overflow-y-auto p-3 lg:flex-1">
          {SECTION_GROUPS.map((group) => {
            const open = openGroups.has(group.key)
            const groupActive = group.items.some((it) => it.key === active)
            return (
              <div key={group.key}>
                <button
                  type="button"
                  onClick={() => toggleGroup(group.key)}
                  className={`flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wider transition ${
                    groupActive ? 'text-brand-400' : 'text-navy-50/50 hover:text-navy-50/90'
                  }`}
                >
                  <ChevronRightIcon
                    className={`h-4 w-4 shrink-0 transition-transform ${open ? 'rotate-90' : ''}`}
                  />
                  <span>{group.label}</span>
                </button>
                {open && (
                  <div className="mb-1 mt-0.5 space-y-0.5 pl-2">
                    {group.items.map((s) => {
                      const on = active === s.key
                      return (
                        <button
                          key={s.key}
                          onClick={() => setActive(s.key)}
                          className={`flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition ${
                            on
                              ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-lg shadow-brand-500/30'
                              : 'text-navy-50/70 hover:bg-white/5 hover:text-white'
                          }`}
                        >
                          <s.Icon className="h-5 w-5 shrink-0" />
                          <span>{s.label}</span>
                          {counts[s.key] !== undefined && (
                            <span
                              className={`ml-auto rounded-full px-2 py-0.5 text-xs font-bold ${
                                on ? 'bg-white/25 text-white' : 'bg-white/10 text-navy-50/70'
                              }`}
                            >
                              {counts[s.key]}
                            </span>
                          )}
                        </button>
                      )
                    })}
                  </div>
                )}
              </div>
            )
          })}
        </nav>

        <div className="relative border-t border-white/10 px-5 py-4" ref={profileRef}>
          <button
            type="button"
            onClick={() => setProfileMenuOpen((o) => !o)}
            className="flex w-full items-center gap-3 rounded-lg p-1 text-left transition hover:bg-white/5"
            aria-haspopup="menu"
            aria-expanded={profileMenuOpen}
          >
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-bold">
              {(user?.email || 'E').charAt(0).toUpperCase()}
            </span>
            <span className="min-w-0 flex-1">
              <span className="block truncate text-sm font-semibold">{user?.name || 'Editor'}</span>
              <span className="block truncate text-xs text-navy-50/60">{user?.email}</span>
            </span>
            <ChevronUpIcon
              className={`h-4 w-4 shrink-0 text-navy-50/50 transition ${profileMenuOpen ? '' : 'rotate-180'}`}
            />
          </button>

          {profileMenuOpen && (
            <div
              role="menu"
              className="absolute bottom-full left-5 right-5 mb-2 overflow-hidden rounded-xl border border-white/10 bg-navy-800 py-1 shadow-2xl shadow-black/40"
            >
              <button
                type="button"
                role="menuitem"
                onClick={() => {
                  setProfileMenuOpen(false)
                  setShowChangePassword(true)
                }}
                className="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm font-medium text-navy-50 transition hover:bg-white/10"
              >
                <KeyIcon className="h-4 w-4 shrink-0 text-navy-50/70" />
                Change my password
              </button>
            </div>
          )}
          <div className="mt-3 flex gap-2">
            {isAdmin && (
              <Link
                to="/admin"
                className="flex-1 rounded-lg bg-white/10 py-2 text-center text-xs font-semibold transition hover:bg-white/20"
              >
                Orders
              </Link>
            )}
            <Link
              to="/"
              className="flex-1 rounded-lg bg-white/10 py-2 text-center text-xs font-semibold transition hover:bg-white/20"
            >
              View site
            </Link>
            <button
              onClick={() => setConfirmLogout(true)}
              className="flex-1 rounded-lg bg-white/10 py-2 text-center text-xs font-semibold transition hover:bg-brand-600"
            >
              Logout
            </button>
          </div>
        </div>
      </aside>

      {/* main */}
      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-20 border-b border-slate-200 bg-white">
          <div className="flex h-16 items-center justify-between gap-4 px-4 sm:px-6">
            <div className="min-w-0">
              <h1 className="truncate text-lg font-bold text-navy-800">{activeLabel}</h1>
            </div>
            <div className="flex items-center gap-3">
              {message === 'saved' && (
                <span className="hidden text-sm font-medium text-green-600 sm:inline">
                  ✓ Saved &amp; published
                </span>
              )}
              {message.startsWith('error:') && (
                <span className="hidden max-w-xs truncate text-sm font-medium text-red-600 sm:inline">
                  ✕ {message.slice(6)}
                </span>
              )}
              <button
                onClick={resetDefaults}
                className="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600"
              >
                Reset
              </button>
              <button
                onClick={save}
                disabled={saving}
                className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:opacity-60"
              >
                {saving ? 'Saving…' : 'Save changes'}
              </button>
            </div>
          </div>
        </header>

        <div className="flex min-w-0 flex-1">
        <main className="min-w-0 flex-1 px-4 py-6 sm:px-6">
          {active === 'announcement' && (
            <Panel
              title="Announcement Bar"
              subtitle="The thin orange strip shown at the very top of every page."
            >
              <textarea
                value={content.announcement}
                onChange={(e) => setField('announcement', e.target.value)}
                rows={2}
                className="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
              />
              <p className="mt-3 mb-1 text-xs font-medium text-slate-500">Live preview</p>
              <div className="overflow-hidden rounded-lg bg-navy-900 px-4 py-2 text-center text-xs font-medium text-white">
                {content.announcement || '—'}
              </div>
            </Panel>
          )}

          {active === 'banners' && (
            <Panel
              title="Promotional Banners"
              subtitle="The big rotating carousel. Best image size: 1920 × 800 px (2.4:1)."
            >
              <div className="grid gap-4 xl:grid-cols-2">
                {content.banners.map((b, i) => (
                  <ItemCard
                    key={i}
                    index={i}
                    total={content.banners.length}
                    onMove={(d) => move('banners', i, d)}
                    onRemove={() => removeItem('banners', i)}
                  >
                    <ImageField
                      label="Banner image"
                      value={b.img}
                      onChange={(img) => updateItem('banners', i, { img })}
                      wide
                    />
                    <TextRow
                      label="Alt text"
                      value={b.alt}
                      onChange={(alt) => updateItem('banners', i, { alt })}
                    />
                  </ItemCard>
                ))}
              </div>
              <AddButton onClick={() => addItem('banners', { img: '', alt: '' })}>
                + Add banner
              </AddButton>
            </Panel>
          )}

          {active === 'whatsNew' && (
            <Panel
              title="What's New"
              subtitle="Heading + the product cards shown in the “What’s New?” section. A card’s “+” adds it to the cart by name, so match a real menu product name. Best image size: 800 × 600 px (landscape, ~4:3) — square works too; images are center-cropped to fill the card."
            >
              <TextRow
                label="Eyebrow"
                value={wn.eyebrow}
                onChange={(eyebrow) => setWhatsNew({ eyebrow })}
              />
              <TextRow
                label="Title"
                value={wn.title}
                onChange={(title) => setWhatsNew({ title })}
              />
              <TextAreaRow
                label="Subtitle"
                value={wn.subtitle}
                onChange={(subtitle) => setWhatsNew({ subtitle })}
              />

              <p className="mb-3 mt-6 text-sm font-semibold text-navy-800">Products</p>
              <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {(content.whatsNewProducts || []).map((p, i) => (
                  <ItemCard
                    key={i}
                    index={i}
                    total={(content.whatsNewProducts || []).length}
                    onMove={(d) => move('whatsNewProducts', i, d)}
                    onRemove={() => removeItem('whatsNewProducts', i)}
                  >
                    <ImageField
                      label="Image"
                      value={p.img}
                      onChange={(img) => updateItem('whatsNewProducts', i, { img })}
                    />
                    <TextRow
                      label="Name"
                      value={p.name}
                      onChange={(name) => updateItem('whatsNewProducts', i, { name })}
                    />
                    <TextAreaRow
                      label="Description"
                      value={p.desc}
                      onChange={(desc) => updateItem('whatsNewProducts', i, { desc })}
                    />
                    <div className="grid grid-cols-2 gap-2">
                      <TextRow
                        label="Price"
                        value={p.price}
                        onChange={(price) => updateItem('whatsNewProducts', i, { price })}
                      />
                      <TextRow
                        label="Tag"
                        value={p.tag}
                        onChange={(tag) => updateItem('whatsNewProducts', i, { tag })}
                      />
                    </div>
                    <NumberRow
                      label="Calories (optional)"
                      value={p.calories ?? ''}
                      onChange={(calories) =>
                        updateItem('whatsNewProducts', i, {
                          calories: calories === '' ? null : calories,
                        })
                      }
                    />
                    <TextAreaRow
                      label="Allergens (one per line)"
                      value={(p.allergens || []).join('\n')}
                      onChange={(v) =>
                        updateItem('whatsNewProducts', i, {
                          allergens: v.split('\n').map((s) => s.trim()).filter(Boolean),
                        })
                      }
                    />
                  </ItemCard>
                ))}
              </div>
              <AddButton
                onClick={() =>
                  addItem('whatsNewProducts', {
                    name: 'New item',
                    desc: '',
                    price: '₱0',
                    tag: 'New',
                    img: '',
                    calories: null,
                    allergens: [],
                  })
                }
              >
                + Add product
              </AddButton>
            </Panel>
          )}

          {active === 'categories' && (
            <Panel title="Categories" subtitle="The round “Shop by category” badges. Best image size: 400 × 400 px (square) — shown as a circle, center-cropped.">
              <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {content.categories.map((c, i) => (
                  <ItemCard
                    key={i}
                    index={i}
                    total={content.categories.length}
                    onMove={(d) => move('categories', i, d)}
                    onRemove={() => removeItem('categories', i)}
                  >
                    <ImageField
                      label="Image"
                      value={c.img}
                      onChange={(img) => updateItem('categories', i, { img })}
                    />
                    <TextRow
                      label="Name"
                      value={c.name}
                      onChange={(name) => updateItem('categories', i, { name })}
                    />
                  </ItemCard>
                ))}
              </div>
              <AddButton onClick={() => addItem('categories', { name: 'New category', img: '' })}>
                + Add category
              </AddButton>
            </Panel>
          )}

          {active === 'bestSellers' && (
            <Panel title="Best Selling Foods" subtitle="The “Our Best Sellers” product cards. Best image size: 800 × 600 px (landscape, ~4:3) — square works too; images are center-cropped to fill the card.">
              <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {content.bestSellers.map((p, i) => (
                  <ItemCard
                    key={i}
                    index={i}
                    total={content.bestSellers.length}
                    onMove={(d) => move('bestSellers', i, d)}
                    onRemove={() => removeItem('bestSellers', i)}
                  >
                    <ImageField
                      label="Image"
                      value={p.img}
                      onChange={(img) => updateItem('bestSellers', i, { img })}
                    />
                    <TextRow
                      label="Name"
                      value={p.name}
                      onChange={(name) => updateItem('bestSellers', i, { name })}
                    />
                    <TextAreaRow
                      label="Description"
                      value={p.desc}
                      onChange={(desc) => updateItem('bestSellers', i, { desc })}
                    />
                    <div className="grid grid-cols-2 gap-2">
                      <TextRow
                        label="Price"
                        value={p.price}
                        onChange={(price) => updateItem('bestSellers', i, { price })}
                      />
                      <TextRow
                        label="Tag"
                        value={p.tag}
                        onChange={(tag) => updateItem('bestSellers', i, { tag })}
                      />
                    </div>
                    <NumberRow
                      label="Calories (optional)"
                      value={p.calories ?? ''}
                      onChange={(calories) =>
                        updateItem('bestSellers', i, {
                          calories: calories === '' ? null : calories,
                        })
                      }
                    />
                    <TextAreaRow
                      label="Allergens (one per line)"
                      value={(p.allergens || []).join('\n')}
                      onChange={(v) =>
                        updateItem('bestSellers', i, {
                          allergens: v.split('\n').map((s) => s.trim()).filter(Boolean),
                        })
                      }
                    />
                  </ItemCard>
                ))}
              </div>
              <AddButton
                onClick={() =>
                  addItem('bestSellers', {
                    name: 'New item',
                    desc: '',
                    price: '₱0',
                    tag: 'New',
                    img: '',
                    calories: null,
                    allergens: [],
                  })
                }
              >
                + Add product
              </AddButton>
            </Panel>
          )}

          {active === 'products' && (
            <Panel
              title="Menu Products"
              subtitle="The shared product catalogue shown on /menu and used for order pricing. Removing a product archives it (soft-delete)."
            >
              <datalist id="bw-categories">
                {categoryOptions.map((c) => (
                  <option key={c} value={c} />
                ))}
              </datalist>
              <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {products.map((p, i) => (
                  <ItemCard
                    key={p.id || p._key}
                    index={i}
                    total={products.length}
                    hideMove
                    onRemove={() => removeProduct(i)}
                  >
                    <ImageField
                      label="Image"
                      value={p.img}
                      onChange={(img) => updateProduct(i, { img })}
                    />
                    <TextRow
                      label="Name"
                      value={p.name}
                      onChange={(name) => updateProduct(i, { name })}
                    />
                    <div className="grid grid-cols-2 gap-2">
                      <ComboRow
                        label="Category"
                        value={p.category}
                        onChange={(category) => updateProduct(i, { category })}
                        listId="bw-categories"
                        placeholder="Pick or type new…"
                      />
                      <SelectRow
                        label="Status"
                        value={p.status || ''}
                        onChange={(status) => updateProduct(i, { status: status || null })}
                        options={[
                          ['', 'None'],
                          ['new', 'New'],
                          ['best_seller', 'Best seller'],
                          ['bundle', 'Bundle'],
                          ['sold_out', 'Sold out'],
                        ]}
                      />
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                      <NumberRow
                        label="Price (₱)"
                        value={p.price}
                        onChange={(price) => updateProduct(i, { price })}
                      />
                      <NumberRow
                        label="Was (₱, optional)"
                        value={p.originalPrice ?? ''}
                        onChange={(originalPrice) => updateProduct(i, { originalPrice })}
                      />
                    </div>
                    <NumberRow
                      label="Calories (optional)"
                      value={p.calories ?? ''}
                      onChange={(calories) => updateProduct(i, { calories })}
                    />
                    <TextAreaRow
                      label="Description"
                      value={p.desc}
                      onChange={(desc) => updateProduct(i, { desc })}
                    />
                    <TextAreaRow
                      label="Allergens (one per line)"
                      value={(p.features || []).join('\n')}
                      onChange={(v) =>
                        updateProduct(i, {
                          features: v.split('\n').map((s) => s.trim()).filter(Boolean),
                        })
                      }
                    />
                    <label className="flex items-center justify-between pt-1">
                      <span className="text-xs font-medium text-slate-500">Featured</span>
                      <Toggle
                        on={!!p.isFeatured}
                        onChange={(isFeatured) => updateProduct(i, { isFeatured })}
                      />
                    </label>
                  </ItemCard>
                ))}
              </div>
              <AddButton onClick={addProduct}>+ Add product</AddButton>
            </Panel>
          )}

          {active === 'menuCategories' && (
            <Panel
              title="Menu Categories"
              subtitle="Categories are derived from products. Rename to merge two categories into one, or delete a category and move its products elsewhere. Click “Save changes” to apply."
            >
              <CategoryManager
                products={products}
                setProducts={setProducts}
                categoryOptions={categoryOptions}
                declared={declaredCategories}
                setDeclared={setDeclaredCategories}
                images={menuCategoryImages}
                onSetImage={setMenuCategoryImage}
                onRenameImage={renameMenuCategoryImage}
                onDeleteImage={deleteMenuCategoryImage}
              />
            </Panel>
          )}

          {active === 'vouchers' && (
            <Panel
              title="Vouchers"
              subtitle="Discount codes customers can apply at checkout. These are the real codes — order totals are validated against them. Click “Save changes” to apply."
            >
              <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {vouchers.map((v, i) => (
                  <ItemCard
                    key={v.id || v._key}
                    index={i}
                    total={vouchers.length}
                    hideMove
                    onRemove={() => removeVoucher(i)}
                  >
                    <TextRow
                      label="Code"
                      value={v.code}
                      onChange={(code) => updateVoucher(i, { code: code.toUpperCase() })}
                    />
                    <SelectRow
                      label="Type"
                      value={v.type}
                      onChange={(type) => updateVoucher(i, { type })}
                      options={[
                        ['percent', '% off'],
                        ['amount', '₱ off'],
                        ['freedel', 'Free delivery'],
                      ]}
                    />
                    {v.type !== 'freedel' && (
                      <NumberRow
                        label={v.type === 'percent' ? 'Percent off (%)' : 'Amount off (₱)'}
                        value={v.value}
                        onChange={(value) => updateVoucher(i, { value })}
                      />
                    )}
                    <TextRow
                      label="Label (shown to customer)"
                      value={v.label}
                      onChange={(label) => updateVoucher(i, { label })}
                    />
                    <label className="flex items-center justify-between pt-1">
                      <span className="text-xs font-medium text-slate-500">Active</span>
                      <Toggle on={!!v.active} onChange={(active) => updateVoucher(i, { active })} />
                    </label>
                  </ItemCard>
                ))}
              </div>
              <AddButton onClick={addVoucher}>+ Add voucher</AddButton>
            </Panel>
          )}

          {active === 'payment' && (
            <Panel
              title="Payment QR (QR Ph)"
              subtitle="Paste your merchant’s QR Ph data string. Checkout regenerates the QR for each order with the exact amount baked in — so it’s never a fixed/static code. Leave it empty to use a generated demo QR."
            >
              <TextAreaRow
                label="QR Ph payload"
                value={pm.qrPayload}
                onChange={(qrPayload) => setPayment({ qrPayload })}
              />
              <p className="-mt-1 text-xs text-slate-400">
                This is the text encoded in your printed/static QR Ph code — scan it with any QR
                reader (e.g. your phone’s camera) and copy the result here. It starts with
                something like <code>00020101…</code>.
              </p>
              {pm.qrPayload && (
                <button
                  type="button"
                  onClick={() => setPayment({ qrPayload: '' })}
                  className="mt-2 text-xs font-semibold text-red-600 transition hover:text-red-700"
                >
                  Clear (use demo QR)
                </button>
              )}
              <div className="mt-5">
                <p className="mb-2 text-xs font-medium text-slate-500">
                  Live preview — checkout QR Ph box
                </p>
                <QrphPreview payload={pm.qrPayload} />
              </div>
            </Panel>
          )}

          {active === 'customCake' && (
            <Panel
              title="Custom Cake Banner"
              subtitle="The big orange promo banner on the landing page. Clicking the banner opens the menu; the “Order a custom cake” button is shown/hidden in the Buttons section (promoOrder). Cake image: a transparent PNG works best, around 1200 × 900 px."
            >
              <TextRow
                label="Eyebrow (script text)"
                value={cc.eyebrow}
                onChange={(eyebrow) => setCustomCake({ eyebrow })}
              />
              <TextRow
                label="Title"
                value={cc.title}
                onChange={(title) => setCustomCake({ title })}
              />
              <TextAreaRow
                label="Subtitle"
                value={cc.subtitle}
                onChange={(subtitle) => setCustomCake({ subtitle })}
              />
              <TextRow
                label="Button label"
                value={cc.buttonLabel}
                onChange={(buttonLabel) => setCustomCake({ buttonLabel })}
              />
              <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <TextRow
                  label="Banner link (where clicking the banner goes)"
                  value={cc.bannerLink}
                  onChange={(bannerLink) => setCustomCake({ bannerLink })}
                />
                <TextRow
                  label="Button link (where the button goes)"
                  value={cc.buttonLink}
                  onChange={(buttonLink) => setCustomCake({ buttonLink })}
                />
              </div>
              <p className="-mt-1 text-xs text-slate-400">
                Use an internal path like <code>/menu</code> or a full URL like{' '}
                <code>https://…</code>.
              </p>
              <ImageField
                label="Cake image"
                value={cc.image}
                onChange={(image) => setCustomCake({ image })}
                wide
              />
              <TextRow
                label="Image alt text"
                value={cc.alt}
                onChange={(alt) => setCustomCake({ alt })}
              />
            </Panel>
          )}

          {active === 'newsletter' && (
            <Panel
              title="Sweet Deals (Newsletter)"
              subtitle="The “Get sweet deals in your inbox” newsletter section near the bottom of the landing page. The Subscribe button is shown/hidden in the Buttons section (newsletterSubscribe)."
            >
              <TextRow
                label="Title"
                value={nl.title}
                onChange={(title) => setNewsletter({ title })}
              />
              <TextAreaRow
                label="Subtitle"
                value={nl.subtitle}
                onChange={(subtitle) => setNewsletter({ subtitle })}
              />
              <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <TextRow
                  label="Input placeholder"
                  value={nl.placeholder}
                  onChange={(placeholder) => setNewsletter({ placeholder })}
                />
                <TextRow
                  label="Button label"
                  value={nl.buttonLabel}
                  onChange={(buttonLabel) => setNewsletter({ buttonLabel })}
                />
              </div>
            </Panel>
          )}

          {active === 'stores' && (
            <Panel
              title="Find a Store"
              subtitle="Branches shown on the “Find a Store” page and map. Latitude/longitude place the map pin — copy them from Google Maps (right-click a spot → the coordinates at the top). Click “Save changes” to publish."
            >
              <div className="grid gap-4 xl:grid-cols-2">
                {stores.map((s, i) => (
                  <ItemCard
                    key={s.id || s._key}
                    index={i}
                    total={stores.length}
                    hideMove
                    onRemove={() => removeStore(i)}
                  >
                    <TextRow
                      label="Branch name"
                      value={s.name}
                      onChange={(name) => updateStore(i, { name })}
                    />
                    <SelectRow
                      label="Region"
                      value={s.region}
                      onChange={(region) => updateStore(i, { region })}
                      options={[
                        ['Metro Manila', 'Metro Manila'],
                        ['Luzon', 'Luzon'],
                        ['Visayas', 'Visayas'],
                        ['Mindanao', 'Mindanao'],
                      ]}
                    />
                    <TextAreaRow
                      label="Address"
                      value={s.address}
                      onChange={(address) => updateStore(i, { address })}
                    />
                    <TextRow
                      label="Hours"
                      value={s.hours}
                      onChange={(hours) => updateStore(i, { hours })}
                    />
                    <TextRow
                      label="Phone"
                      value={s.phone}
                      onChange={(phone) => updateStore(i, { phone })}
                    />
                    <div className="grid grid-cols-2 gap-2">
                      <TextRow
                        label="Latitude"
                        value={s.latitude}
                        onChange={(latitude) => updateStore(i, { latitude })}
                      />
                      <TextRow
                        label="Longitude"
                        value={s.longitude}
                        onChange={(longitude) => updateStore(i, { longitude })}
                      />
                    </div>
                  </ItemCard>
                ))}
              </div>
              <AddButton onClick={addStore}>+ Add store</AddButton>
            </Panel>
          )}

          {active === 'franchise' && (
            <div className="space-y-6">
              <Panel title="Franchise — Hero" subtitle="The top of the /franchise page.">
                <TextRow
                  label="Eyebrow"
                  value={fr.hero?.eyebrow}
                  onChange={(eyebrow) => setFranchiseHero({ eyebrow })}
                />
                <TextRow
                  label="Title"
                  value={fr.hero?.title}
                  onChange={(title) => setFranchiseHero({ title })}
                />
                <TextAreaRow
                  label="Subtitle"
                  value={fr.hero?.subtitle}
                  onChange={(subtitle) => setFranchiseHero({ subtitle })}
                />
                <TextRow
                  label="Inquiry email (the Inquire buttons open this)"
                  value={fr.email}
                  onChange={(email) => setFranchise({ email })}
                />
              </Panel>

              <Panel title="Franchise — Perks" subtitle="The “Why franchise with us” cards.">
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                  {(fr.perks || []).map((p, i) => (
                    <ItemCard
                      key={i}
                      index={i}
                      total={fr.perks.length}
                      hideMove
                      onRemove={() => removeFrItem('perks', i)}
                    >
                      <TextRow
                        label="Icon (emoji)"
                        value={p.icon}
                        onChange={(icon) => updateFrList('perks', i, { icon })}
                      />
                      <TextRow
                        label="Title"
                        value={p.title}
                        onChange={(title) => updateFrList('perks', i, { title })}
                      />
                      <TextAreaRow
                        label="Text"
                        value={p.text}
                        onChange={(text) => updateFrList('perks', i, { text })}
                      />
                    </ItemCard>
                  ))}
                </div>
                <AddButton onClick={() => addFrItem('perks', { icon: '✨', title: 'New perk', text: '' })}>
                  + Add perk
                </AddButton>
              </Panel>

              <Panel title="Franchise — Steps" subtitle="The “How it works” path.">
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                  {(fr.steps || []).map((s, i) => (
                    <ItemCard
                      key={i}
                      index={i}
                      total={fr.steps.length}
                      hideMove
                      onRemove={() => removeFrItem('steps', i)}
                    >
                      <TextRow
                        label="Number"
                        value={s.n}
                        onChange={(n) => updateFrList('steps', i, { n })}
                      />
                      <TextRow
                        label="Title"
                        value={s.title}
                        onChange={(title) => updateFrList('steps', i, { title })}
                      />
                      <TextAreaRow
                        label="Text"
                        value={s.text}
                        onChange={(text) => updateFrList('steps', i, { text })}
                      />
                    </ItemCard>
                  ))}
                </div>
                <AddButton onClick={() => addFrItem('steps', { n: '00', title: 'New step', text: '' })}>
                  + Add step
                </AddButton>
              </Panel>

              <Panel title="Franchise — Packages" subtitle="The franchise package cards.">
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                  {(fr.packages || []).map((pkg, i) => (
                    <ItemCard
                      key={i}
                      index={i}
                      total={fr.packages.length}
                      hideMove
                      onRemove={() => removeFrItem('packages', i)}
                    >
                      <TextRow
                        label="Name"
                        value={pkg.name}
                        onChange={(name) => updateFrList('packages', i, { name })}
                      />
                      <TextRow
                        label="Price"
                        value={pkg.price}
                        onChange={(price) => updateFrList('packages', i, { price })}
                      />
                      <TextAreaRow
                        label="Blurb"
                        value={pkg.blurb}
                        onChange={(blurb) => updateFrList('packages', i, { blurb })}
                      />
                      <TextAreaRow
                        label="Features (one per line)"
                        value={(pkg.features || []).join('\n')}
                        onChange={(v) =>
                          updateFrList('packages', i, {
                            features: v.split('\n').map((s) => s.trim()).filter(Boolean),
                          })
                        }
                      />
                      <label className="flex items-center justify-between pt-1">
                        <span className="text-xs font-medium text-slate-500">
                          Highlight as “Most popular”
                        </span>
                        <Toggle
                          on={!!pkg.featured}
                          onChange={(featured) => updateFrList('packages', i, { featured })}
                        />
                      </label>
                    </ItemCard>
                  ))}
                </div>
                <AddButton
                  onClick={() =>
                    addFrItem('packages', { name: 'New package', price: '₱0', blurb: '', features: [], featured: false })
                  }
                >
                  + Add package
                </AddButton>
              </Panel>
            </div>
          )}

          {active === 'authPanel' && (
            <Panel
              title="Login Page"
              subtitle="The branded left panel shown on the Login and Register pages. Use transparent PNGs — logo ~400 px tall, image ~600 px wide."
            >
              <ImageField
                label="Logo"
                value={ap.logo}
                onChange={(logo) => setAuthPanel({ logo })}
              />
              <TextRow
                label="Tagline"
                value={ap.tagline}
                onChange={(tagline) => setAuthPanel({ tagline })}
              />
              <TextRow
                label="Script line"
                value={ap.script}
                onChange={(script) => setAuthPanel({ script })}
              />
              <ImageField
                label="Image"
                value={ap.image}
                onChange={(image) => setAuthPanel({ image })}
                wide
              />
            </Panel>
          )}

          {active === 'social' && (
            <Panel
              title="Social Links"
              subtitle="The Facebook, LinkedIn, and X (Twitter) icons in the footer. Paste each profile’s full URL (https://…). All three icons always show; a field left empty just won’t link anywhere."
            >
              <TextRow
                label="Facebook URL"
                value={so.facebook}
                onChange={(facebook) => setSocial({ facebook })}
              />
              <TextRow
                label="LinkedIn URL"
                value={so.linkedin}
                onChange={(linkedin) => setSocial({ linkedin })}
              />
              <TextRow
                label="X (Twitter) URL"
                value={so.x}
                onChange={(x) => setSocial({ x })}
              />
            </Panel>
          )}

          {active === 'buttons' && (
            <Panel
              title="Buttons & Calls-to-Action"
              subtitle="Control each action button across your site. Visible = shown and working, Disabled = shown but clicking does nothing, Hidden = removed from the live site."
            >
              <div className="space-y-6">
                {BUTTON_GROUPS.map(([group, items]) => (
                  <div key={group}>
                    <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                      {group}
                    </p>
                    <div className="overflow-hidden rounded-xl border border-slate-200">
                      {items.map((b, i) => (
                        <div
                          key={b.key}
                          className={`flex flex-wrap items-center justify-between gap-3 px-4 py-3 ${
                            i > 0 ? 'border-t border-slate-100' : ''
                          }`}
                        >
                          <span className="text-sm font-medium text-navy-800">{b.label}</span>
                          <ButtonStateControl
                            value={buttonState(content.buttons, b.key)}
                            onChange={(state) => setButton(b.key, state)}
                          />
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </Panel>
          )}
        </main>

        {/* live preview */}
        <aside className="hidden shrink-0 border-l border-slate-200 bg-slate-100/70 xl:block xl:w-[42%]">
          <div
            data-preview-scroll
            className="sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto p-5"
          >
            <p className="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
              <span className="h-2 w-2 rounded-full bg-green-500" />
              Live preview — full page, unsaved changes
            </p>
            <FullPreview content={content} active={active} products={products} stores={stores} />
          </div>
        </aside>
        </div>
      </div>

      {confirmReset && (
        <ConfirmModal
          title="Reset landing content?"
          message="This restores all landing page content back to the defaults. Your unsaved edits will be lost — nothing is saved until you click “Save changes”."
          confirmLabel="Reset to defaults"
          onConfirm={doReset}
          onCancel={() => setConfirmReset(false)}
        />
      )}

      {confirmLogout && (
        <ConfirmModal
          title="Log out?"
          message="You’ll be signed out of the Site Editor. Any unsaved changes will be lost."
          confirmLabel="Log out"
          loadingLabel="Logging out"
          onConfirm={logout}
          onCancel={() => setConfirmLogout(false)}
        />
      )}

      {showChangePassword && (
        <ChangePasswordModal
          onSubmit={changePassword}
          onClose={() => setShowChangePassword(false)}
        />
      )}
    </div>
  )
}


// Change-password modal — new password + confirmation, submitted via the
// AuthContext `changePassword` (Supabase updateUser).
function ChangePasswordModal({ onSubmit, onClose }) {
  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [show, setShow] = useState(false)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [done, setDone] = useState(false)

  useEffect(() => {
    const onKey = (e) => e.key === 'Escape' && onClose()
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [onClose])

  const submit = async (e) => {
    e.preventDefault()
    setError('')
    setBusy(true)
    try {
      await onSubmit(password, confirm)
      setDone(true)
      setTimeout(onClose, 1200)
    } catch (err) {
      setError(err.message || 'Could not update password.')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div
      className="fixed inset-0 z-[80] flex items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm"
      onClick={onClose}
      role="dialog"
      aria-modal="true"
      aria-label="Change password"
    >
      <form
        onSubmit={submit}
        className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        <h3 className="text-lg font-bold text-navy-800">Change my password</h3>
        <p className="mt-2 text-sm leading-relaxed text-slate-500">
          Enter a new password for your account. It must be at least 6 characters.
        </p>

        {done ? (
          <p className="mt-6 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            ✓ Password updated.
          </p>
        ) : (
          <>
            <div className="mt-5 space-y-3">
              <div>
                <label className="mb-1 block text-xs font-semibold text-navy-700">New password</label>
                <input
                  type={show ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  autoFocus
                  autoComplete="new-password"
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </div>
              <div>
                <label className="mb-1 block text-xs font-semibold text-navy-700">Confirm new password</label>
                <input
                  type={show ? 'text' : 'password'}
                  value={confirm}
                  onChange={(e) => setConfirm(e.target.value)}
                  autoComplete="new-password"
                  className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                />
              </div>
              <label className="flex items-center gap-2 text-xs font-medium text-slate-500">
                <input
                  type="checkbox"
                  checked={show}
                  onChange={(e) => setShow(e.target.checked)}
                  className="h-3.5 w-3.5 rounded border-slate-300"
                />
                Show passwords
              </label>
            </div>

            {error && (
              <p className="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm font-medium text-red-600">{error}</p>
            )}

            <div className="mt-6 flex justify-end gap-3">
              <button
                type="button"
                onClick={onClose}
                className="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-navy-700 transition hover:bg-slate-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={busy}
                className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:opacity-60"
              >
                {busy ? 'Updating…' : 'Update password'}
              </button>
            </div>
          </>
        )}
      </form>
    </div>
  )
}

// Full-page live preview: renders the real Landing page (controlled by the
// in-editor content) at a fixed design width, then CSS-scales it down to fit
// the preview pane. Because it's the actual page component, the preview always
// matches the live site and reflects every field as you type.
const PREVIEW_BASE_WIDTH = 1280

// Editor section -> landing page anchor to scroll into view when you switch.
const SECTION_ANCHOR = {
  announcement: null,
  banners: '#home',
  categories: '#categories',
  bestSellers: '#best-sellers',
  customCake: '#custom-cake',
  newsletter: '#newsletter',
  products: null,
  payment: null,
  stores: null,
  franchise: null,
  buttons: null,
}

function FullPreview({ content, active, products, stores }) {
  const wrapRef = useRef(null)
  const innerRef = useRef(null)
  const [scale, setScale] = useState(0.4)
  const [height, setHeight] = useState(0)

  // Keep the scale (pane width / design width) and the collapsed wrapper height
  // (the transform doesn't affect layout, so we reserve the scaled height) in
  // sync with the pane size and the rendered content.
  useLayoutEffect(() => {
    const wrap = wrapRef.current
    const inner = innerRef.current
    if (!wrap || !inner) return
    const measure = () => {
      const s = wrap.clientWidth / PREVIEW_BASE_WIDTH
      setScale(s)
      setHeight(inner.offsetHeight * s)
    }
    measure()
    const ro = new ResizeObserver(measure)
    ro.observe(wrap)
    ro.observe(inner)
    return () => ro.disconnect()
  }, [content])

  // When you switch editor sections, scroll the preview to the matching part.
  useEffect(() => {
    const wrap = wrapRef.current
    const inner = innerRef.current
    const scroller = wrap?.closest('[data-preview-scroll]')
    if (!wrap || !inner || !scroller) return

    const anchor = SECTION_ANCHOR[active]
    if (!anchor) {
      scroller.scrollTo({ top: 0, behavior: 'smooth' })
      return
    }
    const target = inner.querySelector(anchor)
    if (!target) return
    // getBoundingClientRect is post-transform, so this works at any scale.
    const delta = target.getBoundingClientRect().top - wrap.getBoundingClientRect().top
    scroller.scrollTo({ top: Math.max(0, scroller.scrollTop + delta - 8), behavior: 'smooth' })
  }, [active, scale, height])

  return (
    <div
      ref={wrapRef}
      style={{ height }}
      className="w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
    >
      <div
        ref={innerRef}
        style={{
          width: PREVIEW_BASE_WIDTH,
          transform: `scale(${scale})`,
          transformOrigin: 'top left',
          pointerEvents: 'none',
        }}
      >
        {active === 'franchise' ? (
          <Franchise content={content} preview />
        ) : active === 'authPanel' ? (
          <Login content={content} preview />
        ) : active === 'stores' ? (
          <Stores previewStores={stores} preview />
        ) : active === 'products' || active === 'menuCategories' ? (
          <Menu previewProducts={products} previewContent={content} preview />
        ) : (
          <Landing content={content} preview />
        )}
      </div>
    </div>
  )
}

function Panel({ title, subtitle, children }) {
  return (
    <div className="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
      <h2 className="text-lg font-bold text-navy-800">{title}</h2>
      {subtitle && <p className="mb-5 mt-0.5 text-sm text-slate-500">{subtitle}</p>}
      {children}
    </div>
  )
}

// Renders the checkout QR Ph box as it will appear, generating a real QR from the
// merchant payload (with a sample amount) so the editor can confirm it scans.
const QR_PREVIEW_AMOUNT = 100
function QrphPreview({ payload }) {
  const [url, setUrl] = useState('')
  const trimmed = (payload || '').trim()
  const built = trimmed ? buildQrphPayload(trimmed, QR_PREVIEW_AMOUNT) : null
  const invalid = !!trimmed && !built

  useEffect(() => {
    const data = built || `QRPH|BW Superbakeshop|PHP ${QR_PREVIEW_AMOUNT.toFixed(2)}`
    QRCode.toDataURL(data, { width: 220, margin: 1 })
      .then(setUrl)
      .catch(() => setUrl(''))
  }, [built])

  return (
    <div className="mx-auto flex max-w-xs flex-col items-center gap-3 rounded-xl border border-slate-200 p-5 text-center">
      {url ? (
        <img src={url} alt="QR Ph preview" loading="lazy" decoding="async" className="h-48 w-48 rounded-lg" />
      ) : (
        <div className="flex h-48 w-48 items-center justify-center rounded-lg border-2 border-dashed border-slate-300 text-slate-300">
          …
        </div>
      )}
      <p className="text-sm font-semibold text-navy-800">Scan to pay ₱{QR_PREVIEW_AMOUNT.toFixed(2)}</p>
      {invalid ? (
        <p className="text-xs font-medium text-red-600">
          Couldn’t read this as a QR Ph code — a demo QR will be shown at checkout.
        </p>
      ) : built ? (
        <p className="text-xs text-slate-500">
          Preview shows a sample ₱{QR_PREVIEW_AMOUNT.toFixed(2)} — the real order amount is injected
          at checkout.
        </p>
      ) : (
        <p className="text-xs text-slate-400">
          No payload set — a demo QR is shown at checkout.
        </p>
      )}
    </div>
  )
}

function ItemCard({ children, index, total, onMove, onRemove, hideMove }) {
  return (
    <div className="flex flex-col rounded-xl border border-slate-200 bg-slate-50/60 p-4">
      <div className="mb-3 flex items-center justify-between">
        <span className="text-xs font-semibold uppercase tracking-wide text-slate-400">
          #{index + 1}
        </span>
        <div className="flex items-center gap-1">
          {!hideMove && (
            <>
              <IconBtn onClick={() => onMove(-1)} disabled={index === 0} label="Move up">
                ↑
              </IconBtn>
              <IconBtn onClick={() => onMove(1)} disabled={index === total - 1} label="Move down">
                ↓
              </IconBtn>
            </>
          )}
          <button
            onClick={onRemove}
            className="rounded-md px-2 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50"
          >
            Remove
          </button>
        </div>
      </div>
      <div className="space-y-3">{children}</div>
    </div>
  )
}

function IconBtn({ children, onClick, disabled, label }) {
  return (
    <button
      onClick={onClick}
      disabled={disabled}
      aria-label={label}
      className="flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40"
    >
      {children}
    </button>
  )
}

function AddButton({ children, onClick }) {
  return (
    <button
      onClick={onClick}
      className="mt-4 w-full rounded-xl border-2 border-dashed border-slate-300 py-3 text-sm font-semibold text-slate-500 transition hover:border-brand-400 hover:text-brand-600"
    >
      {children}
    </button>
  )
}

function Toggle({ on, onChange }) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={on}
      onClick={() => onChange(!on)}
      className={`relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition ${
        on ? 'bg-brand-500' : 'bg-slate-300'
      }`}
    >
      <span className="sr-only">{on ? 'Visible' : 'Hidden'}</span>
      <span
        className={`inline-block h-5 w-5 transform rounded-full bg-white shadow transition ${
          on ? 'translate-x-5' : 'translate-x-0.5'
        }`}
      />
    </button>
  )
}

// Three-way segmented control for a landing button: Visible / Disabled / Hidden.
const BUTTON_STATES = [
  ['on', 'Visible'],
  ['disabled', 'Disabled'],
  ['off', 'Hidden'],
]
function ButtonStateControl({ value, onChange }) {
  return (
    <div className="inline-flex shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
      {BUTTON_STATES.map(([state, label]) => {
        const active = value === state
        return (
          <button
            key={state}
            type="button"
            onClick={() => onChange(state)}
            aria-pressed={active}
            className={`px-3 py-1.5 text-xs font-semibold transition ${
              active
                ? state === 'off'
                  ? 'bg-slate-500 text-white'
                  : state === 'disabled'
                    ? 'bg-amber-500 text-white'
                    : 'bg-brand-500 text-white'
                : 'text-slate-500 hover:bg-slate-100'
            }`}
          >
            {label}
          </button>
        )
      })}
    </div>
  )
}

function TextRow({ label, value, onChange }) {
  return (
    <label className="block">
      <span className="mb-1 block text-xs font-medium text-slate-500">{label}</span>
      <input
        value={value || ''}
        onChange={(e) => onChange(e.target.value)}
        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
    </label>
  )
}

// Free-text input with autocomplete suggestions from a shared <datalist>.
// Lets editors reuse an existing category or type a brand-new one.
function ComboRow({ label, value, onChange, listId, placeholder }) {
  return (
    <label className="block">
      <span className="mb-1 block text-xs font-medium text-slate-500">{label}</span>
      <input
        list={listId}
        value={value || ''}
        placeholder={placeholder}
        onChange={(e) => onChange(e.target.value)}
        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
    </label>
  )
}

// Bulk category management. Categories are derived from products, so renaming
// (= merging) and deleting (= reassigning) are just edits to product.category.
// Changes mutate the editor's local products state, persisted on global Save.
function CategoryManager({
  products,
  setProducts,
  categoryOptions,
  declared,
  setDeclared,
  images = {},
  onSetImage = () => {},
  onRenameImage = () => {},
  onDeleteImage = () => {},
}) {
  const [newCat, setNewCat] = useState('')

  const counts = {}
  products.forEach((p) => {
    if (p.category) counts[p.category] = (counts[p.category] || 0) + 1
  })
  const cats = [...new Set([...Object.keys(counts), ...declared])].sort()

  const addCategory = () => {
    const name = newCat.trim()
    if (!name || cats.includes(name)) return
    setDeclared((list) => [...new Set([...list, name])])
    setNewCat('')
  }

  const renameCategory = (from, to) => {
    if (!to || to === from) return
    setProducts((ps) => ps.map((p) => (p.category === from ? { ...p, category: to } : p)))
    setDeclared((list) => [...new Set(list.map((c) => (c === from ? to : c)))])
    onRenameImage(from, to)
  }

  const deleteCategory = (from, to) => {
    setProducts((ps) => ps.map((p) => (p.category === from ? { ...p, category: to || null } : p)))
    setDeclared((list) => list.filter((c) => c !== from))
    onDeleteImage(from)
  }

  return (
    <>
      <datalist id="bw-categories">
        {categoryOptions.map((c) => (
          <option key={c} value={c} />
        ))}
      </datalist>

      {/* add a new category */}
      <div className="mb-5 flex items-end gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50/50 p-4">
        <label className="min-w-0 flex-1">
          <span className="mb-1 block text-xs font-medium text-slate-500">Add a new category</span>
          <input
            value={newCat}
            onChange={(e) => setNewCat(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && addCategory()}
            placeholder="e.g. Sandwiches"
            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
          />
        </label>
        <button
          type="button"
          onClick={addCategory}
          disabled={!newCat.trim() || cats.includes(newCat.trim())}
          className="shrink-0 rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:cursor-not-allowed disabled:opacity-40"
        >
          + Add
        </button>
      </div>

      {cats.length === 0 ? (
        <p className="text-sm text-slate-500">No categories yet — add one above.</p>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2">
          {cats.map((name) => (
            <CategoryRow
              key={name}
              name={name}
              count={counts[name] || 0}
              others={cats.filter((c) => c !== name)}
              image={images[name] || ''}
              onImage={(url) => onSetImage(name, url)}
              onRename={renameCategory}
              onDelete={deleteCategory}
            />
          ))}
        </div>
      )}
    </>
  )
}

function CategoryRow({ name, count, others, image, onImage, onRename, onDelete }) {
  const targetOptions = [...new Set([...others, 'Other'])].filter((o) => o !== name)
  const [rename, setRename] = useState(name)
  const [target, setTarget] = useState(targetOptions[0] || 'Other')

  return (
    <div className="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
      <div className="flex items-center justify-between">
        <p className="font-semibold text-navy-800">{name}</p>
        <span className="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">
          {count} product{count === 1 ? '' : 's'}
        </span>
      </div>

      <div className="mt-3">
        <ImageField label="Category image (optional)" value={image} onChange={onImage} />
        <p className="mt-1 text-[0.7rem] text-slate-400">
          Shown as the category badge on the menu. Leave empty to use the first product’s photo. Square works best (~400 × 400 px).
        </p>
      </div>

      <div className="mt-3 flex items-end gap-2">
        <label className="min-w-0 flex-1">
          <span className="mb-1 block text-xs font-medium text-slate-500">Rename / merge to</span>
          <input
            list="bw-categories"
            value={rename}
            onChange={(e) => setRename(e.target.value)}
            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
          />
        </label>
        <button
          type="button"
          onClick={() => onRename(name, rename.trim())}
          disabled={!rename.trim() || rename.trim() === name}
          className="shrink-0 rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-40"
        >
          Apply
        </button>
      </div>

      <div className="mt-2 flex items-end gap-2">
        <label className="min-w-0 flex-1">
          <span className="mb-1 block text-xs font-medium text-slate-500">Delete & move products to</span>
          <select
            value={target}
            onChange={(e) => setTarget(e.target.value)}
            className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-500"
          >
            {targetOptions.map((o) => (
              <option key={o} value={o}>
                {o}
              </option>
            ))}
          </select>
        </label>
        <button
          type="button"
          onClick={() => onDelete(name, target)}
          className="shrink-0 rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
        >
          Delete
        </button>
      </div>
    </div>
  )
}

function NumberRow({ label, value, onChange }) {
  return (
    <label className="block">
      <span className="mb-1 block text-xs font-medium text-slate-500">{label}</span>
      <input
        type="number"
        min="0"
        value={value === null || value === undefined ? '' : value}
        onChange={(e) => onChange(e.target.value === '' ? '' : Number(e.target.value))}
        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
    </label>
  )
}

function TextAreaRow({ label, value, onChange }) {
  return (
    <label className="block">
      <span className="mb-1 block text-xs font-medium text-slate-500">{label}</span>
      <textarea
        value={value || ''}
        onChange={(e) => onChange(e.target.value)}
        rows={3}
        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      />
    </label>
  )
}

function SelectRow({ label, value, onChange, options }) {
  return (
    <label className="block">
      <span className="mb-1 block text-xs font-medium text-slate-500">{label}</span>
      <select
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      >
        {options.map(([val, lab]) => (
          <option key={val} value={val}>
            {lab}
          </option>
        ))}
      </select>
    </label>
  )
}

function ImageField({ label, value, onChange, wide }) {
  const fileRef = useRef(null)
  const [busy, setBusy] = useState(false)
  const [err, setErr] = useState('')

  const pick = async (e) => {
    const file = e.target.files?.[0]
    if (!file) return
    setBusy(true)
    setErr('')
    try {
      onChange(await uploadImage(file))
    } catch (ex) {
      setErr(ex.message)
    } finally {
      setBusy(false)
      if (fileRef.current) fileRef.current.value = ''
    }
  }

  return (
    <div>
      <span className="mb-1 block text-xs font-medium text-slate-500">{label}</span>
      <div className="flex items-start gap-3">
        <div
          className={`shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100 ${
            wide ? 'h-16 w-32' : 'h-16 w-16'
          }`}
        >
          {value ? (
            <img src={value} alt="" loading="lazy" decoding="async" className="h-full w-full object-cover" />
          ) : (
            <div className="flex h-full w-full items-center justify-center text-[0.6rem] text-slate-400">
              no image
            </div>
          )}
        </div>
        <div className="min-w-0 flex-1">
          <input
            value={value || ''}
            onChange={(e) => onChange(e.target.value)}
            placeholder="Paste image URL"
            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
          />
          <div className="mt-1.5 flex items-center gap-2">
            <button
              onClick={() => fileRef.current?.click()}
              disabled={busy}
              className="rounded-md bg-navy-800 px-3 py-1 text-xs font-semibold text-white transition hover:bg-brand-600 disabled:opacity-60"
            >
              {busy ? 'Uploading…' : 'Upload'}
            </button>
            <input ref={fileRef} type="file" accept="image/*" onChange={pick} className="hidden" />
            {err && <span className="text-xs text-red-600">{err}</span>}
          </div>
        </div>
      </div>
    </div>
  )
}

/* icons */
function iconBase(p) {
  return {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 2,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    'aria-hidden': true,
    ...p,
  }
}
function MegaphoneIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <path d="m3 11 18-5v12L3 14v-3z" />
      <path d="M11.6 16.8a3 3 0 1 1-5.8-1.6" />
    </svg>
  )
}
function ChevronUpIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <path d="m18 15-6-6-6 6" />
    </svg>
  )
}
function KeyIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <circle cx="7.5" cy="15.5" r="4.5" />
      <path d="m10.7 12.3 8.3-8.3M16 5l2 2M14 7l2 2" />
    </svg>
  )
}
function ImageIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <rect x="3" y="3" width="18" height="18" rx="2" />
      <circle cx="9" cy="9" r="2" />
      <path d="m21 15-4.5-4.5L3 21" />
    </svg>
  )
}
function GridIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <rect x="3" y="3" width="7" height="7" rx="1" />
      <rect x="14" y="3" width="7" height="7" rx="1" />
      <rect x="3" y="14" width="7" height="7" rx="1" />
      <rect x="14" y="14" width="7" height="7" rx="1" />
    </svg>
  )
}
function StarIcon(p) {
  return (
    <svg {...iconBase({ ...p, fill: 'none' })}>
      <path d="m12 2 3 6.3 6.9.9-5 4.8 1.2 6.8L12 17.8 5.9 20.8 7.1 14l-5-4.8 6.9-.9Z" />
    </svg>
  )
}
function LoginIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
      <polyline points="10 17 15 12 10 7" />
      <line x1="15" y1="12" x2="3" y2="12" />
    </svg>
  )
}
function SparkleIcon(p) {
  return (
    <svg {...iconBase({ ...p, fill: 'currentColor' })}>
      <path d="M12 2l1.9 5.6a3 3 0 0 0 1.9 1.9L21.4 11.4l-5.6 1.9a3 3 0 0 0-1.9 1.9L12 20.8l-1.9-5.6a3 3 0 0 0-1.9-1.9L2.6 11.4l5.6-1.9a3 3 0 0 0 1.9-1.9L12 2z" />
    </svg>
  )
}
function TicketIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2 2 2 0 0 0 0 4 2 2 0 0 1-2 2H5a2 2 0 0 1-2-2 2 2 0 0 0 0-4Z" />
      <path d="M13 7v2M13 15v2" />
    </svg>
  )
}
function ToggleIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <rect x="2" y="7" width="20" height="10" rx="5" />
      <circle cx="9" cy="12" r="2.5" />
    </svg>
  )
}
function TagIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z" />
      <circle cx="7" cy="7" r="1.2" />
    </svg>
  )
}
function BriefcaseIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <rect x="2" y="7" width="20" height="14" rx="2" />
      <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
      <path d="M2 13h20" />
    </svg>
  )
}
function PinIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z" />
      <circle cx="12" cy="10" r="3" />
    </svg>
  )
}
function ChevronRightIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <polyline points="9 18 15 12 9 6" />
    </svg>
  )
}
function CakeIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <path d="M4 21h16v-7a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3Z" />
      <path d="M4 16c1.5 0 1.5 1.5 3 1.5s1.5-1.5 3-1.5 1.5 1.5 3 1.5 1.5-1.5 3-1.5 1.5 1.5 3 1.5" />
      <path d="M12 8V4M12 4l-1.2 1M12 4l1.2 1" />
    </svg>
  )
}
function MailIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <rect x="3" y="5" width="18" height="14" rx="2" />
      <path d="m3 7 9 6 9-6" />
    </svg>
  )
}
function QrIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <rect x="3" y="3" width="7" height="7" rx="1" />
      <rect x="14" y="3" width="7" height="7" rx="1" />
      <rect x="3" y="14" width="7" height="7" rx="1" />
      <path d="M14 14h3v3M21 21v.01M17 21h.01M21 17h.01" />
    </svg>
  )
}
function ShareIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <circle cx="18" cy="5" r="3" />
      <circle cx="6" cy="12" r="3" />
      <circle cx="18" cy="19" r="3" />
      <path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4" />
    </svg>
  )
}
