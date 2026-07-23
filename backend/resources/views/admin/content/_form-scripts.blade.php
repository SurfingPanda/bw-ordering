{{-- Shared Site Editor form behaviors: repeater rows (add / move / remove /
     renumber) and image fields (thumbnail preview + Upload → /admin/uploads).
     Included by the content editor and the products editor. --}}

{{-- Circular pan/zoom cropper — opens for any [data-image-field
     data-image-crop="circle"] (currently just menu category images) instead
     of uploading the raw file straight away, so an off-center subject in a
     rectangular photo doesn't just get silently center-cropped by the
     rounded-full badge it's displayed in elsewhere. One modal shared by
     every such field on the page; see the JS below for the wiring. --}}
<div id="image-cropper-modal" class="fixed inset-0 z-[95] hidden items-center justify-center bg-navy-900/60 p-4" role="dialog" aria-modal="true" aria-label="Adjust image">
    <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl">
        <h3 class="text-base font-bold text-navy-800">Adjust your image</h3>
        <p class="mt-1 text-xs text-slate-500">Drag to reposition, use the slider to zoom — the dimmed corners are what the circle badge will crop out.</p>

        <div id="cropper-viewport" class="relative mx-auto mt-4 h-72 w-72 cursor-grab touch-none select-none overflow-hidden rounded-xl bg-slate-900 active:cursor-grabbing">
            <img id="cropper-image" src="" alt="" draggable="false" class="pointer-events-none absolute left-0 top-0 max-w-none select-none">
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                <div class="h-64 w-64 rounded-full" style="box-shadow: 0 0 0 9999px rgba(15, 23, 42, .55);"></div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></svg>
            <input id="cropper-zoom" type="range" min="1" max="3" step="0.01" value="1" class="w-full accent-brand-500" aria-label="Zoom">
            <svg class="h-5 w-5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></svg>
        </div>

        <div class="mt-5 flex justify-end gap-3">
            <button type="button" id="cropper-cancel" class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-navy-700 transition hover:bg-slate-50">Cancel</button>
            <button type="button" id="cropper-apply" class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">Use this image</button>
        </div>
    </div>
</div>

