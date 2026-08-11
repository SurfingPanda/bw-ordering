{{-- Live-preview iframe for the Site Editor shell's preview pane: the real
     public page rendered at a chosen device width (Mobile/Tablet/Desktop —
     desktop by default), scaled to fit the column (like the SPA's
     scaled-down FullPreview). $url = the page to preview.
     The content editor navigates the preview via window.swapPreview(url, editable),
     which double-buffers: the new page loads in a hidden iframe and is only
     swapped in once rendered, so the visible preview never blanks/blinks.

     The iframe defaults to pointer-events:none so the preview is *look, don't
     touch* — clicking a link inside it can't navigate the preview away.
     Callers previewing a page that includes partials/_editor-bridge (which
     enforces that same guarantee itself, click by click, while also enabling
     click-to-edit) pass editable=true to lift that restriction for this one
     swap. Scrolling is handled by the outer box instead (the iframe is sized
     to the full page height and the box scrolls it). --}}
<div class="mb-3 flex items-center justify-end gap-1 rounded-lg bg-slate-200/60 p-1" role="group" aria-label="Preview device size">
    <button type="button" data-preview-device="375" class="preview-device-btn flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:text-navy-800">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="2" width="10" height="20" rx="2" /><line x1="11" y1="18" x2="13" y2="18" /></svg>
        Mobile
    </button>
    <button type="button" data-preview-device="768" class="preview-device-btn flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:text-navy-800">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="2" width="16" height="20" rx="2" /><line x1="11" y1="18" x2="13" y2="18" /></svg>
        Tablet
    </button>
    <button type="button" data-preview-device="1280" data-active class="preview-device-btn flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:text-navy-800">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="14" rx="2" /><line x1="8" y1="21" x2="16" y2="21" /><line x1="12" y1="18" x2="12" y2="21" /></svg>
        Desktop
    </button>
</div>
<div id="preview-box" class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div id="preview-sizer" class="relative mx-auto">
        <iframe id="preview-frame" src="{{ $url ?? '/' }}" title="Live preview" tabindex="-1"
            class="pointer-events-none absolute left-0 top-0 origin-top-left" style="width: 1280px; height: 800px; border: 0"></iframe>
    </div>
