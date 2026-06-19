import { useEffect, useLayoutEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import Landing from './Landing'
import {
  DEFAULT_CONTENT,
  LANDING_BUTTONS,
  fetchAllProducts,
  getSiteContent,
  saveSiteContent,
  syncProducts,
  uploadImage,
} from '../lib/content'

// Admin "Site Content" editor — full-width CMS layout with a section sidebar.
// Edits the landing page content, the Menu products, and the Franchise page.

const SECTIONS = [
  { key: 'announcement', label: 'Announcement', Icon: MegaphoneIcon },
  { key: 'banners', label: 'Promo Banners', Icon: ImageIcon },
  { key: 'categories', label: 'Categories', Icon: GridIcon },
  { key: 'bestSellers', label: 'Best Sellers', Icon: StarIcon },
  { key: 'products', label: 'Products', Icon: TagIcon },
  { key: 'franchise', label: 'Franchise', Icon: BriefcaseIcon },
  { key: 'buttons', label: 'Buttons', Icon: ToggleIcon },
]

// LANDING_BUTTONS grouped by their `group` field, preserving first-seen order,
// for the "Buttons" editor's sectioned switch list.
const BUTTON_GROUPS = LANDING_BUTTONS.reduce((acc, b) => {
  const entry = acc.find(([g]) => g === b.group)
  if (entry) entry[1].push(b)
  else acc.push([b.group, [b]])
  return acc
}, [])

export default function AdminContent() {
  const { logout, isAdmin, user } = useAuth()
  const [content, setContent] = useState(null)
  const [products, setProducts] = useState([])
  const [originalIds, setOriginalIds] = useState([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState('')
  const [active, setActive] = useState('announcement')

  useEffect(() => {
    Promise.all([getSiteContent(), fetchAllProducts().catch(() => [])]).then(([c, p]) => {
      setContent(c)
      setProducts(p)
      setOriginalIds(p.map((x) => x.id))
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
      setMessage('saved')
    } catch (err) {
      setMessage(`error:${err.message}`)
    } finally {
      setSaving(false)
    }
  }

  const resetDefaults = () => {
    if (window.confirm('Reset all landing content back to the defaults?')) {
      setContent(structuredClone(DEFAULT_CONTENT))
      setMessage('')
    }
  }

  const setField = (key, value) => setContent((c) => ({ ...c, [key]: value }))
  const setButton = (key, on) =>
    setContent((c) => ({ ...c, buttons: { ...(c.buttons || {}), [key]: on } }))
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
        isFeatured: false,
        status: null,
      },
    ])
  const removeProduct = (idx) => setProducts((ps) => ps.filter((_, i) => i !== idx))

  // Franchise content (nested under content.franchise).
  const fr = content?.franchise || DEFAULT_CONTENT.franchise
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

        <nav className="flex gap-2 overflow-x-auto p-3 lg:flex-1 lg:flex-col lg:gap-1 lg:overflow-y-auto">
          {SECTIONS.map((s) => {
            const on = active === s.key
            return (
              <button
                key={s.key}
                onClick={() => setActive(s.key)}
                className={`flex shrink-0 items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition lg:w-full ${
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
        </nav>

        <div className="border-t border-white/10 px-5 py-4">
          <div className="flex items-center gap-3">
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-bold">
              {(user?.email || 'E').charAt(0).toUpperCase()}
            </span>
            <span className="min-w-0">
              <span className="block truncate text-sm font-semibold">{user?.name || 'Editor'}</span>
              <span className="block truncate text-xs text-navy-50/60">{user?.email}</span>
            </span>
          </div>
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
              onClick={logout}
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

          {active === 'categories' && (
            <Panel title="Categories" subtitle="The round “Shop by category” badges.">
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
            <Panel title="Best Selling Foods" subtitle="The “Our Best Sellers” product cards.">
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
                  </ItemCard>
                ))}
              </div>
              <AddButton
                onClick={() =>
                  addItem('bestSellers', { name: 'New item', price: '₱0', tag: 'New', img: '' })
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
                      <TextRow
                        label="Category"
                        value={p.category}
                        onChange={(category) => updateProduct(i, { category })}
                      />
                      <SelectRow
                        label="Status"
                        value={p.status || ''}
                        onChange={(status) => updateProduct(i, { status: status || null })}
                        options={[
                          ['', 'None'],
                          ['new', 'New'],
                          ['best_seller', 'Best seller'],
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
                    <TextAreaRow
                      label="Description"
                      value={p.desc}
                      onChange={(desc) => updateProduct(i, { desc })}
                    />
                    <TextAreaRow
                      label="Tags (one per line)"
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

          {active === 'buttons' && (
            <Panel
              title="Buttons & Calls-to-Action"
              subtitle="Show or hide the action buttons across your landing page. Hidden buttons disappear from the live site."
            >
              <div className="space-y-6">
                {BUTTON_GROUPS.map(([group, items]) => (
                  <div key={group}>
                    <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                      {group}
                    </p>
                    <div className="overflow-hidden rounded-xl border border-slate-200">
                      {items.map((b, i) => {
                        const on = content.buttons?.[b.key] !== false
                        return (
                          <div
                            key={b.key}
                            className={`flex items-center justify-between gap-4 px-4 py-3 ${
                              i > 0 ? 'border-t border-slate-100' : ''
                            }`}
                          >
                            <span className="text-sm font-medium text-navy-800">{b.label}</span>
                            <Toggle on={on} onChange={(v) => setButton(b.key, v)} />
                          </div>
                        )
                      })}
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
            <FullPreview content={content} active={active} />
          </div>
        </aside>
        </div>
      </div>
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
  products: null,
  franchise: null,
  buttons: null,
}

function FullPreview({ content, active }) {
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
        <Landing content={content} preview />
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
            <img src={value} alt="" className="h-full w-full object-cover" />
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
