{{-- Shared Site Editor form behaviors: repeater rows (add / move / remove /
     renumber) and image fields (thumbnail preview + Upload → /admin/uploads).
     Included by the content editor and the products editor. --}}
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
        }
    })

    // ---- image fields (thumbnail + Upload → /admin/uploads) ---------------
    document.addEventListener('change', async (e) => {
        if (!e.target.matches('[data-image-file]')) return
        const field = e.target.closest('[data-image-field]')
        const file = e.target.files[0]
        if (!file) return
        const btn = field.querySelector('[data-image-upload]')
        const err = field.querySelector('[data-image-error]')
        btn.disabled = true
        btn.textContent = 'Uploading…'
        err.classList.add('hidden')
        try {
            const form = new FormData()
            form.append('file', file)
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
            e.target.value = ''
        }
    })

    document.addEventListener('input', (e) => {
        if (!e.target.matches('[data-image-url]')) return
        const field = e.target.closest('[data-image-field]')
        const img = field.querySelector('[data-image-preview]')
        const empty = field.querySelector('[data-image-empty]')
        const value = e.target.value.trim()
        img.src = value || ''
        img.classList.toggle('hidden', !value)
        empty.classList.toggle('hidden', !!value)
        empty.classList.toggle('flex', !value)
        field.querySelector('[data-image-remove]')?.classList.toggle('hidden', !value)
    })
</script>
