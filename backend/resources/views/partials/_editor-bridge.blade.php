{{-- Site Editor click-to-edit bridge — included only when the controller
     confirms this is a genuine editor preview (see Controller::isEditablePreview).
     Runs *inside* the previewed public page (which the Site Editor loads in an
     iframe — see admin/content/_preview.blade.php). Jobs:

     1. Every click on the page is intercepted and prevented — the preview iframe
        is normally pointer-events:none precisely so an editor can't accidentally
        navigate it away; this script is what makes that iframe interactive at
        all (the parent only lifts pointer-events for pages that include this
        bridge — see swapPreview's `editable` param), so it has to uphold that
        same "look, don't touch" guarantee itself, for every element, not just
        the editable ones.
     2. Elements tagged data-editable="<dotted CMS field path>" become directly
        editable in place:
          - Plain text targets get contenteditable — typing posts the live
            value back to the parent on every keystroke (see index.blade.php's
            "message" listener), which mirrors it into the real form field so
            autosave/dirty-state/preview-refresh all keep working unchanged.
            Enter submits (blurs) unless data-editable-multiline is set, the
            same way a real <input> vs <textarea> behaves.
          - <img data-editable> elements aren't editable in place (there's no
            sane "type a new photo") — clicking one instead tells the parent to
            open that field's real Upload flow.
          - data-editable-list marks a field bound to a structured/array value
            (e.g. a list rendered as several child elements) where inline
            editing isn't safe — clicking it just jumps to the sidebar field,
            the only behavior this bridge had before it grew inline editing.

     Message shapes (all posted to the parent as {source:'bw-editor-bridge', ...}):
       {type:'field-focus', path}         — focus entered an inline-editable field
       {type:'field-input',  path, value} — live text as the user types
       {type:'field-blur',   path}        — focus left an inline-editable field
       {type:'image',        path}        — an <img data-editable> was clicked
       {type:'jump',         path}        — data-editable-list click --}}
<style>
    /* pointer-events: auto !important overrides any pointer-events-none the
       public page itself applies to a tagged element (e.g. a decorative
       photo layered under other content) — otherwise the browser's own hit
       test would route the click to whatever's underneath it instead of
       here, and it could never be reached in the editable preview at all. */
    [data-editable] { cursor: pointer; pointer-events: auto !important; transition: outline-color .1s, background-color .1s; outline: 2px dashed transparent; outline-offset: 3px; border-radius: 4px; }
    [data-editable]:hover { outline-color: #f97316; background-color: rgba(249, 115, 22, .06); }
    [data-editable][contenteditable]:focus { outline: 2px solid #f97316; outline-offset: 3px; background-color: rgba(249, 115, 22, .08); cursor: text; }
</style>
<script>
    (() => {
        function isInlineEditable(el) {
            return !!el && el.tagName !== 'IMG' && !('editableList' in el.dataset)
        }
        function post(msg) {
            window.parent.postMessage(Object.assign({ source: 'bw-editor-bridge' }, msg), window.location.origin)
        }

        // Plaintext-only where supported so a paste can never smuggle in
        // markup we'd otherwise have to sanitize — harmless where unsupported
        // (Firefox lacks it) since only .innerText is ever read back below.
        function makeEditable(el) {
            if (!isInlineEditable(el)) return
            try { el.contentEditable = 'plaintext-only' } catch { el.contentEditable = 'true' }
        }
        document.querySelectorAll('[data-editable]').forEach(makeEditable)

        // Some pages build their CMS-driven markup client-side (e.g. /menu's
        // promo banner, re-rendered from a JSON blob via innerHTML rather
        // than server-rendered Blade) — a data-editable element can appear
        // well after this script's initial pass. All the click/focus/input
        // listeners below are already delegated on `document`, so they work
        // on a freshly-inserted element for free; only the imperative
        // contentEditable assignment needs re-running for it.
        new MutationObserver((mutations) => {
            for (const m of mutations) {
                for (const node of m.addedNodes) {
                    if (node.nodeType !== 1) continue
                    if (node.matches?.('[data-editable]')) makeEditable(node)
                    node.querySelectorAll?.('[data-editable]').forEach(makeEditable)
                }
            }
        }).observe(document.body, { childList: true, subtree: true })

        // Contenteditable focus/caret placement happens natively on
        // mousedown, before this click handler ever runs, so preventDefault()
        // here doesn't interfere with it — it only stops the click's *other*
        // default (e.g. franchise.email's data-editable span sits inside a
        // real <a href="mailto:...">, which must never actually navigate).
        document.addEventListener('click', (e) => {
            e.preventDefault()
            const el = e.target.closest('[data-editable]')
            if (!el) return
            if (el.tagName === 'IMG') { post({ type: 'image', path: el.dataset.editable }); return }
            if (!isInlineEditable(el)) { post({ type: 'jump', path: el.dataset.editable }); return }
            // Plain text: nothing more to do here — the focusin listener
            // below is what tells the parent, so Tab-key navigation into a
            // field (not just clicking) reports the same way.
        }, true)

        document.addEventListener('focusin', (e) => {
            const el = e.target.closest('[data-editable]')
            if (!isInlineEditable(el)) return
            post({ type: 'field-focus', path: el.dataset.editable })
        })

        document.addEventListener('input', (e) => {
            const el = e.target.closest('[data-editable]')
            if (!isInlineEditable(el)) return
            post({ type: 'field-input', path: el.dataset.editable, value: el.innerText })
        })

        document.addEventListener('focusout', (e) => {
            const el = e.target.closest('[data-editable]')
            if (!isInlineEditable(el)) return
            post({ type: 'field-blur', path: el.dataset.editable })
        })

        // Enter submits (like a real <input>) unless the field's sidebar
        // counterpart is a <textarea> (data-editable-multiline).
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter') return
            const el = e.target.closest('[data-editable]')
            if (!isInlineEditable(el) || 'editableMultiline' in el.dataset) return
            e.preventDefault()
            el.blur()
        })
    })()
</script>
