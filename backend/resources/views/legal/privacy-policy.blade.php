<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BW Superbakeshop | Privacy Policy</title>
    <meta name="description" content="How bw Superbakeshop collects, uses, and protects your personal information.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Pacifico&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body>
    <div class="min-h-screen bg-white text-navy-800">
        <header class="sticky top-0 z-50 border-b border-slate-100 bg-white">
            <div class="mx-auto flex h-20 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="/" class="flex min-w-0 items-center gap-2">
                    <img src="/images/logo (1).png" alt="bw Superbakeshop" class="h-14 w-20 shrink-0 object-cover sm:h-16 sm:w-24">
                </a>
                <a href="/" class="text-sm font-medium text-navy-700 transition hover:text-brand-600">← Back to home</a>
            </div>
        </header>

        <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
            <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">Legal</span>
            <h1 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">Privacy Policy</h1>
            <p class="mt-2 text-sm text-slate-500">Last updated: {{ now()->format('F j, Y') }}</p>

            <div class="prose prose-slate mt-10 max-w-none space-y-8 text-sm leading-relaxed text-slate-600">
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Information we collect</h2>
                    <p class="mt-2">When you create an account, place an order, or submit a custom cake inquiry, we collect information such as your name, email address, contact number, delivery address, and order details. If you sign in with Google or Facebook, we receive the basic profile information those providers share with us.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">How we use your information</h2>
                    <p class="mt-2">We use your information to process and fulfill orders, communicate order and account updates, respond to custom cake inquiries, and improve our products and services. We do not sell your personal information to third parties.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Payments</h2>
                    <p class="mt-2">Online payments are processed by our payment partner, PayMongo. We do not store your full card or e-wallet credentials — payment details are handled directly by PayMongo's secure systems.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Account & authentication</h2>
                    <p class="mt-2">Account authentication is handled by Supabase. Your password (or OAuth sign-in) is managed by Supabase's authentication service and is never stored in our own database.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Cookies</h2>
                    <p class="mt-2">We use a session cookie to keep you signed in, and your cart is kept in your browser's local storage. We do not use third-party advertising or tracking cookies.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Your choices</h2>
                    <p class="mt-2">You may update your account information at any time from your Profile page, or contact us to request that your data be corrected or deleted, subject to any records we're required to keep for order and tax history.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Contact us</h2>
                    <p class="mt-2">If you have questions about this Privacy Policy, please reach out through our store locations or the contact details listed on our Franchise page.</p>
                </div>
            </div>
        </section>

        @include('partials.site-footer', ['f' => $footerContent, 'social' => $social])
    </div>
</body>
</html>
