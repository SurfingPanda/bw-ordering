{{--
    Shared product detail modal — the image + badge/name/description/calories/
    allergens/trust-line shell used by both the landing page's card grid and
    the /menu page's product grid. Previously each page hand-rolled an
    identical copy of this markup and its populate logic; now there's one
    shell and one JS module (window.ProductModal), and each page supplies
    only what's genuinely different — the price/CTA footer — via the
    `renderFooter(footerEl, data)` callback passed to open().

    Usage (in a page's own <script>):
        var pmModal = window.ProductModal.init({
            closeOnBackdrop: true,   // default true; menu.blade.php sets false
            onClose: function () {}, // optional extra cleanup on any close
        });
        pmModal.open({
            img, name, badge, desc, calorieText,
            // Free-form text, one entry per admin-entered line — rendered as
            // plain joined text, never split apart (an entry may itself be a
            // full sentence containing commas). Array, or a JSON-encoded
            // array string (e.g. from a data-* attribute).
            allergens: [...] | '["...", "..."]',
            netWeight: '250g', storageCondition: 'Refrigerate after opening',
            servingNote: 'Best served when hot', // optional
            dim: false, grayscale: false, // optional sold-out treatment
        }, function (footerEl, data) {
            footerEl.innerHTML = `...price + CTA markup...`;
            // wire up any listeners on elements inside footerEl here
        });
        pmModal.close();
--}}
<div id="product-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
    <div class="scrollbar-slim relative max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
        <button type="button" id="pm-close" aria-label="Close" class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-navy-800 shadow transition hover:bg-white">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="6" y1="6" x2="18" y2="18" />
                <line x1="6" y1="18" x2="18" y2="6" />
            </svg>
        </button>
        <div class="grid md:grid-cols-2">
            <span id="pm-img-container" class="relative block h-64 w-full overflow-hidden bg-slate-100 md:h-full md:min-h-[28rem]">
                {{-- object-cover (not -contain): contain avoided cropping
                     portrait photos but left visible top/bottom letterbox
                     gaps on bg-slate-100 for other aspect ratios — a filled
                     box reads cleaner than a gap, even if it means cropping
                     some images' edges. --}}
                <img id="pm-img" src="" alt="" class="absolute inset-0 h-full w-full object-cover">
                <span id="pm-img-fallback" class="absolute inset-0 hidden items-center justify-center text-xs font-medium text-slate-400">no image</span>
            </span>
            <div class="flex flex-col p-8 sm:p-10">
                <span id="pm-tag" class="hidden w-fit rounded-full bg-orange-50 px-3.5 py-1.5 text-xs font-bold uppercase tracking-wide text-brand-600"></span>
                <div class="mt-4 flex items-start gap-1">
                    <h3 id="pm-name" class="min-w-0 text-3xl font-extrabold text-navy-900"></h3>
                    <span id="pm-serving-note" class="hidden mt-1 shrink-0 whitespace-nowrap text-[10px] font-semibold text-red-500"></span>
                </div>
                <p id="pm-desc" class="hidden mt-4 text-base leading-relaxed text-slate-500"></p>
                <span id="pm-calories" class="hidden mt-5 w-fit items-center gap-1.5 rounded-full bg-navy-50 px-3.5 py-1.5 text-sm font-semibold text-navy-700"></span>
                <div id="pm-allergens-wrap" class="hidden mt-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Allergens</p>
                    <p id="pm-allergens" class="mt-1.5 text-sm leading-relaxed text-slate-600"></p>
                </div>
                <div id="pm-meta-wrap" class="hidden mt-6 grid grid-cols-2 gap-4">
                    <div id="pm-netweight-wrap" class="hidden">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Net Weight</p>
                        <p id="pm-netweight" class="mt-1.5 text-sm font-semibold text-navy-700"></p>
                    </div>
                    <div id="pm-storage-wrap" class="hidden">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Storage Condition</p>
                        <p id="pm-storage" class="mt-1.5 text-sm font-semibold text-navy-700"></p>
                    </div>
                </div>
                <div class="mt-8 border-t border-slate-100 pt-6">
                    <div id="pm-footer"></div>
                    <p class="mt-4 flex items-start gap-2 text-xs leading-relaxed text-slate-500">
                        <span aria-hidden="true" class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-50 text-[11px]">✅</span>
                        Made fresh daily with quality ingredients. Satisfaction guaranteed.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.ProductModal = (function () {
    function init(opts) {
        opts = opts || {};
        var closeOnBackdrop = opts.closeOnBackdrop !== false;
        var onClose = opts.onClose || function () {};
        var modal = document.getElementById('product-modal');

        function close() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            onClose();
        }

        document.getElementById('pm-close').addEventListener('click', close);
        // e.target === modal only when the click lands on the backdrop itself
        // (any click inside the card hits a descendant), so no separate
        // stopPropagation wiring is needed on the card.
        modal.addEventListener('click', function (e) {
            if (closeOnBackdrop && e.target === modal) close();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
        });

        function open(d, renderFooter) {
            var imgEl = document.getElementById('pm-img');
            var imgFallback = document.getElementById('pm-img-fallback');
            var imgContainer = document.getElementById('pm-img-container');
            if (d.img) {
                imgEl.src = d.img;
            } else {
                imgEl.removeAttribute('src');
            }
            // Products without an image carry a default picture already; if
            // it (or any product image) fails to load, fall back to the
            // "no image" tile.
            imgEl.onerror = function () {
                imgEl.classList.add('hidden');
                imgFallback.classList.remove('hidden');
                imgFallback.classList.add('flex');
            };
            imgEl.alt = d.name || '';
            imgEl.classList.toggle('hidden', !d.img);
            imgEl.classList.toggle('grayscale', !!d.grayscale);
            imgContainer.classList.toggle('opacity-60', !!d.dim);
            imgFallback.classList.toggle('hidden', !!d.img);
            imgFallback.classList.toggle('flex', !d.img);

            var tagEl = document.getElementById('pm-tag');
            tagEl.textContent = d.badge || '';
            tagEl.classList.toggle('hidden', !d.badge);

            document.getElementById('pm-name').textContent = d.name || '';

            var servingNoteEl = document.getElementById('pm-serving-note');
            servingNoteEl.textContent = d.servingNote || '';
            servingNoteEl.classList.toggle('hidden', !d.servingNote);

            var descEl = document.getElementById('pm-desc');
            descEl.textContent = d.desc || '';
            descEl.classList.toggle('hidden', !d.desc);

            var calEl = document.getElementById('pm-calories');
            calEl.textContent = '';
            if (d.calorieText) {
                var flame = document.createElement('span');
                flame.setAttribute('aria-hidden', 'true');
                flame.textContent = '🔥';
                calEl.appendChild(flame);
                calEl.appendChild(document.createTextNode(' ' + d.calorieText));
                calEl.classList.remove('hidden');
                calEl.classList.add('inline-flex');
            } else {
                calEl.classList.add('hidden');
                calEl.classList.remove('inline-flex');
            }

            // Each entry is free-form text (an editor may write a full
            // sentence, not just a single keyword), so entries are only ever
            // joined for display, never split apart again — splitting on
            // commas used to shred a sentence like "May contain glucose,
            // sulfirite" into two fragments.
            var allergensWrap = document.getElementById('pm-allergens-wrap');
            var allergensEl = document.getElementById('pm-allergens');
            var allergensRaw = d.allergens;
            if (typeof allergensRaw === 'string') {
                try { allergensRaw = JSON.parse(allergensRaw); } catch (e) { allergensRaw = allergensRaw ? [allergensRaw] : []; }
            }
            var allergens = (Array.isArray(allergensRaw) ? allergensRaw : [allergensRaw])
                .map(function (a) { return String(a || '').trim(); })
                .filter(Boolean);
            allergensEl.textContent = allergens.join(' ');
            allergensWrap.classList.toggle('hidden', !allergens.length);

            var netWeightWrap = document.getElementById('pm-netweight-wrap');
            document.getElementById('pm-netweight').textContent = d.netWeight || '';
            netWeightWrap.classList.toggle('hidden', !d.netWeight);

            var storageWrap = document.getElementById('pm-storage-wrap');
            document.getElementById('pm-storage').textContent = d.storageCondition || '';
            storageWrap.classList.toggle('hidden', !d.storageCondition);

            document.getElementById('pm-meta-wrap').classList.toggle('hidden', !d.netWeight && !d.storageCondition);

            var footer = document.getElementById('pm-footer');
            footer.innerHTML = '';
            if (renderFooter) renderFooter(footer, d);

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        return { open: open, close: close };
    }

    return { init: init };
})();
</script>
