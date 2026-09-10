{{-- Public shop-assistant widget ("Moymoy", the bw Superbakeshop chef-boy
     mascot — avatar cropped from /images/Full Moymoy 2.png into
     /images/moymoy-head.png). Mounted on every marketing page
     (landing, menu, stores, franchise, about, custom-cake). Talks to
     AssistantController@chat (POST /assistant/chat), which is Groq-backed with a
     rule-based fast path and a canned fallback.

     Rendered only when GROQ_API_KEY is set (config/services.php) and never
     inside the Site Editor's live-preview iframe ($editable) — that iframe
     intercepts every click (partials/_editor-bridge), so an interactive widget
     there would be dead weight and could confuse the preview.

     Vanilla JS + sessionStorage transcript, same house style as menu.blade.php's
     cart — no bundler entry. The FAB sits bottom-LEFT on purpose: the cart FAB
     already owns bottom-right on landing/menu.

     This is a presentation-only layer: the request/response contract, the Groq
     and rule-based logic, the @@PRODUCTS handling, and the cart shape it writes
     (`bw_cart` = { productId: qty }) are all unchanged. --}}
@php
    // Rendered when: GROQ_API_KEY is set, not inside the Site Editor preview
    // iframe, and the editor hasn't switched the launcher off (Site Editor →
    // Buttons → "Moymoy AI Assistant"; absent key ⇒ on by default).
    $bwAssistantOn = config('services.groq.key') && ! ($editable ?? false);
    if ($bwAssistantOn) {
        $bwSiteContent = (array) app(\App\Http\Controllers\SiteContentController::class)->cachedData();
        $bwAssistantOn = (bool) ($bwSiteContent['assistant']['enabled'] ?? true);
    }
@endphp
@if($bwAssistantOn)
<style>
    @keyframes bw-a-dot { 0%, 80%, 100% { opacity: .25; transform: translateY(0); } 40% { opacity: 1; transform: translateY(-2px); } }
    @keyframes bw-a-in { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    .bw-a-dot { animation: bw-a-dot 1.2s infinite ease-in-out both; }
    .bw-a-dot:nth-child(2) { animation-delay: .15s; }
    .bw-a-dot:nth-child(3) { animation-delay: .3s; }
    .bw-a-in { animation: bw-a-in .2s ease-out both; }
    /* Soft, borderless card elevation. */
    .bw-a-card { box-shadow: 0 1px 2px rgba(16,24,40,.06), 0 6px 16px rgba(16,24,40,.05); }
    .bw-a-card:hover { box-shadow: 0 10px 26px rgba(16,24,40,.10); }
    /* Added / selected product tint — not border-only (see brief). */
    .bw-a-added { background-color: rgba(239,125,26,.07); box-shadow: inset 0 0 0 1.5px rgba(239,125,26,.45); }
    /* Subtle, neutral scrollbar (no strong blue). */
    #bw-a-log { scrollbar-width: thin; scrollbar-color: rgba(15,23,42,.14) transparent; }
    #bw-a-log::-webkit-scrollbar { width: 5px; }
    #bw-a-log::-webkit-scrollbar-thumb { background: rgba(15,23,42,.14); border-radius: 9999px; }
    #bw-a-log::-webkit-scrollbar-thumb:hover { background: rgba(15,23,42,.26); }
    #bw-a-chips::-webkit-scrollbar { display: none; }
    #bw-a-chips { -ms-overflow-style: none; scrollbar-width: none; }
    #bw-a-panel[hidden] { display: none; }
    #bw-a-launcher[hidden] { display: none; }
    #bw-a-invite[hidden] { display: none; }

    /* ---- Launcher: white pill that collapses to a circular avatar --------
       Quieter than the orange cart FAB on the opposite corner — white
       surface, avatar-led identity, orange only on the focus ring. */
    #bw-a-launcher { animation: bw-a-launcher-in .45s ease-out both; }
    @keyframes bw-a-launcher-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

    #bw-a-toggle {
        padding: .3125rem;                 /* 56px avatar + 10px ⇒ ~66px circle when collapsed */
        border: 1px solid rgba(16,24,40,.10);
        background: #fff;
        box-shadow: 0 4px 16px rgba(16,24,40,.14);
        cursor: pointer;
        transition: padding-right .3s ease, transform .2s ease, box-shadow .3s ease;
    }
    #bw-a-toggle:hover  { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(16,24,40,.18); }
    #bw-a-toggle:active { transform: translateY(0) scale(.98); }
    #bw-a-toggle:focus-visible { outline: 2px solid rgb(239 125 26); outline-offset: 2px; }

    #bw-a-toggle .bw-a-label {
        display: block;
        max-width: 0; margin-left: 0; opacity: 0;
        overflow: hidden; white-space: nowrap;
        transition: max-width .3s ease, margin-left .3s ease, opacity .25s ease;
    }
    /* Expanded — first-visit intro phase (JS) or desktop hover/focus. */
    #bw-a-launcher.is-expanded #bw-a-toggle { padding-right: .875rem; }
    #bw-a-launcher.is-expanded #bw-a-toggle .bw-a-label { max-width: 12rem; margin-left: .5rem; opacity: 1; }
    @media (hover: hover) and (min-width: 640px) {
        #bw-a-toggle:hover, #bw-a-toggle:focus-visible { padding-right: .875rem; }
        #bw-a-toggle:hover .bw-a-label, #bw-a-toggle:focus-visible .bw-a-label { max-width: 12rem; margin-left: .5rem; opacity: 1; }
    }

    /* One-shot attention: a single soft pulse of the avatar, never looping. */
    @keyframes bw-a-pulse-once { 0%, 100% { transform: scale(1); } 45% { transform: scale(1.07); } }
    .bw-a-pulse-once { animation: bw-a-pulse-once .7s ease-in-out 1; }

    /* Proactive greeting bubble, tethered above the avatar. */
    #bw-a-invite.bw-a-anim-in { animation: bw-a-invite-in .3s ease-out both; }
    @keyframes bw-a-invite-in { from { opacity: 0; transform: translateY(8px) scale(.97); } to { opacity: 1; transform: none; } }

    @media (prefers-reduced-motion: reduce) {
        .bw-a-in, .bw-a-dot, .bw-a-pulse-once,
        #bw-a-launcher, #bw-a-invite.bw-a-anim-in { animation: none; }
        #bw-a-toggle, #bw-a-toggle .bw-a-label { transition: none; }
    }
