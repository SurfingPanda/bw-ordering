<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BW Superbakeshop | {{ $page['title'] }}</title>
    <meta name="description" content="{{ $page['description'] }}">
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
                    <img src="{{ $nav['logo'] ?? '/images/logo (1).png' }}" alt="bw Superbakeshop" class="h-14 w-20 shrink-0 object-cover sm:h-16 sm:w-24">
                </a>
                <a href="/" class="text-sm font-medium text-navy-700 transition hover:text-brand-600">&larr; Back to home</a>
            </div>
        </header>

        <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
            <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">Legal</span>
            <h1 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">{{ $page['title'] }}</h1>
            @if(!empty($page['lastUpdated']))
                <p class="mt-2 text-sm text-slate-500">Last updated: {{ $page['lastUpdated'] }}</p>
            @endif

            <div class="mt-10 space-y-8 text-sm leading-relaxed text-slate-600">
                @foreach(preg_split('/\r\n|\r|\n/', trim((string) ($page['body'] ?? ''))) as $paragraph)
                    @if(trim($paragraph) === '')
                        @continue
                    @endif
                    @if(!str_contains($paragraph, '.'))
                        <h2 class="text-lg font-semibold text-navy-800">{{ $paragraph }}</h2>
                    @else
                        <p>{{ $paragraph }}</p>
                    @endif
                @endforeach
            </div>
        </section>

        @include('partials.site-footer', ['f' => $footerContent, 'social' => $social])
    </div>
</body>
</html>
