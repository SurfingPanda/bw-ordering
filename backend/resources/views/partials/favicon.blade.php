{{-- Shared favicon tags. Each Blade page has its own <head>, so include this in
     all of them (the old React index.html carried these; they were lost in the
     migration). Only favicon-192x192.png is a real file — public/favicon.ico is
     an empty 0-byte placeholder, so we point at the PNG. --}}
<link rel="icon" type="image/png" sizes="192x192" href="/favicon-192x192.png">
<link rel="apple-touch-icon" href="/favicon-192x192.png">