</div>
<script>
    (() => {
        const box = document.getElementById('preview-box')
        const sizer = document.getElementById('preview-sizer')
        let frame = document.getElementById('preview-frame')
        let pending = null
        let modalObserver = null
        let unflattened = false // true while a same-page modal has temporarily taken over real iframe scrolling
        // The iframe's real internal viewport width — changing this (via the
        // Mobile/Tablet/Desktop toggle below) makes the previewed page's own
        // responsive CSS breakpoints kick in for real, not just a visual
        // zoom, before it's scaled down to fit the pane.
        let deviceWidth = 1280

        // Never zoomed IN past 100% — a narrow device width (Mobile/Tablet)
        // shown in a much wider pane renders as a true-to-size column
        // centered in the pane (sizer's `mx-auto`), not stretched full-bleed
        // to fill it (which is what made Mobile look blown-up/zoomed-in).
        // Only zooms OUT below 100% when the pane itself is narrower than
        // the chosen device width.
        function currentScale() {
            return Math.min(box.clientWidth / deviceWidth, 1)
        }

        function fitPreview() {
            if (!box.clientWidth) return // pane hidden below the xl breakpoint
            if (unflattened) return // a modal is open — see unflatten() below
            frame.style.width = deviceWidth + 'px'
            const scale = currentScale()
            // Size the iframe to the full page height so the OUTER box scrolls
            // it (the iframe itself is non-interactive). Same-origin, so we can
            // read the rendered height; fall back to one viewport pre-load.
            let contentH = box.clientHeight / scale
            try {
                const d = frame.contentDocument
                if (d && d.body) contentH = Math.max(d.body.scrollHeight, d.documentElement.scrollHeight)
            } catch { /* not loaded yet — keep the fallback */ }
            frame.style.height = contentH + 'px'
            frame.style.transform = `scale(${scale})`
            sizer.style.width = (deviceWidth * scale) + 'px'
            sizer.style.height = (contentH * scale) + 'px'
        }

        // The iframe is normally sized to the *full* page height so the outer
        // box scrolls it, not the iframe (scrolling a scaled-down iframe
        // natively feels wrong — wheel deltas don't match the visual scale).
        // But that means the iframe has no real bounded viewport, so
        // `position: fixed` elements inside it (modals) end up centered on
        // the whole flattened page instead of whatever's currently visible.
        // Every modal in this app follows the same convention (product
        // modal, logout-confirm, etc.): `role="dialog"` on the backdrop,
        // shown/hidden by toggling the `hidden` class — not every page also
        // locks background scroll, so that's the one signal consistent
        // enough to watch. While any dialog is visible, temporarily give the
        // iframe a real bounded viewport (matching what was on screen) and
        // let it scroll internally instead, so `fixed` positioning resolves
        // correctly. Reverse it once the dialog closes.
        function hasOpenDialog(doc) {
            return !!Array.from(doc.querySelectorAll('[role="dialog"]')).find((el) => !el.classList.contains('hidden'))
        }
        function unflatten() {
            if (unflattened || !box.clientWidth) return
            const scale = currentScale()
            const topOffset = box.scrollTop / scale
            unflattened = true
            frame.style.height = (box.clientHeight / scale) + 'px'
            sizer.style.height = box.clientHeight + 'px'
            try { frame.contentWindow.scrollTo(0, topOffset) } catch { /* same-origin, shouldn't happen */ }
        }
        function reflatten() {
            if (!unflattened) return
            let scrollY = 0
            try { scrollY = frame.contentWindow.scrollY || 0 } catch { /* same-origin, shouldn't happen */ }
            unflattened = false
            fitPreview()
            const scale = currentScale()
            box.scrollTop = scrollY * scale
        }
        function watchModals(doc) {
            if (modalObserver) modalObserver.disconnect()
            if (!doc || !doc.body) return
            if (hasOpenDialog(doc)) unflatten()
            modalObserver = new MutationObserver(() => {
                if (hasOpenDialog(doc)) unflatten()
                else reflatten()
            })
            modalObserver.observe(doc.body, { subtree: true, attributes: true, attributeFilter: ['class'] })
        }

        // Mobile/Tablet/Desktop toggle — just swaps deviceWidth and re-fits;
        // the currently loaded page stays put (no reload needed since the
        // iframe already holds a real, same-origin document).
        document.querySelectorAll('.preview-device-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                deviceWidth = Number(btn.dataset.previewDevice)
                document.querySelectorAll('.preview-device-btn').forEach((b) => {
                    const active = b === btn
                    b.toggleAttribute('data-active', active)
                    b.classList.toggle('bg-white', active)
                    b.classList.toggle('shadow-sm', active)
                    b.classList.toggle('text-navy-800', active)
                    b.classList.toggle('text-slate-500', !active)
                })
                unflattened = false
                fitPreview()
            })
        })
        document.querySelector('.preview-device-btn[data-active]')?.classList.add('bg-white', 'shadow-sm', 'text-navy-800')

        // Double-buffered navigation: load the url in a hidden clone of the
        // iframe and swap it in only once it has fully loaded, so the visible
        // preview keeps showing the previous render instead of blanking. A
        // newer swap cancels any still-loading one.
        window.swapPreview = (url, editable = false) => {
            if (pending) { pending.remove(); pending = null }
            const next = frame.cloneNode(false)
            next.removeAttribute('id')
            next.style.visibility = 'hidden'
            // cloneNode copies the pointer-events-none class from `frame`
            // (the safe default) — only override it for a page that actually
            // ships the bridge enforcing "look, don't touch" on its own.
            next.style.pointerEvents = editable ? 'auto' : ''
            next.addEventListener('load', () => {
                if (next !== pending) return // superseded by a newer swap
                pending = null
                frame.remove()
                frame = next
                frame.id = 'preview-frame'
                frame.style.visibility = ''
                unflattened = false
                fitPreview()
                try { watchModals(frame.contentDocument) } catch { /* same-origin, shouldn't happen */ }
            })
            pending = next
            next.src = url
            sizer.appendChild(next)
        }

        // Re-measure when the initial page loads (later swaps re-fit above)
        // and on resize.
        frame.addEventListener('load', () => {
            fitPreview()
            try { watchModals(frame.contentDocument) } catch { /* same-origin, shouldn't happen */ }
        })
        window.addEventListener('resize', fitPreview)
        fitPreview()
        try { watchModals(frame.contentDocument) } catch { /* not loaded yet — the load listener above will catch it */ }
    })()
</script>
