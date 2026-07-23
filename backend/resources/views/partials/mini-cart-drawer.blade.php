{{--
    Landing page's lightweight cart drawer. Shares the exact same `bw_cart`
    localStorage the full /menu cart reads/writes, but intentionally doesn't
    replicate /menu's bundle-decomposition or voucher UI — this is a quick
    "add and preview" affordance, not a second checkout. Proceeding to
    checkout hands off to the real /checkout page the same way /menu's cart
    does (see the mini-cart script in landing.blade.php).

    Expects $user (nullable, from LandingController) to decide whether the
    footer CTA goes straight to checkout or asks the guest to sign in first,
    and $btn (defined in landing.blade.php, in scope via @include) for the
    admin checkout kill switch — same guards /menu's cart panel uses
    (partials/cart.blade.php).
--}}
<div id="mini-cart-drawer" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-black/40" onclick="document.getElementById('mini-cart-drawer').classList.add('hidden')"></div>
    <div class="absolute right-0 top-0 flex h-full w-full max-w-sm flex-col bg-white shadow-2xl">
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
            <span class="text-lg">🛒</span>
            <h2 class="text-lg font-bold text-navy-800">Your Cart</h2>
            <span class="mini-cart-count hidden ml-auto rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-600">0</span>
            <button type="button" onclick="document.getElementById('mini-cart-drawer').classList.add('hidden')" aria-label="Close cart"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-navy-800">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <ul class="mini-cart-list flex-1 space-y-3 overflow-y-auto"></ul>

        {{-- hidden by the script while the cart is empty, same as /menu's cart-footer --}}
        <div class="mini-cart-footer border-t border-slate-100 px-5 py-4">
            <div class="mini-cart-totals space-y-1.5 text-sm"></div>

            @php($checkoutState = $btn('menuCheckout'))
            @if($checkoutState !== 'on')
                {{-- Admin kill switch (Site Editor → Buttons → "Proceed to
                     checkout"): Disabled shows an inert button, Hidden drops it —
                     CheckoutController refuses new orders server-side either way.
                     Same guard as partials/cart.blade.php's checkout-btn. --}}
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
                <a href="{{ route('checkout') }}" class="mini-checkout-btn mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-center text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                    Proceed to Checkout
                </a>
            @else
                <a href="{{ route('login') }}" class="mini-checkout-btn mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-center text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                    Proceed to Checkout
                </a>
                <p class="mt-2 text-center text-xs text-slate-400">Sign in to complete your order</p>
            @endif
        </div>
    </div>
</div>
