{{-- Header Save/Reset pair for a Site Editor form ($formId). Both start
     hidden and appear on the first edit (typed change, toggle, or a repeater
     row added/moved/removed). Reset discards unsaved edits by reloading the
     server-rendered state. --}}
<button type="button" data-reset-button
    class="hidden rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600">
    Reset
</button>
<button type="submit" form="{{ $formId }}" data-save-button
    class="hidden rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
    Save changes
</button>

{{-- Styled confirmation for Reset — a Blade port of the old SPA's ConfirmModal,
     replacing the native window.confirm() browser dialog. --}}
<div id="reset-confirm" class="fixed inset-0 z-[80] hidden items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Discard changes">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" data-modal-card>
        <h3 class="text-lg font-bold text-navy-800">Discard changes?</h3>
        <p class="mt-2 text-sm leading-relaxed text-slate-500">Your unsaved edits will be lost and the last saved content will be restored.</p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" data-modal-cancel class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-navy-700 transition hover:bg-slate-50">
                Keep editing
            </button>
            <button type="button" data-modal-confirm class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                Discard changes
            </button>
        </div>
    </div>
</div>

<script>
    // This script is parsed in the sticky header, before the form exists in
    // the DOM — wait for the full document before wiring anything up.
    document.addEventListener('DOMContentLoaded', () => {
        const save = document.querySelector('[data-save-button]')
        const reset = document.querySelector('[data-reset-button]')
        const form = document.getElementById('{{ $formId }}')

        let dirty = false

        const markDirty = (e) => {
            if (e && e.target.closest('[data-no-dirty]')) return
            save.classList.remove('hidden')
            reset.classList.remove('hidden')
            dirty = true
        }
        form.addEventListener('input', markDirty)
        form.addEventListener('change', markDirty)
        form.addEventListener('click', (e) => {
            if (e.target.closest('[data-add], [data-remove], [data-move]')) markDirty(e)
        })

        // Warn before leaving with unsaved edits (closed tab, back button,
        // typed URL) — a real submit clears `dirty` first so Save/Reset never
        // trigger this themselves.
        window.addEventListener('beforeunload', (e) => {
            if (! dirty) return
            e.preventDefault()
            e.returnValue = ''
        })

        // Reset opens a styled confirmation modal (not the native browser
        // confirm dialog). Confirming reloads the server-rendered state, which
        // discards the unsaved edits.
        const modal = document.getElementById('reset-confirm')
        const openModal = () => { modal.classList.remove('hidden'); modal.classList.add('flex') }
        const closeModal = () => { modal.classList.add('hidden'); modal.classList.remove('flex') }

        reset.addEventListener('click', openModal)
        modal.querySelector('[data-modal-cancel]').addEventListener('click', closeModal)
        modal.querySelector('[data-modal-confirm]').addEventListener('click', () => { dirty = false; location.reload() })
        // Dismiss on backdrop click or Escape, like the old modal.
        modal.addEventListener('click', (e) => { if (! e.target.closest('[data-modal-card]')) closeModal() })
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal() })

        // This is a real (non-AJAX) form submit — the browser navigates away
        // and back once the server redirects, which can take a moment on a
        // big blob. Without feedback, that gap reads as a dead button.
        form.addEventListener('submit', () => {
            dirty = false
            save.disabled = true
            save.classList.add('cursor-not-allowed', 'opacity-60')
            save.textContent = 'Saving…'
        })
    })
</script>