<script>
    // ---- repeaters -------------------------------------------------------
    // [data-repeater] holds [data-rows], a <template> row (indices are the
    // repeater's token, default __IDX__), and a [data-add] button. Rows are
    // removed via [data-remove] and reordered via [data-move]. Indices only
    // need to be unique — the server reindexes in DOM order — so clones use a
    // global counter.
    let repeaterUid = 10000

    function rowsOf(repeater) {
        return Array.from(repeater.querySelector(':scope > [data-rows]').children).filter((el) => el.matches('[data-row]'))
    }

    function renumber(repeater) {
        const rows = rowsOf(repeater)
        rows.forEach((row, i) => {
            const num = row.querySelector('[data-row-number]')
            if (num) num.textContent = '#' + (i + 1)
            const up = row.querySelector('[data-move="-1"]')
            const down = row.querySelector('[data-move="1"]')
            if (up) up.disabled = i === 0
            if (down) down.disabled = i === rows.length - 1
        })
    }

    document.querySelectorAll('[data-repeater]').forEach(renumber)

    document.addEventListener('click', (e) => {
        const add = e.target.closest('[data-add]')
        if (add) {
            const repeater = add.closest('[data-repeater]')
            const template = repeater.querySelector(':scope > template')
            const token = repeater.dataset.token || '__IDX__'
            repeater.querySelector(':scope > [data-rows]')
                .insertAdjacentHTML('beforeend', template.innerHTML.split(token).join(String(repeaterUid++)))
            renumber(repeater)
            return
        }
        const move = e.target.closest('[data-move]')
        if (move) {
            const row = move.closest('[data-row]')
            const rows = row.parentElement
            if (move.dataset.move === '-1' && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling)
            if (move.dataset.move === '1' && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row)
            renumber(rows.closest('[data-repeater]'))
            return
        }
        const remove = e.target.closest('[data-remove]')
        if (remove) {
            const repeater = remove.closest('[data-repeater]')
            remove.closest('[data-row]').remove()
            if (repeater) renumber(repeater)
            return
        }
        const uploadBtn = e.target.closest('[data-image-upload]')
        if (uploadBtn) {
            uploadBtn.closest('[data-image-field]').querySelector('[data-image-file]').click()
            return
        }
        // Remove image: clear the URL input; the dispatched input event updates
        // the preview, any summary thumbnail, and the Save/Reset dirty state.
        const removeImage = e.target.closest('[data-image-remove]')
        if (removeImage) {
            const urlInput = removeImage.closest('[data-image-field]').querySelector('[data-image-url]')
            urlInput.value = ''
            urlInput.dispatchEvent(new Event('input', { bubbles: true }))
            return
        }
        // Upload / Use link toggle: swap the active pane within the field.
        const mode = e.target.closest('[data-image-mode]')
        if (mode) {
            const field = mode.closest('[data-image-field]')
            field.querySelectorAll('[data-image-mode]').forEach((btn) => {
                const active = btn === mode
                btn.classList.toggle('bg-navy-800', active)
                btn.classList.toggle('text-white', active)
                btn.classList.toggle('bg-white', !active)
                btn.classList.toggle('text-navy-700', !active)
                btn.classList.toggle('hover:bg-slate-50', !active)
            })
            field.querySelectorAll('[data-image-pane]').forEach((pane) => {
                pane.classList.toggle('hidden', pane.dataset.imagePane !== mode.dataset.imageMode)
            })
            return
        }
        // Typography panel's Alignment/Transform segmented button groups —
        // same active-state toggle as the image Upload/Use-link pair above,
        // generalized to N sibling buttons sharing one hidden input.
        const option = e.target.closest('[data-segmented-option]')
        if (option) {
            const field = option.closest('[data-segmented-field]')
            const input = field.querySelector('[data-segmented-input]')
            const next = input.value === option.dataset.segmentedOption ? '' : option.dataset.segmentedOption
            input.value = next
            field.querySelectorAll('[data-segmented-option]').forEach((btn) => {
                const active = btn.dataset.segmentedOption === next
                btn.classList.toggle('bg-navy-800', active)
                btn.classList.toggle('text-white', active)
                btn.classList.toggle('bg-white', !active)
                btn.classList.toggle('text-navy-700', !active)
                btn.classList.toggle('hover:bg-slate-50', !active)
            })
            input.dispatchEvent(new Event('input', { bubbles: true }))
        }
    })

    // ---- image fields (thumbnail + Upload → /admin/uploads) ---------------
    // Shared by both the plain upload path and the cropper's "Use this
    // image" button below — uploads whatever Blob/File it's given and wires
    // the result into this field's URL input exactly the same way either way.
    async function uploadToField(field, fileOrBlob) {
        const btn = field.querySelector('[data-image-upload]')
        const err = field.querySelector('[data-image-error]')
        btn.disabled = true
        btn.textContent = 'Uploading…'
        err.classList.add('hidden')
        try {
            const form = new FormData()
            form.append('file', fileOrBlob, fileOrBlob.name || 'image.jpg')
            const res = await fetch('{{ route('admin.uploads') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
                body: form,
            })
            const data = await res.json()
            if (!res.ok) throw new Error(data.message || 'Upload failed')
            const urlInput = field.querySelector('[data-image-url]')
            urlInput.value = data.url
            urlInput.dispatchEvent(new Event('input', { bubbles: true }))
        } catch (ex) {
            err.textContent = ex.message
            err.classList.remove('hidden')
        } finally {
            btn.disabled = false
            btn.textContent = 'Upload'
        }
    }

    document.addEventListener('change', (e) => {
        if (!e.target.matches('[data-image-file]')) return
        const field = e.target.closest('[data-image-field]')
        const file = e.target.files[0]
        if (!file) return
        // Fields that render as a round badge (e.g. menu category images)
        // open the pan/zoom cropper first instead of uploading the raw file
        // straight away — object-fit: cover alone can't be repositioned by
        // the visitor, so an off-center subject just gets silently cropped.
        // Falls back to a plain upload if this page never rendered the
        // cropper modal (window.openCropper only gets defined below when
        // #image-cropper-modal exists), so a missing modal can't hard-error.
        if (field.dataset.imageCrop === 'circle' && typeof window.openCropper === 'function') {
            window.openCropper(field, file)
            e.target.value = ''
            return
        }
        uploadToField(field, file).then(() => { e.target.value = '' })
    })

    // ---- circular cropper (drag to reposition, slider to zoom) -----------
    // Exports a full square (not just the circular inset) at a fixed
    // resolution, matching how the badge is actually displayed elsewhere
    // (object-cover inside a rounded-full container) — the circle overlay
    // here is only a preview of what the corners lose, nothing is destroyed.
    const cropperModal = document.getElementById('image-cropper-modal')
    if (cropperModal) {
        const viewport = document.getElementById('cropper-viewport')
        const cropperImg = document.getElementById('cropper-image')
        const zoomSlider = document.getElementById('cropper-zoom')
        const VIEWPORT = 288 // px — matches the h-72 w-72 viewport box below
        const EXPORT_SIZE = 600

        let activeField = null
        let naturalW = 0
        let naturalH = 0
        let baseScale = 1
        let zoom = 1
        let panX = 0
        let panY = 0
        let dragging = false
        let dragStartX = 0
        let dragStartY = 0
        let panStartX = 0
        let panStartY = 0

        function maxPan() {
            const scale = baseScale * zoom
            return { x: Math.max(0, (naturalW * scale - VIEWPORT) / 2), y: Math.max(0, (naturalH * scale - VIEWPORT) / 2) }
        }

        function render() {
            const scale = baseScale * zoom
            const dispW = naturalW * scale
            const dispH = naturalH * scale
            const limit = maxPan()
            panX = Math.max(-limit.x, Math.min(limit.x, panX))
            panY = Math.max(-limit.y, Math.min(limit.y, panY))
            cropperImg.style.width = dispW + 'px'
            cropperImg.style.height = dispH + 'px'
            cropperImg.style.left = ((VIEWPORT - dispW) / 2 + panX) + 'px'
            cropperImg.style.top = ((VIEWPORT - dispH) / 2 + panY) + 'px'
        }

        function openCropper(field, file) {
            activeField = field
            const url = URL.createObjectURL(file)
            cropperImg.onload = () => {
                naturalW = cropperImg.naturalWidth
                naturalH = cropperImg.naturalHeight
                baseScale = Math.max(VIEWPORT / naturalW, VIEWPORT / naturalH)
                zoom = 1
                panX = 0
                panY = 0
                zoomSlider.value = '1'
                render()
                cropperModal.classList.remove('hidden')
                cropperModal.classList.add('flex')
            }
            cropperImg.src = url
        }
        // Exposed globally so the change handler above (registered earlier,
        // outside this block) can reach it — see the typeof guard there.
        window.openCropper = openCropper

        function closeCropper() {
            cropperModal.classList.add('hidden')
            cropperModal.classList.remove('flex')
            if (cropperImg.src) URL.revokeObjectURL(cropperImg.src)
            cropperImg.src = ''
            activeField = null
        }

        viewport.addEventListener('pointerdown', (e) => {
            dragging = true
            dragStartX = e.clientX
            dragStartY = e.clientY
            panStartX = panX
            panStartY = panY
            viewport.setPointerCapture(e.pointerId)
        })
        viewport.addEventListener('pointermove', (e) => {
            if (!dragging) return
            panX = panStartX + (e.clientX - dragStartX)
            panY = panStartY + (e.clientY - dragStartY)
            render()
        })
        ;['pointerup', 'pointercancel'].forEach((evt) => viewport.addEventListener(evt, () => { dragging = false }))

        zoomSlider.addEventListener('input', () => {
            zoom = parseFloat(zoomSlider.value)
            render()
        })

        document.getElementById('cropper-cancel').addEventListener('click', closeCropper)
        cropperModal.addEventListener('click', (e) => { if (e.target === cropperModal) closeCropper() })
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !cropperModal.classList.contains('hidden')) closeCropper() })

        document.getElementById('cropper-apply').addEventListener('click', () => {
            const field = activeField
            if (!field) return
            const scale = baseScale * zoom
            const sw = VIEWPORT / scale
            const sh = sw
            const imageLeft = (VIEWPORT - naturalW * scale) / 2 + panX
            const imageTop = (VIEWPORT - naturalH * scale) / 2 + panY
            const sx = -imageLeft / scale
            const sy = -imageTop / scale

            const canvas = document.createElement('canvas')
            canvas.width = EXPORT_SIZE
            canvas.height = EXPORT_SIZE
            canvas.getContext('2d').drawImage(cropperImg, sx, sy, sw, sh, 0, 0, EXPORT_SIZE, EXPORT_SIZE)
            canvas.toBlob((blob) => {
                if (blob) uploadToField(field, blob)
                closeCropper()
            }, 'image/jpeg', 0.92)
        })
    }

    document.addEventListener('input', (e) => {
        if (e.target.matches('[data-image-url]')) {
            const field = e.target.closest('[data-image-field]')
            const img = field.querySelector('[data-image-preview]')
            const empty = field.querySelector('[data-image-empty]')
            const value = e.target.value.trim()
            img.src = value || ''
            img.classList.toggle('hidden', !value)
            empty.classList.toggle('hidden', !!value)
            empty.classList.toggle('flex', !value)
            field.querySelector('[data-image-remove]')?.classList.toggle('hidden', !value)
            return
        }
        // Typography panel's Opacity slider — live "N%" readout next to the label.
        if (e.target.matches('[data-opacity-range]')) {
            e.target.closest('label').querySelector('[data-opacity-readout]').textContent = e.target.value
            return
        }
        // Typography panel's Color swatch ↔ hex text field, kept in sync both ways.
        if (e.target.matches('[data-color-swatch]')) {
            e.target.closest('[data-typography-color]').querySelector('[data-color-hex]').value = e.target.value
            return
        }
        if (e.target.matches('[data-color-hex]')) {
            const hex = e.target.value.trim()
            if (/^#[0-9a-f]{6}$/i.test(hex)) {
                e.target.closest('[data-typography-color]').querySelector('[data-color-swatch]').value = hex
            }
        }
    })
</script>
