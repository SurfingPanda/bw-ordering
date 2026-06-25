import { useEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'

// Shared confirmation modal (replaces the native window.confirm). Used by the
// logout buttons across the app and the Site Editor's reset/logout prompts.
// `onConfirm` may be async — while it runs the buttons show a loading state.
export default function ConfirmModal({
  title,
  message,
  confirmLabel = 'Confirm',
  loadingLabel,
  cancelLabel = 'Cancel',
  onConfirm,
  onCancel,
}) {
  const [busy, setBusy] = useState(false)
  const alive = useRef(true)
  useEffect(() => {
    alive.current = true
    return () => {
      alive.current = false
    }
  }, [])

  useEffect(() => {
    const onKey = (e) => e.key === 'Escape' && !busy && onCancel()
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [onCancel, busy])

  const confirm = async () => {
    if (busy) return
    setBusy(true)
    try {
      await onConfirm()
    } finally {
      // The modal often unmounts on success (e.g. logout navigates away); only
      // reset if we're still mounted so a failed action stays actionable.
      if (alive.current) setBusy(false)
    }
  }

  // Portal to <body> so the fixed overlay is always centered on the viewport,
  // never trapped inside a transformed/backdrop-filtered ancestor (e.g. the
  // Menu page's blurred sticky header) which would create a containing block.
  if (typeof document === 'undefined') return null

  return createPortal(
    <div
      className="fixed inset-0 z-[80] flex items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm"
      onClick={() => !busy && onCancel()}
      role="dialog"
      aria-modal="true"
      aria-label={title}
    >
      <div
        className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        <h3 className="text-lg font-bold text-navy-800">{title}</h3>
        <p className="mt-2 text-sm leading-relaxed text-slate-500">{message}</p>
        <div className="mt-6 flex justify-end gap-3">
          <button
            type="button"
            onClick={onCancel}
            disabled={busy}
            className="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-navy-700 transition hover:bg-slate-50 disabled:opacity-50"
          >
            {cancelLabel}
          </button>
          <button
            type="button"
            onClick={confirm}
            disabled={busy}
            className="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:cursor-wait disabled:opacity-80"
          >
            {busy && (
              <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
            )}
            {busy ? loadingLabel || `${confirmLabel}…` : confirmLabel}
          </button>
        </div>
      </div>
    </div>,
    document.body,
  )
}
