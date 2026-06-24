import { useEffect, useLayoutEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import ConfirmModal from '../components/ConfirmModal'
import Careers from './Careers'
import {
  DEFAULT_CONTENT,
  getSiteContent,
  saveSiteContent,
  uploadImage,
} from '../lib/content'
import { fetchApplications, getResumeUrl } from '../lib/careers'

// Admin "Careers" editor — HR panel for managing the /careers page content and
// viewing job applications. Follows the same pattern as AdminContent.jsx.

const SECTIONS = [
  { key: 'hero', label: 'Hero', Icon: ImageIcon },
  { key: 'perks', label: 'Perks', Icon: HeartIcon },
  { key: 'jobs', label: 'Job Listings', Icon: BriefcaseIcon },
  { key: 'filters', label: 'Filters', Icon: FilterIcon },
  { key: 'culture', label: 'Culture', Icon: UsersIcon },
  { key: 'applications', label: 'Applications', Icon: InboxIcon },
]

export default function AdminCareers() {
  const { logout, isAdmin, user } = useAuth()
  const [content, setContent] = useState(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState('')
  const [active, setActive] = useState('hero')
  const [confirmLogout, setConfirmLogout] = useState(false)

  // Applications state
  const [applications, setApplications] = useState([])
  const [appsLoading, setAppsLoading] = useState(false)

  useEffect(() => {
    getSiteContent().then((c) => {
      setContent(c)
      setLoading(false)
    })
  }, [])

  useEffect(() => {
    if (active === 'applications') {
      setAppsLoading(true)
      fetchApplications()
        .then(setApplications)
        .catch(() => setApplications([]))
        .finally(() => setAppsLoading(false))
    }
  }, [active])

  const save = async () => {
    setSaving(true)
    setMessage('')
    try {
      await saveSiteContent(content)
      setMessage('saved')
    } catch (err) {
      setMessage(`error:${err.message}`)
    } finally {
      setSaving(false)
    }
  }

  const resetDefaults = () => {
    if (window.confirm('Reset all careers content back to the defaults?')) {
      setContent((c) => ({ ...c, careers: structuredClone(DEFAULT_CONTENT.careers) }))
      setMessage('')
    }
  }

  // Careers content helpers (nested under content.careers)
  const cr = content?.careers || DEFAULT_CONTENT.careers
  const setCareers = (patch) =>
    setContent((c) => ({ ...c, careers: { ...(c.careers || DEFAULT_CONTENT.careers), ...patch } }))
  const setCareersHero = (patch) => setCareers({ hero: { ...cr.hero, ...patch } })
  const setCultureField = (patch) =>
    setCareers({ culture: { ...(cr.culture || DEFAULT_CONTENT.careers.culture), ...patch } })
  const updateCrList = (listKey, idx, patch) =>
    setCareers({ [listKey]: (cr[listKey] || []).map((it, i) => (i === idx ? { ...it, ...patch } : it)) })
  const addCrItem = (listKey, blank) => setCareers({ [listKey]: [...(cr[listKey] || []), blank] })
  const removeCrItem = (listKey, idx) =>
    setCareers({ [listKey]: (cr[listKey] || []).filter((_, i) => i !== idx) })

  if (loading || !content) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-navy-50/40 text-slate-500">
        Loading content...
      </div>
    )
  }

  const counts = {
    perks: (cr.perks || []).length,
    jobs: (cr.jobs || []).length,
    applications: applications.length,
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
            Careers Manager
          </p>
          <p className="mt-1 text-xs text-navy-50/60">Manage your careers page</p>
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
              {(user?.email || 'H').charAt(0).toUpperCase()}
            </span>
            <span className="min-w-0">
              <span className="block truncate text-sm font-semibold">{user?.name || 'HR'}</span>
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
                  &#10003; Saved &amp; published
                </span>
              )}
              {message.startsWith('error:') && (
                <span className="hidden max-w-xs truncate text-sm font-medium text-red-600 sm:inline">
                  &#10005; {message.slice(6)}
                </span>
              )}
              {active !== 'applications' && (
                <>
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
                    {saving ? 'Saving...' : 'Save changes'}
                  </button>
                </>
              )}
            </div>
          </div>
        </header>

        <div className="flex min-w-0 flex-1">
          <main className="min-w-0 flex-1 px-4 py-6 sm:px-6">
            {active === 'hero' && (
              <Panel title="Careers &#8212; Hero" subtitle="The top banner of the /careers page.">
                <TextRow
                  label="Title"
                  value={cr.hero?.title}
                  onChange={(title) => setCareersHero({ title })}
                />
                <TextAreaRow
                  label="Subtitle"
                  value={cr.hero?.subtitle}
                  onChange={(subtitle) => setCareersHero({ subtitle })}
                />
                <ImageField
                  label="Background image"
                  value={cr.hero?.image}
                  onChange={(image) => setCareersHero({ image })}
                  wide
                />
                <TextRow
                  label="Careers email (shown at bottom of job listings)"
                  value={cr.email}
                  onChange={(email) => setCareers({ email })}
                />
              </Panel>
            )}

            {active === 'perks' && (
              <Panel title="Careers &#8212; Perks" subtitle='The "Why join us" benefit cards.'>
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                  {(cr.perks || []).map((p, i) => (
                    <ItemCard
                      key={i}
                      index={i}
                      total={cr.perks.length}
                      hideMove
                      onRemove={() => removeCrItem('perks', i)}
                    >
                      <TextRow
                        label="Icon (emoji)"
                        value={p.icon}
                        onChange={(icon) => updateCrList('perks', i, { icon })}
                      />
                      <TextRow
                        label="Title"
                        value={p.title}
                        onChange={(title) => updateCrList('perks', i, { title })}
                      />
                      <TextAreaRow
                        label="Text"
                        value={p.text}
                        onChange={(text) => updateCrList('perks', i, { text })}
                      />
                    </ItemCard>
                  ))}
                </div>
                <AddButton onClick={() => addCrItem('perks', { icon: '&#10024;', title: 'New perk', text: '' })}>
                  + Add perk
                </AddButton>
              </Panel>
            )}

            {active === 'jobs' && (
              <Panel
                title="Careers &#8212; Job Listings"
                subtitle="Open positions shown on the Openings page. Department, Location, and Job Type options come from the Filters tab."
              >
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                  {(cr.jobs || []).map((job, i) => (
                    <ItemCard
                      key={i}
                      index={i}
                      total={cr.jobs.length}
                      hideMove
                      onRemove={() => removeCrItem('jobs', i)}
                    >
                      <TextRow
                        label="Job title"
                        value={job.title}
                        onChange={(title) => updateCrList('jobs', i, { title })}
                      />
                      <SelectRow
                        label="Department"
                        value={job.dept}
                        options={cr.departments || DEFAULT_CONTENT.careers.departments}
                        onChange={(dept) => updateCrList('jobs', i, { dept })}
                      />
                      <div className="grid grid-cols-2 gap-2">
                        <SelectRow
                          label="Job Type"
                          value={job.type}
                          options={cr.jobTypes || DEFAULT_CONTENT.careers.jobTypes}
                          onChange={(type) => updateCrList('jobs', i, { type })}
                        />
                        <SelectRow
                          label="Location"
                          value={job.location}
                          options={cr.locations || DEFAULT_CONTENT.careers.locations}
                          onChange={(location) => updateCrList('jobs', i, { location })}
                        />
                      </div>
                      <TextAreaRow
                        label="Description"
                        value={job.description}
                        onChange={(description) => updateCrList('jobs', i, { description })}
                      />
                      <TextAreaRow
                        label="Requirements (one per line)"
                        value={(job.requirements || []).join('\n')}
                        onChange={(v) =>
                          updateCrList('jobs', i, {
                            requirements: v.split('\n').map((s) => s.trim()).filter(Boolean),
                          })
                        }
                      />
                    </ItemCard>
                  ))}
                </div>
                <AddButton
                  onClick={() =>
                    addCrItem('jobs', { title: 'New position', dept: '', type: 'Full-time', location: '', description: '', requirements: [] })
                  }
                >
                  + Add job listing
                </AddButton>
              </Panel>
            )}

            {active === 'filters' && (
              <Panel
                title="Careers &#8212; Filters"
                subtitle="Manage the dropdown filter options on the Openings page. These also appear as selectable values when adding job listings."
              >
                <TagListEditor
                  label="Departments"
                  items={cr.departments || DEFAULT_CONTENT.careers.departments}
                  onChange={(departments) => setCareers({ departments })}
                />
                <TagListEditor
                  label="Locations"
                  items={cr.locations || DEFAULT_CONTENT.careers.locations}
                  onChange={(locations) => setCareers({ locations })}
                />
                <TagListEditor
                  label="Job Types"
                  items={cr.jobTypes || DEFAULT_CONTENT.careers.jobTypes}
                  onChange={(jobTypes) => setCareers({ jobTypes })}
                />
              </Panel>
            )}

            {active === 'culture' && (
              <div className="space-y-6">
                <Panel title="Careers &#8212; Culture" subtitle="The culture section of the /careers page.">
                  <TextRow
                    label="Eyebrow"
                    value={cr.culture?.eyebrow}
                    onChange={(eyebrow) => setCultureField({ eyebrow })}
                  />
                  <TextRow
                    label="Title"
                    value={cr.culture?.title}
                    onChange={(title) => setCultureField({ title })}
                  />
                  <TextAreaRow
                    label="Text"
                    value={cr.culture?.text}
                    onChange={(text) => setCultureField({ text })}
                  />
                  <ImageField
                    label="Image"
                    value={cr.culture?.image}
                    onChange={(image) => setCultureField({ image })}
                    wide
                  />
                  <div className="grid grid-cols-2 gap-2">
                    <TextRow
                      label="Stat number"
                      value={cr.culture?.stat}
                      onChange={(stat) => setCultureField({ stat })}
                    />
                    <TextRow
                      label="Stat label"
                      value={cr.culture?.statLabel}
                      onChange={(statLabel) => setCultureField({ statLabel })}
                    />
                  </div>
                  <TextAreaRow
                    label="Highlights (one per line)"
                    value={(cr.culture?.highlights || []).join('\n')}
                    onChange={(v) =>
                      setCultureField({
                        highlights: v
                          .split('\n')
                          .map((s) => s.trim())
                          .filter(Boolean),
                      })
                    }
                  />
                </Panel>
              </div>
            )}

            {active === 'applications' && (
              <Panel title="Job Applications" subtitle="CVs and applications received from the careers page.">
                {appsLoading ? (
                  <p className="py-8 text-center text-sm text-slate-500">Loading applications...</p>
                ) : applications.length === 0 ? (
                  <p className="py-8 text-center text-sm text-slate-500">No applications yet.</p>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                      <thead>
                        <tr className="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-400">
                          <th className="px-3 py-3">Name</th>
                          <th className="px-3 py-3">Email</th>
                          <th className="px-3 py-3">Phone</th>
                          <th className="px-3 py-3">Position</th>
                          <th className="px-3 py-3">Date</th>
                          <th className="px-3 py-3">CV</th>
                        </tr>
                      </thead>
                      <tbody>
                        {applications.map((app) => (
                          <tr key={app.id} className="border-b border-slate-100 transition hover:bg-slate-50">
                            <td className="px-3 py-3 font-medium text-navy-800">{app.name}</td>
                            <td className="px-3 py-3 text-slate-600">{app.email}</td>
                            <td className="px-3 py-3 text-slate-600">{app.phone || '—'}</td>
                            <td className="px-3 py-3 text-slate-600">{app.position}</td>
                            <td className="px-3 py-3 text-slate-500">
                              {new Date(app.created_at).toLocaleDateString('en-PH', {
                                month: 'short',
                                day: 'numeric',
                                year: 'numeric',
                              })}
                            </td>
                            <td className="px-3 py-3">
                              {app.cv_url ? (
                                <DownloadCvButton cvPath={app.cv_url} />
                              ) : (
                                <span className="text-slate-400">—</span>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </Panel>
            )}
          </main>

          {/* live preview (hidden for applications tab) */}
          {active !== 'applications' && (
            <aside className="hidden shrink-0 border-l border-slate-200 bg-slate-100/70 xl:block xl:w-[42%]">
              <div
                data-preview-scroll
                className="sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto p-5"
              >
                <p className="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                  <span className="h-2 w-2 rounded-full bg-green-500" />
                  Live preview &#8212; full page, unsaved changes
                </p>
                <CareersPreview content={content} />
              </div>
            </aside>
          )}
        </div>
      </div>

      {confirmLogout && (
        <ConfirmModal
          title="Log out?"
          message="You’ll be signed out of the Careers admin. Any unsaved changes will be lost."
          confirmLabel="Log out"
          loadingLabel="Logging out"
          onConfirm={logout}
          onCancel={() => setConfirmLogout(false)}
        />
      )}
    </div>
  )
}

// Download button that fetches a fresh signed URL on click.
function DownloadCvButton({ cvPath }) {
  const [busy, setBusy] = useState(false)
  const handleClick = async () => {
    setBusy(true)
    try {
      const url = await getResumeUrl(cvPath)
      window.open(url, '_blank')
    } catch {
      alert('Could not download CV. Please try again.')
    } finally {
      setBusy(false)
    }
  }
  return (
    <button
      onClick={handleClick}
      disabled={busy}
      className="rounded-md bg-navy-800 px-3 py-1 text-xs font-semibold text-white transition hover:bg-brand-600 disabled:opacity-60"
    >
      {busy ? '...' : 'Download'}
    </button>
  )
}

// Scaled-down live preview of the Careers page (mirrors FullPreview in AdminContent).
const PREVIEW_BASE_WIDTH = 1280

function CareersPreview({ content }) {
  const wrapRef = useRef(null)
  const innerRef = useRef(null)
  const [scale, setScale] = useState(0.4)
  const [height, setHeight] = useState(0)

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
        <Careers content={content} preview />
      </div>
    </div>
  )
}

/* Shared editor UI components (duplicated from AdminContent for self-containment) */

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
                &uarr;
              </IconBtn>
              <IconBtn onClick={() => onMove(1)} disabled={index === total - 1} label="Move down">
                &darr;
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

function SelectRow({ label, value, options, onChange }) {
  return (
    <label className="block">
      <span className="mb-1 block text-xs font-medium text-slate-500">{label}</span>
      <select
        value={value || ''}
        onChange={(e) => onChange(e.target.value)}
        className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
      >
        <option value="">— Select —</option>
        {options.map((opt) => (
          <option key={opt} value={opt}>{opt}</option>
        ))}
      </select>
    </label>
  )
}

function TagListEditor({ label, items, onChange }) {
  const [newVal, setNewVal] = useState('')

  const add = () => {
    const v = newVal.trim()
    if (!v || items.includes(v)) return
    onChange([...items, v])
    setNewVal('')
  }

  return (
    <div className="mb-5">
      <span className="mb-2 block text-sm font-semibold text-navy-800">{label}</span>
      <div className="flex flex-wrap gap-2">
        {items.map((item, i) => (
          <span
            key={item}
            className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-navy-800"
          >
            {item}
            <button
              onClick={() => onChange(items.filter((_, idx) => idx !== i))}
              className="ml-0.5 text-slate-400 transition hover:text-red-500"
              aria-label={`Remove ${item}`}
            >
              &#10005;
            </button>
          </span>
        ))}
      </div>
      <div className="mt-2 flex gap-2">
        <input
          value={newVal}
          onChange={(e) => setNewVal(e.target.value)}
          onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); add() } }}
          placeholder={`Add new ${label.toLowerCase().replace(/s$/, '')}...`}
          className="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
        />
        <button
          onClick={add}
          disabled={!newVal.trim()}
          className="rounded-lg bg-navy-800 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-600 disabled:opacity-40"
        >
          Add
        </button>
      </div>
    </div>
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
              {busy ? 'Uploading...' : 'Upload'}
            </button>
            <input ref={fileRef} type="file" accept="image/*" onChange={pick} className="hidden" />
            {err && <span className="text-xs text-red-600">{err}</span>}
          </div>
        </div>
      </div>
    </div>
  )
}

/* Icons */
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
function ImageIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <rect x="3" y="3" width="18" height="18" rx="2" />
      <circle cx="9" cy="9" r="2" />
      <path d="m21 15-4.5-4.5L3 21" />
    </svg>
  )
}
function HeartIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
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
function UsersIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
      <circle cx="9" cy="7" r="4" />
      <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
      <path d="M16 3.13a4 4 0 0 1 0 7.75" />
    </svg>
  )
}
function FilterIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
    </svg>
  )
}
function InboxIcon(p) {
  return (
    <svg {...iconBase(p)}>
      <polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
      <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
    </svg>
  )
}
