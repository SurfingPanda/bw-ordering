{{--
    Shared client-side order pricing — the peso formatter, fee constants, and
    subtotal/discount/delivery/VAT/total formula, previously hand-duplicated
    (and drifting: checkout.blade.php was missing the "add more for free
    delivery" hint that menu.blade.php's cart drawer has) between menu.blade.php
    (the /menu page's cart drawer preview) and checkout.blade.php (the
    authoritative computation shown at checkout). Server-side pricing is the
    real source of truth (see OrderCreationService) — this is display-only,
    used purely to preview totals before submit.

    The fee amounts, the free-delivery threshold, and the VAT rate/on-off are
    the Site Editor's "Fees & Tax" settings (site_content → `pricing`), resolved
    by SiteContent::pricingConfig(). This partial reads them from the parent
    view's $content when present (so a Site Editor live-preview draft is
    reflected) and otherwise loads the saved row — the same values
    OrderCreationService uses server-side, so the preview and the real charge
    always agree.

    Usage (in a page's own <script>):
        const { peso, computeTotals, renderTotalsHTML, DELIVERY_FEE } = window.OrderPricing;
        const t = computeTotals({ subtotal, voucher, deliveryMode, deliverySpeed });
        el.innerHTML = renderTotalsHTML(t, { deliveryLabel, showFreeDeliveryHint });

    `voucher` is `null` or `{ type: 'percent'|'amount'|'freedel', value }`.
    `deliveryMode`/`deliverySpeed` default to 'delivery'/'standard' — callers
    that don't yet know the final fulfillment choice (like the /menu cart
    preview, which always previews standard delivery pricing) can omit them.
--}}
@php($bwPricing = $pricing ?? \App\Models\SiteContent::pricingConfig($content ?? null))
<script>
window.OrderPricing = (function () {
    var CFG = @json($bwPricing);

    var VAT_ENABLED = CFG.vatEnabled !== false;
    var VAT_PCT = Number(CFG.vatRate) || 0;      // percent, e.g. 12
    var VAT_RATE = VAT_PCT / 100;                // fraction — kept for callers that destructure it
    var DELIVERY_ENABLED = CFG.deliveryEnabled !== false;
    var DELIVERY_FEE = Number(CFG.deliveryFee) || 0;
    var EXPRESS_DELIVERY_FEE = Number(CFG.expressFee) || 0;
    var FREE_DELIVERY_MIN = Number(CFG.freeDeliveryMin) || 0;

    var peso = function (n) {
        return '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    function voucherDiscount(subtotal, voucher) {
        if (!voucher) return 0;
        if (voucher.type === 'percent') return (subtotal * voucher.value) / 100;
        if (voucher.type === 'amount') return Math.min(voucher.value, subtotal);
        return 0; // 'freedel' has no direct discount — it zeroes the delivery fee instead
    }

    function computeTotals(opts) {
        opts = opts || {};
        var subtotal = Number(opts.subtotal) || 0;
        var voucher = opts.voucher || null;
        var mode = opts.deliveryMode || 'delivery';
        var speed = opts.deliverySpeed || 'standard';

        var discount = voucherDiscount(subtotal, voucher);
        var discounted = subtotal - discount;
        // Delivery turned off in the editor ⇒ every delivery order ships free.
        var freeDelivery = !DELIVERY_ENABLED
            || (FREE_DELIVERY_MIN > 0 && subtotal >= FREE_DELIVERY_MIN)
            || !!(voucher && voucher.type === 'freedel');
        var delivery = 0;
        if (mode === 'delivery' && DELIVERY_ENABLED) {
            delivery = speed === 'express' ? EXPRESS_DELIVERY_FEE : (freeDelivery ? 0 : DELIVERY_FEE);
        }
        var vat = VAT_ENABLED ? discounted * VAT_RATE : 0;
        var total = discounted + vat + delivery;

        return { subtotal: subtotal, discount: discount, delivery: delivery, vat: vat, total: total, freeDelivery: freeDelivery };
    }

    function renderTotalsHTML(t, opts) {
        opts = opts || {};
        var deliveryLabel = opts.deliveryLabel || 'Delivery';
        var hint = opts.showFreeDeliveryHint && !t.freeDelivery && FREE_DELIVERY_MIN > 0
            ? '<p class="pt-1 text-xs text-brand-600">Add ' + peso(FREE_DELIVERY_MIN - t.subtotal) + ' more for free delivery 🚚</p>'
            : '';
        // VAT rate label: drop a trailing ".00" so "12%" not "12.00%".
        var vatLabel = 'VAT (' + String(VAT_PCT.toFixed(2)).replace(/\.?0+$/, '') + '%)';
        var vatRow = VAT_ENABLED
            ? `<div class="flex justify-between text-slate-600"><span>${vatLabel}</span><span>${peso(t.vat)}</span></div>`
            : '';
        return `
            <div class="flex justify-between text-slate-600"><span>Subtotal</span><span class="font-semibold text-navy-800">${peso(t.subtotal)}</span></div>
            ${t.discount > 0 ? `<div class="flex justify-between text-green-600"><span>Discount</span><span class="font-semibold">−${peso(t.discount)}</span></div>` : ''}
            <div class="flex justify-between text-slate-600"><span>${deliveryLabel}</span><span class="${t.delivery === 0 ? 'font-semibold text-green-600' : 'text-slate-500'}">${t.delivery === 0 ? 'FREE' : peso(t.delivery)}</span></div>
            ${vatRow}
            <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-navy-800"><span>Total</span><span>${peso(t.total)}</span></div>
            ${hint}
        `;
    }

    return {
        VAT_ENABLED: VAT_ENABLED,
        VAT_RATE: VAT_RATE,
        DELIVERY_ENABLED: DELIVERY_ENABLED,
        DELIVERY_FEE: DELIVERY_FEE,
        EXPRESS_DELIVERY_FEE: EXPRESS_DELIVERY_FEE,
        FREE_DELIVERY_MIN: FREE_DELIVERY_MIN,
        peso: peso,
        computeTotals: computeTotals,
        renderTotalsHTML: renderTotalsHTML,
    };
})();
</script>