</style>

<div id="bw-assistant">
    {{-- Launcher — bottom-LEFT (the cart FAB owns bottom-right). A white pill
         "Chat with Moymoy AI" that collapses to a circular avatar; a proactive
         greeting bubble introduces it once per visitor. `safe-area-inset-*`
         keeps clear of notches / mobile browser chrome; z-40 sits under the
         open panel (z-55). --}}
    <div id="bw-a-launcher" class="fixed z-40 flex flex-col items-start gap-2"
         style="left: calc(1.25rem + env(safe-area-inset-left)); bottom: calc(1.25rem + env(safe-area-inset-bottom));">

        {{-- Proactive greeting — hidden until JS reveals it for a first-time visitor --}}
        <div id="bw-a-invite" hidden
             class="relative ml-1 w-[15rem] max-w-[calc(100vw-2.5rem)] rounded-2xl rounded-bl-md bg-white px-3.5 py-2.5 pr-8 text-navy-800 shadow-[0_8px_24px_rgba(16,24,40,.16)] ring-1 ring-navy-900/5">
            <p class="text-[13px] font-semibold leading-tight">Hi, I'm Moymoy!</p>
            <p class="mt-0.5 text-[12.5px] leading-snug text-navy-800/65">Need help finding a cake?</p>
            <span class="pointer-events-none absolute -bottom-1.5 left-6 h-3 w-3 rotate-45 bg-white" aria-hidden="true"></span>
            <button type="button" id="bw-a-invite-close" aria-label="Dismiss Moymoy greeting"
                    class="absolute right-1.5 top-1.5 grid h-5 w-5 place-items-center rounded-full text-navy-900/40 transition hover:bg-navy-900/5 hover:text-navy-900/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <button type="button" id="bw-a-toggle" aria-expanded="false" aria-controls="bw-a-panel" aria-haspopup="dialog"
                aria-label="Open Moymoy AI assistant"
                class="flex items-center rounded-full">
            <span id="bw-a-avatar" class="relative block h-14 w-14 shrink-0">
                <img src="/images/moymoy-head.png" alt="" class="h-full w-full rounded-full object-cover ring-1 ring-navy-900/5">
                {{-- availability dot: small, offset into a corner, white-bordered so it reads on the photo --}}
                <span class="absolute bottom-0.5 right-0.5 h-3 w-3 rounded-full border-2 border-white bg-emerald-400" aria-hidden="true"></span>
            </span>
            <span class="bw-a-label text-[13px] font-semibold text-navy-900">Chat with Moymoy AI</span>
        </button>
    </div>

    {{-- Panel: near-fullscreen on phones, a pinned card from sm up --}}
    <div id="bw-a-panel" hidden role="dialog" aria-modal="false" aria-label="Moymoy — your cake shopping assistant"
         class="fixed inset-x-3 bottom-3 z-[55] flex h-[calc(100dvh-1.5rem)] max-h-[36rem] flex-col overflow-hidden rounded-[20px] bg-white shadow-2xl shadow-navy-900/25 ring-1 ring-navy-900/5 sm:inset-x-auto sm:left-5 sm:bottom-5 sm:h-[34rem] sm:w-[24rem]">

        {{-- Header --}}
        <div class="flex items-center gap-3 bg-navy-900 px-4 py-3.5 text-white">
            <span class="relative shrink-0">
                <img src="/images/moymoy-head.png" alt="" class="h-11 w-11 rounded-full bg-white/10 object-cover ring-1 ring-white/15">
                <span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-navy-900 bg-emerald-400" aria-hidden="true"></span>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-[15px] font-bold leading-tight">Moymoy</p>
                <p class="truncate text-[11px] leading-tight text-white/55">Your cake shopping assistant</p>
            </div>
            <button type="button" id="bw-a-close" aria-label="Close chat"
                    class="-mr-1 shrink-0 rounded-lg p-1.5 text-white/70 transition hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        {{-- Conversation --}}
        <div id="bw-a-log" class="min-w-0 flex-1 space-y-4 overflow-y-auto overflow-x-hidden bg-[#faf6f1] px-4 py-4 text-sm" aria-live="polite"></div>

        {{-- Quick actions (compact) — only once a conversation is underway.
             Visibility via inline style so Tailwind's `flex` never fights a
             `hidden` class/attribute. --}}
        <div id="bw-a-chips" style="display:none" class="shrink-0 gap-2 overflow-x-auto border-t border-navy-900/5 bg-white px-4 py-2.5"></div>

        {{-- Input --}}
        <form id="bw-a-form" class="flex shrink-0 items-center bg-white px-3 pb-2 pt-2.5">
            <label for="bw-a-input" class="sr-only">Message Moymoy</label>
            <div class="flex flex-1 items-center gap-1.5 rounded-full border border-navy-900/12 bg-white pl-4 pr-1.5 transition focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/15">
                <input id="bw-a-input" type="text" autocomplete="off" maxlength="2000"
                       placeholder="Ask Moymoy about cakes, branches, or orders…"
                       class="min-w-0 flex-1 bg-transparent py-2.5 text-sm text-navy-900 outline-none placeholder:text-navy-900/35">
                <button type="submit" id="bw-a-send" aria-label="Send message"
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-500 text-white transition hover:bg-brand-600 active:scale-95 disabled:opacity-40">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                </button>
            </div>
        </form>
        <p class="bg-white px-4 pb-3 pt-1 text-center text-[11px] leading-tight text-navy-900/40">
            Prices and availability may change. Please confirm before checkout.
        </p>
    </div>
