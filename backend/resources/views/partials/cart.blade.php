{{-- Shared cart panel markup, rendered twice (desktop sidebar + mobile
     drawer) by menu.blade.php. All content is filled/updated by the vanilla
     JS in menu.blade.php via the `.cart-list` / `.cart-totals` / `.voucher-box`
     hooks below — this partial only provides the static shell. --}}
<div class="flex h-full flex-col border-t border-slate-200 lg:border-l lg:border-t-0">
    <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
        <span class="text-lg">🛒</span>
        <h2 class="text-lg font-bold text-navy-800">Your Cart</h2>
        <span class="cart-count hidden ml-auto rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-600">0</span>
    </div>

    <ul class="cart-list flex-1 space-y-3 overflow-y-auto"></ul>

    {{-- .cart-footer is hidden by renderCart() while the cart is empty, so an
         empty cart shows no delivery fee / total / checkout button. --}}
    <div class="cart-footer border-t border-slate-100 px-5 py-4">
        <div class="voucher-box mb-4"></div>

        <div class="cart-totals space-y-1.5 text-sm"></div>

        @if($user && ! empty($isStaffAccount))
            {{-- staff browse but don't buy — CheckoutController blocks them anyway --}}
            <p class="mt-4 rounded-xl bg-slate-100 px-4 py-3 text-center text-xs font-medium text-slate-500">
                Staff accounts can't place orders — use a customer account to check out.
            </p>
        @elseif(($checkoutState ?? 'on') !== 'on')
            {{-- Admin kill switch (Site Editor → Buttons → "Proceed to
                 checkout"): Disabled shows an inert button, Hidden drops it —
                 CheckoutController refuses new orders server-side either way. --}}
            @if($checkoutState === 'disabled')
                <button type="button" disabled
                    class="mt-4 inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-full bg-slate-300 py-3 text-center text-sm font-semibold text-white">
                    Proceed to Checkout
                </button>
            @endif
            <p class="{{ $checkoutState === 'disabled' ? 'mt-2' : 'mt-4' }} rounded-xl bg-slate-100 px-4 py-3 text-center text-xs font-medium text-slate-500">
                Checkout is temporarily unavailable. Please check back soon.
            </p>
        @elseif($user)
            <button type="button" class="checkout-btn mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-center text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                Proceed to Checkout
            </button>
        @else
            <a href="{{ route('login') }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-center text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                Proceed to Checkout
            </a>
            <p class="mt-2 text-center text-xs text-slate-400">Sign in to complete your order</p>
        @endif
    </div>
</div>
