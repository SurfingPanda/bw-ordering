{{--
    Shared client-side order pricing — the peso formatter, fee constants, and
    subtotal/discount/delivery/VAT/total formula, previously hand-duplicated
    (and drifting: checkout.blade.php was missing the "add more for free
    delivery" hint that menu.blade.php's cart drawer has) between menu.blade.php
    (the /menu page's cart drawer preview) and checkout.blade.php (the
    authoritative computation shown at checkout). Server-side pricing is the
    real source of truth (see OrderCreationService) — this is display-only,
    used purely to preview totals before submit.

    Usage (in a page's own <script>):
        const { peso, computeTotals, renderTotalsHTML, DELIVERY_FEE } = window.OrderPricing;
        const t = computeTotals({ subtotal, voucher, deliveryMode, deliverySpeed });
        el.innerHTML = renderTotalsHTML(t, { deliveryLabel, showFreeDeliveryHint });

    `voucher` is `null` or `{ type: 'percent'|'amount'|'freedel', value }`.
    `deliveryMode`/`deliverySpeed` default to 'delivery'/'standard' — callers
    that don't yet know the final fulfillment choice (like the /menu cart
    preview, which always previews standard delivery pricing) can omit them.
--}}
<script>
window.OrderPricing = (function () {
    var VAT_RATE = 0.12;
    var DELIVERY_FEE = 79;
    var EXPRESS_DELIVERY_FEE = 149;
    var FREE_DELIVERY_MIN = 1000;

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
        var freeDelivery = subtotal >= FREE_DELIVERY_MIN || !!(voucher && voucher.type === 'freedel');
        var delivery = 0;
        if (mode === 'delivery') delivery = speed === 'express' ? EXPRESS_DELIVERY_FEE : (freeDelivery ? 0 : DELIVERY_FEE);
        var vat = discounted * VAT_RATE;
        var total = discounted + vat + delivery;

        return { subtotal: subtotal, discount: discount, delivery: delivery, vat: vat, total: total, freeDelivery: freeDelivery };
    }

    function renderTotalsHTML(t, opts) {
        opts = opts || {};
        var deliveryLabel = opts.deliveryLabel || 'Delivery';
        var hint = opts.showFreeDeliveryHint && !t.freeDelivery
            ? '<p class="pt-1 text-xs text-brand-600">Add ' + peso(FREE_DELIVERY_MIN - t.subtotal) + ' more for free delivery 🚚</p>'
            : '';
        return `
            <div class="flex justify-between text-slate-600"><span>Subtotal</span><span class="font-semibold text-navy-800">${peso(t.subtotal)}</span></div>
            ${t.discount > 0 ? `<div class="flex justify-between text-green-600"><span>Discount</span><span class="font-semibold">−${peso(t.discount)}</span></div>` : ''}
            <div class="flex justify-between text-slate-600"><span>${deliveryLabel}</span><span class="${t.delivery === 0 ? 'font-semibold text-green-600' : 'text-slate-500'}">${t.delivery === 0 ? 'FREE' : peso(t.delivery)}</span></div>
            <div class="flex justify-between text-slate-600"><span>VAT (12%)</span><span>${peso(t.vat)}</span></div>
            <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-navy-800"><span>Total</span><span>${peso(t.total)}</span></div>
            ${hint}
        `;
    }

    return {
        VAT_RATE: VAT_RATE,
        DELIVERY_FEE: DELIVERY_FEE,
        EXPRESS_DELIVERY_FEE: EXPRESS_DELIVERY_FEE,
        FREE_DELIVERY_MIN: FREE_DELIVERY_MIN,
        peso: peso,
        computeTotals: computeTotals,
        renderTotalsHTML: renderTotalsHTML,
    };
})();
</script>