</div>

<script>
    (() => {
        const CSRF = @json(csrf_token());
        const ENDPOINT = @json(route('assistant.chat'));
        const STORE_KEY = 'bw_assistant_chat';
        const CART_KEY = 'bw_cart';
        // Bumped when the stored transcript shape changes. v2 = product cards
        // carry an `id` so "＋ Add" writes to the cart in place; older saved
        // transcripts (id-less cards) are dropped so no card can fall back to
        // a page navigation.
        const STORE_VERSION = 2;

        const GREETING = "Hi, I'm Moymoy. What can I help you find today?";
        // Quick actions on the empty welcome screen. `icon` is an inline-SVG path
        // set (lucide-style, same stroke language as the header/send icons) so
        // the three read as one consistent icon family, not mixed emoji.
        const WELCOME_ACTIONS = [
            { label: 'Browse Cakes', msg: 'Show me your cakes',
              icon: '<path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8"/><path d="M4 16s.5-1 2-1 2.5 2 4 2 2.5-2 4-2 2.5 2 4 2 2-1 2-1"/><path d="M2 21h20"/><path d="M7 8v2"/><path d="M12 8v2"/><path d="M17 8v2"/>' },
            { label: 'Find a Branch', msg: 'Where are your branches?',
              icon: '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>' },
            { label: 'Best Sellers', msg: 'What are your best sellers?',
              icon: '<path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.85a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 10.633a.53.53 0 0 1 .294-.904l5.166-.755a2.12 2.12 0 0 0 1.596-1.16z"/>' },
        ];
        // Compact chip row shown once the conversation has started.
        const CHIPS = [
            { label: 'Best Sellers', msg: 'What are your best sellers?' },
            { label: 'Cake Menu', msg: 'Show me your cakes' },
            { label: 'Find a Branch', msg: 'Where are your branches?' },
            { label: 'Custom Cakes', msg: 'I want to order a custom cake' },
        ];

        const toggle = document.getElementById('bw-a-toggle');
        const launcher = document.getElementById('bw-a-launcher');
        const invite = document.getElementById('bw-a-invite');
        const inviteClose = document.getElementById('bw-a-invite-close');
        const avatar = document.getElementById('bw-a-avatar');
        const panel = document.getElementById('bw-a-panel');
        const closeBtn = document.getElementById('bw-a-close');
        const log = document.getElementById('bw-a-log');
        const chipBar = document.getElementById('bw-a-chips');
        const form = document.getElementById('bw-a-form');
        const input = document.getElementById('bw-a-input');
        const sendBtn = document.getElementById('bw-a-send');

        // messages = [{ role, content, products? }] — products ride on the
        // assistant turn they arrived with. Only { role, content } is sent back.
        let messages = [];
        let conversationId = null;
        let pending = false;
        let open = false;
        // Product ids the shopper tapped "＋ Add" on *in this chat*. A card shows
        // "✓ Added" only for these — not for whatever else is already in the
        // cart — so a fresh recommendation always reads as a fresh "＋ Add".
        let addedIds = new Set();

        try {
            const saved = JSON.parse(sessionStorage.getItem(STORE_KEY) || 'null');
            if (saved && saved.v === STORE_VERSION && Array.isArray(saved.messages)) {
                messages = saved.messages;
                conversationId = saved.conversationId || null;
                if (Array.isArray(saved.added)) addedIds = new Set(saved.added.map(String));
            } else if (saved) {
                sessionStorage.removeItem(STORE_KEY); // stale shape — start clean
            }
        } catch {}

        function persist() {
            try {
                sessionStorage.setItem(STORE_KEY, JSON.stringify({
                    v: STORE_VERSION, conversationId, messages: messages.slice(-20),
                    added: [...addedIds],
                }));
            } catch {}
        }

        // ---- cart helpers (same bw_cart map /menu uses) --------------------
        function readCart() {
            try { return JSON.parse(localStorage.getItem(CART_KEY) || '{}') || {}; } catch { return {}; }
        }
        function addToCart(id) {
            if (id == null) return false;
            const cart = readCart();
            cart[id] = (cart[id] || 0) + 1;
            try { localStorage.setItem(CART_KEY, JSON.stringify(cart)); } catch { return false; }
            // The host page (/, /menu) keeps its own in-memory copy of bw_cart
            // read once at load — tell it to re-sync so the badge / cart drawer
            // reflect this add without a reload.
            window.dispatchEvent(new CustomEvent('bw-cart-changed', { detail: { id, source: 'assistant' } }));
            return true;
        }

        // ---- text with links (DOM nodes, never innerHTML) ------------------
        const LINK_RE = /(https?:\/\/[^\s)]+|\/(menu|stores|franchise|about|contact|custom-cake)(?:\?[^\s)]*)?)/g;
        function textWithLinks(str) {
            const frag = document.createDocumentFragment();
            let last = 0, m;
            LINK_RE.lastIndex = 0;
            while ((m = LINK_RE.exec(str)) !== null) {
                if (m.index > last) frag.appendChild(document.createTextNode(str.slice(last, m.index)));
                const a = document.createElement('a');
                a.href = m[0];
                a.textContent = m[0];
                a.className = 'font-medium text-brand-600 underline underline-offset-2 break-all';
                if (m[0].startsWith('http')) { a.target = '_blank'; a.rel = 'noopener'; }
                frag.appendChild(a);
                last = m.index + m[0].length;
            }
            if (last < str.length) frag.appendChild(document.createTextNode(str.slice(last)));
            return frag;
        }

        // ---- building blocks ---------------------------------------------
        function bubble(role, content) {
            const b = document.createElement('div');
            // `[overflow-wrap:anywhere]` so a long unbroken token the model can
            // emit — a street address run, a bare URL, a "tel:(045)…" string —
            // wraps inside the bubble instead of spilling past max-w and
            // dragging a phantom horizontal scrollbar onto the log.
            b.className = role === 'user'
                ? 'ml-auto max-w-[86%] whitespace-pre-wrap [overflow-wrap:anywhere] rounded-2xl rounded-br-md bg-brand-500 px-3.5 py-2.5 text-white'
                : 'mr-auto max-w-[86%] whitespace-pre-wrap [overflow-wrap:anywhere] rounded-2xl rounded-bl-md bg-white px-3.5 py-2.5 text-navy-800 shadow-sm ring-1 ring-navy-900/5';
            b.appendChild(textWithLinks(content));
            return b;
        }

        function peso(n) {
            return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        function productCard(p) {
            const card = document.createElement('div');
            card.className = 'bw-a-card flex items-start gap-3 rounded-2xl bg-white p-3 transition duration-150 hover:-translate-y-0.5';

            if (p.image) {
                const img = document.createElement('img');
                img.src = p.image;
                img.alt = '';
                img.loading = 'lazy';
                img.className = 'h-14 w-14 shrink-0 rounded-xl bg-navy-50 object-cover';
                img.onerror = () => img.remove();
                card.appendChild(img);
            }

            const meta = document.createElement('div');
            meta.className = 'min-w-0 flex-1 pt-0.5';
            const name = document.createElement('p');
            name.className = 'line-clamp-2 text-[13px] font-semibold leading-snug text-navy-900';
            name.textContent = p.name;
            const price = document.createElement('p');
            price.className = 'mt-1 text-[12px] font-medium text-navy-800/60';
            price.textContent = peso(p.price);
            meta.appendChild(name);
            meta.appendChild(price);
            card.appendChild(meta);

            // "＋ Add" pill → "✓ Added". Always adds into bw_cart in place — the
            // shopper never leaves the page they're on. (Never an <a>: a link
            // would navigate.) "✓ Added" reflects taps made in this chat only,
            // not the cart's prior contents.
            const key = p.id == null ? null : String(p.id);
            const added = key != null && addedIds.has(key);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.name = p.name;
            btn.className = 'mt-0.5 inline-flex shrink-0 items-center gap-1 rounded-full px-3 py-2 text-xs font-semibold transition active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40';
            paintBtn(btn, added);
            if (added) card.classList.add('bw-a-added');

            btn.addEventListener('click', () => {
                if (key == null) return;                  // nothing to key the cart by
                addToCart(p.id);
                addedIds.add(key);
                persist();
                card.classList.add('bw-a-added');
                paintBtn(btn, true);
                btn.animate(
                    [{ transform: 'scale(1)' }, { transform: 'scale(1.12)' }, { transform: 'scale(1)' }],
                    { duration: 180, easing: 'ease-out' },
                );
            });

            card.appendChild(btn);
            return card;
        }

        function paintBtn(btn, isAdded) {
            btn.classList.toggle('bg-brand-500', isAdded);
            btn.classList.toggle('text-white', isAdded);
            btn.classList.toggle('bg-brand-500/10', !isAdded);
            btn.classList.toggle('text-brand-600', !isAdded);
            btn.classList.toggle('hover:bg-brand-500/20', !isAdded);
            btn.setAttribute('aria-label', (isAdded ? 'Added to cart: ' : 'Add to cart: ') + (btn.dataset.name || ''));
            btn.textContent = isAdded ? '✓ Added' : '＋ Add';
        }

        function chipButton(spec, extraClass) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = extraClass;
            b.textContent = (spec.icon ? spec.icon + '  ' : '') + spec.label;
            b.addEventListener('click', () => { if (!pending) send(spec.msg); });
            return b;
        }

        // Inline lucide-style icon — matches the stroke language of the header
        // close / send glyphs. Static path strings only.
        function svgIcon(paths, cls) {
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('viewBox', '0 0 24 24');
            svg.setAttribute('fill', 'none');
            svg.setAttribute('stroke', 'currentColor');
            svg.setAttribute('stroke-width', '1.75');
            svg.setAttribute('stroke-linecap', 'round');
            svg.setAttribute('stroke-linejoin', 'round');
            svg.setAttribute('aria-hidden', 'true');
            svg.setAttribute('class', cls || 'h-[18px] w-[18px]');
            svg.innerHTML = paths;
            return svg;
        }

        // A single welcome quick-action: icon tile · label · chevron. Compact
        // row, full-width for thumb reach, one subtle border, no shadow.
        function welcomeActionButton(spec) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'group flex w-full items-center gap-3 rounded-xl border border-navy-900/10 bg-white px-3 py-2.5 text-left text-[13px] font-semibold text-navy-800 transition hover:border-brand-500/50 hover:bg-brand-500/5 active:scale-[.99] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/30';

            const tile = document.createElement('span');
            tile.className = 'grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-500/10 text-brand-600 transition group-hover:bg-brand-500/20';
            tile.appendChild(svgIcon(spec.icon));
            b.appendChild(tile);

            const label = document.createElement('span');
            label.className = 'flex-1';
            label.textContent = spec.label;
            b.appendChild(label);

            b.appendChild(svgIcon('<path d="m9 18 6-6-6-6"/>',
                'h-4 w-4 shrink-0 text-navy-900/25 transition group-hover:text-brand-500'));

            b.addEventListener('click', () => { if (!pending) send(spec.msg); });
            return b;
        }

        function welcomeBlock() {
            const wrap = document.createElement('div');
            wrap.className = 'space-y-4';
            wrap.appendChild(bubble('assistant', GREETING));
            const actions = document.createElement('div');
            actions.className = 'space-y-2';
            WELCOME_ACTIONS.forEach(a => actions.appendChild(welcomeActionButton(a)));
            wrap.appendChild(actions);
            return wrap;
        }

        function turnEl(m) {
            const wrap = document.createElement('div');
            wrap.className = 'space-y-2';
            const text = (m.content || '').trim()
                || (m.products && m.products.length ? 'Here are some cakes you might like \u{1F370}' : '');
            if (text) wrap.appendChild(bubble(m.role, text));
            if (m.role === 'assistant' && Array.isArray(m.products) && m.products.length) {
                const cards = document.createElement('div');
                cards.className = 'space-y-2';
                m.products.forEach(p => cards.appendChild(productCard(p)));
                wrap.appendChild(cards);
            }
            return wrap;
        }

        function typingRow() {
            const row = document.createElement('div');
            row.id = 'bw-a-typing';
            row.innerHTML = '<div class="mr-auto flex w-max gap-1 rounded-2xl rounded-bl-md bg-white px-3.5 py-3 shadow-sm ring-1 ring-navy-900/5">'
                + '<span class="bw-a-dot h-1.5 w-1.5 rounded-full bg-navy-900/70"></span>'
                + '<span class="bw-a-dot h-1.5 w-1.5 rounded-full bg-navy-900/70"></span>'
                + '<span class="bw-a-dot h-1.5 w-1.5 rounded-full bg-navy-900/70"></span></div>';
            return row;
        }

        function renderChips() {
            const show = messages.length > 0;
            chipBar.style.display = show ? 'flex' : 'none';
            if (!show) return;
            chipBar.textContent = '';
            CHIPS.forEach(c => chipBar.appendChild(chipButton(c,
                'inline-flex shrink-0 items-center rounded-full border border-navy-900/10 bg-white px-3 py-1.5 text-xs font-medium text-navy-800 shadow-sm transition hover:border-brand-500/40 hover:bg-brand-500/5 hover:text-brand-600 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/30')));
        }

        function scrollDown() { log.scrollTop = log.scrollHeight; }

        function render(animateLast) {
            log.textContent = '';
            if (messages.length === 0) {
                // Welcome + actions sit just below the header — vertically
                // centring them in the tall log area left a big empty band
                // under the header.
                log.appendChild(welcomeBlock());
            } else {
                log.appendChild(bubble('assistant', GREETING));
                messages.forEach(m => log.appendChild(turnEl(m)));
            }
            if (pending) log.appendChild(typingRow());
            if (animateLast && log.lastElementChild) log.lastElementChild.classList.add('bw-a-in');
            renderChips();
            scrollDown();
        }

        // ---- proactive greeting / collapse behaviour --------------------
        // Shown once per visitor (localStorage). Reduced-motion still gets the
        // bubble, just without the fade / pulse.
        const SEEN_KEY = 'bw_moymoy_seen';
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const isDesktop = window.matchMedia('(hover: hover) and (min-width: 640px)').matches;
        let seen = false;
        try { seen = localStorage.getItem(SEEN_KEY) === '1'; } catch {}
        let inviteTimer = null, collapseTimer = null;

        function markSeen() {
            seen = true;
            try { localStorage.setItem(SEEN_KEY, '1'); } catch {}
        }
        function dismissInvite() {
            clearTimeout(inviteTimer); clearTimeout(collapseTimer);
            invite.hidden = true;
            invite.classList.remove('bw-a-anim-in');
            launcher.classList.remove('is-expanded');
        }
        function showInvite() {
            if (open || seen) return;
            invite.hidden = false;
            if (!prefersReduced) {
                invite.classList.add('bw-a-anim-in');
                avatar.classList.add('bw-a-pulse-once');
                avatar.addEventListener('animationend',
                    () => avatar.classList.remove('bw-a-pulse-once'), { once: true });
            }
            markSeen();
            // Let it breathe, then settle back to the compact avatar.
            collapseTimer = setTimeout(dismissInvite, 9000);
        }

        if (!seen) {
            if (isDesktop) launcher.classList.add('is-expanded'); // intro: expanded pill
            inviteTimer = setTimeout(showInvite, 2600);
        }
        inviteClose.addEventListener('click', e => { e.stopPropagation(); dismissInvite(); markSeen(); });
        invite.addEventListener('click', e => {
            if (e.target.closest('#bw-a-invite-close')) return;
            setOpen(true);
        });

        // ---- open / close ------------------------------------------------
        function setOpen(next) {
            open = next;
            panel.hidden = !open;
            launcher.hidden = open; // launcher shares the corner; hide while open
            toggle.setAttribute('aria-expanded', String(open));
            if (open) { dismissInvite(); markSeen(); render(false); setTimeout(() => input.focus(), 60); }
        }
        toggle.addEventListener('click', () => setOpen(!open));
        closeBtn.addEventListener('click', () => setOpen(false));
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && open) setOpen(false); });

        // ---- send -------------------------------------------------------
        async function send(text) {
            text = (text || '').trim();
            if (pending || !text) return;
            pending = true;
            sendBtn.disabled = true;
            input.value = '';
            messages.push({ role: 'user', content: text });
            render(true);

            try {
                const res = await fetch(ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        conversation_id: conversationId,
                        messages: messages.map(m => ({ role: m.role, content: m.content })).slice(-16),
                    }),
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                conversationId = data.conversation_id || conversationId;
                messages.push({
                    role: 'assistant',
                    content: data.reply || "Sorry, I didn't catch that — could you rephrase?",
                    products: Array.isArray(data.products) ? data.products : [],
                });
            } catch (err) {
                messages.push({
                    role: 'assistant',
                    content: "Sorry — I couldn't reach the kitchen just now. Please try again in a moment, or browse the menu at /menu.",
                });
            } finally {
                pending = false;
                sendBtn.disabled = false;
                persist();
                render(true);
                input.focus();
            }
        }

        form.addEventListener('submit', e => { e.preventDefault(); send(input.value); });
    })();
</script>
@endif
