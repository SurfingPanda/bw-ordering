{{-- Site Editor click-to-edit bridge — included only when the controller
     confirms this is a genuine editor preview (see Controller::isEditablePreview).
     Runs *inside* the previewed public page (which the Site Editor loads in an
     iframe — see admin/content/_preview.blade.php). Two jobs:

     1. Every click on the page is intercepted and prevented — the preview iframe
        is normally pointer-events:none precisely so an editor can't accidentally
        navigate it away; this script is what makes that iframe interactive at
        all (the parent only lifts pointer-events for pages that include this
        bridge — see swapPreview's `editable` param), so it has to uphold that
        same "look, don't touch" guarantee itself, for every element, not just
        the editable ones.
     2. Clicking an element tagged data-editable="<dotted CMS field path>" posts
        that path to the parent Site Editor window, which switches to the right
        tab and focuses the matching field (see index.blade.php's "message"
        listener and revealField()). --}}
<style>
    [data-editable] { cursor: pointer; transition: outline-color .1s, background-color .1s; outline: 2px dashed transparent; outline-offset: 3px; border-radius: 4px; }
    [data-editable]:hover { outline-color: #f97316; background-color: rgba(249, 115, 22, .06); }
</style>
<script>
    (() => {
        document.addEventListener('click', (e) => {
            e.preventDefault()
            const el = e.target.closest('[data-editable]')
            if (!el) return
            window.parent.postMessage({ source: 'bw-editor-bridge', path: el.dataset.editable }, window.location.origin)
        }, true)
    })()
</script>
