<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BW Superbakeshop | Terms of Service</title>
    <meta name="description" content="The terms and conditions for ordering from and using bw Superbakeshop.">
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
            <h1 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">Terms of Service</h1>
            <p class="mt-2 text-sm text-slate-500">Last updated: {{ now()->format('F j, Y') }}</p>

            <div class="prose prose-slate mt-10 max-w-none space-y-8 text-sm leading-relaxed text-slate-600">
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Acceptance of terms</h2>
                    <p class="mt-2">By using bw Superbakeshop's website to browse, order, or submit a custom cake inquiry, you agree to these Terms of Service. If you do not agree, please do not use the site.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Orders & pricing</h2>
                    <p class="mt-2">All prices are shown in Philippine Peso (₱) and are subject to change without notice. Order totals — including delivery fees and any applied vouchers — are calculated and confirmed by us at checkout; the cart you build is a request, not a final price quote.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Payment</h2>
                    <p class="mt-2">We accept cash on pickup and, where available, online payment via our payment partner. Cash orders are payable at the store upon pickup. Online payments are confirmed by our payment gateway before an order is marked paid.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Delivery & pickup</h2>
                    <p class="mt-2">Delivery is available only from stores that serve your chosen delivery option and area; standard and express delivery fees apply as shown at checkout, unless a promotion or voucher applies. Pickup orders must be collected from the selected store within its operating hours.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Custom cake inquiries</h2>
                    <p class="mt-2">Submitting a custom cake inquiry does not guarantee availability or a final price — our team will follow up with a quote based on your requirements before any order is confirmed.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Cancellations & refunds</h2>
                    <p class="mt-2">Cancellation and refund requests are handled on a case-by-case basis. Please contact the store handling your order as soon as possible if you need to cancel or change it.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Account responsibility</h2>
                    <p class="mt-2">You are responsible for keeping your account credentials secure and for all activity under your account. Notify us promptly if you believe your account has been accessed without authorization.</p>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-navy-800">Changes to these terms</h2>
                    <p class="mt-2">We may update these Terms of Service from time to time. Continued use of the site after changes are posted constitutes acceptance of the revised terms.</p>
                </div>
            </div>
        </section>

        @include('partials.site-footer', ['f' => $footerContent, 'social' => $social])
    </div>
</body>
</html>
