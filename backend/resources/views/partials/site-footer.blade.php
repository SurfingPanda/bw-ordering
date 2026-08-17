{{-- Site-wide footer (CMS content.footer + content.social) — extracted from
     landing.blade.php so other public pages (e.g. /stores) share it.
     Vars: $f (content.footer merged over LandingController defaults),
     $social (content.social). --}}
@php
    // mirrors frontend/src/pages/Landing.jsx's isExternal().
    $isExternal = fn (?string $href) => (bool) preg_match('#^https?://#i', (string) $href);
    $socialMeta = [
        ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'f'],
        ['key' => 'tiktok', 'label' => 'TikTok', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07Z"/></svg>'],
        ['key' => 'x', 'label' => 'X (Twitter)', 'icon' => '𝕏'],
    ];
@endphp
<footer id="site-footer" class="text-navy-50/80" style="background-color: {{ $f['backgroundColor'] ?? '#083caa' }};">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.5fr_2.5fr]">
        <div>
            <div class="flex items-center gap-2">
                @if(!empty($f['logo']))
                    {{-- Editor-uploaded, arbitrary aspect ratio — unlike the nav/
                         maintenance-page logo (a fixed local asset), there's no
                         single real width/height to declare here. A fixed box +
                         object-contain reserves the space without needing one. --}}
                    <img data-editable="footer.logo" src="{{ $f['logo'] }}" alt="bw Superbakeshop" class="h-24 w-48 object-contain object-left">
                @endif
            </div>
            <p data-editable="footer.description" data-editable-multiline class="mt-4 max-w-xs text-sm text-navy-50/70">{{ $f['description'] }}</p>
            <div class="mt-5 flex gap-3">
                @foreach($socialMeta as $sm)
                    @php $href = trim($social[$sm['key']] ?? ''); @endphp
                    <a href="{{ $href ?: '#' }}" aria-label="{{ $sm['label'] }}"
                        @if($isExternal($href)) target="_blank" rel="noopener noreferrer" @endif
                        @if(!$href) onclick="event.preventDefault()" aria-disabled="true" @endif
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-sm font-semibold text-white transition hover:bg-brand-600">
                        {!! $sm['icon'] !!}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Each column is a collapsed accordion on mobile (all 3 columns'
             links stacked open at once was the "too long" complaint — this
             lets a visitor open just the one they came for) and reverts to
             the always-open 3-across grid at sm:, where the height doesn't
             cost anything a click needs to fix. --}}
        <div class="divide-y divide-white/10 sm:grid sm:grid-cols-3 sm:gap-8 sm:divide-y-0">
            @foreach($f['columns'] ?? [] as $col)
                <div>
                    <button type="button" data-footer-toggle
                        class="flex w-full items-center justify-between py-4 text-left sm:pointer-events-none sm:py-0">
                        <h4 data-editable="footer.columns.{{ $loop->index }}.title" class="text-sm font-semibold text-white">{{ $col['title'] }}</h4>
                        <svg class="h-4 w-4 shrink-0 text-navy-50/50 transition-transform sm:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </button>
                    <ul data-footer-panel class="hidden space-y-2 pb-4 text-sm sm:block sm:pb-0 sm:pt-4">
                        @foreach($col['links'] ?? [] as $j => $l)
                            <li>
                                @php
                                    $url = trim((string) ($l['url'] ?? ''));
                                    // Repair legacy CMS entries saved with placeholders
                                    // instead of their real first-party pages.
                                    $legacyPageRoutes = [
                                        'about us' => 'about',
                                        'contact' => 'contact',
                                    ];
                                    $labelKey = strtolower(trim((string) ($l['label'] ?? '')));
                                    if (isset($legacyPageRoutes[$labelKey])
                                        && in_array($url, ['', '#', '/#'], true)) {
                                        $url = route($legacyPageRoutes[$labelKey], absolute: false);
                                    }
                                    $legacyShopCategories = [
                                        'cakes' => 'Cakes',
                                        'breads' => 'Breads',
                                        'pastries' => 'Pastries',
                                        'delicacies' => 'Delicacies',
                                    ];
                                    if (isset($legacyShopCategories[$labelKey])
                                        && in_array(rtrim($url, '/'), ['/menu'], true)) {
                                        $url = route('menu', ['category' => $legacyShopCategories[$labelKey]], false);
                                    }
                                @endphp
                                @php $linkPath = 'footer.columns.'.$loop->parent->index.'.links.'.$j.'.label'; @endphp
                                @if(!$url)
                                    <a data-editable="{{ $linkPath }}" href="#" onclick="event.preventDefault()" aria-disabled="true" class="transition hover:text-brand-600">{{ $l['label'] }}</a>
                                @elseif($isExternal($url))
                                    <a data-editable="{{ $linkPath }}" href="{{ $url }}" target="_blank" rel="noreferrer" class="transition hover:text-brand-600">{{ $l['label'] }}</a>
                                @else
                                    <a data-editable="{{ $linkPath }}" href="{{ $url }}" class="transition hover:text-brand-600">{{ $l['label'] }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-navy-50/60 sm:flex-row sm:px-6">
            <p data-editable="footer.copyright">{{ $f['copyright'] }}</p>
            <nav aria-label="Legal" class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-navy-50/70">
                <a href="{{ route('privacy-policy') }}" class="transition hover:text-brand-400">Privacy Policy</a>
                <span aria-hidden="true">&nbsp;|&nbsp;</span>
                <a href="{{ route('terms-of-service') }}" class="transition hover:text-brand-400">Terms of Service</a>
                <span aria-hidden="true">&nbsp;|&nbsp;</span>
                <a href="{{ route('data-deletion') }}" class="transition hover:text-brand-400">Data Deletion</a>
            </nav>
        </div>
    </div>

    <script>
    (function () {
        // Mobile-only accordion (the sm:pointer-events-none on the button
        // means this listener simply never fires once sm: kicks in and the
        // panel is forced open via sm:block regardless of the 'hidden' class).
        document.querySelectorAll('#site-footer [data-footer-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var panel = btn.nextElementSibling;
                panel.classList.toggle('hidden');
                btn.querySelector('svg').classList.toggle('rotate-180');
            });
        });
    })();
    </script>
</footer>
