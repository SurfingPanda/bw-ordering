{{-- Site-wide footer (CMS content.footer + content.social) — extracted from
     landing.blade.php so other public pages (e.g. /stores) share it.
     Vars: $f (content.footer merged over LandingController defaults),
     $social (content.social). --}}
@php
    // mirrors frontend/src/pages/Landing.jsx's isExternal().
    $isExternal = fn (?string $href) => (bool) preg_match('#^https?://#i', (string) $href);
    $socialMeta = [
        ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'f'],
        ['key' => 'linkedin', 'label' => 'LinkedIn', 'icon' => 'in'],
        ['key' => 'x', 'label' => 'X (Twitter)', 'icon' => '𝕏'],
    ];
@endphp
<footer id="site-footer" class="bg-navy-900 text-navy-50/80">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.5fr_2.5fr]">
        <div>
            <div class="flex items-center gap-2">
                @if(!empty($f['logo']))
                    <img src="{{ $f['logo'] }}" alt="bw Superbakeshop" class="h-12 w-auto">
                @endif
                @if(!empty($f['brand']))
                    <span class="font-brand text-2xl font-bold text-white">{{ $f['brand'] }}</span>
                @endif
            </div>
            <p class="mt-4 max-w-xs text-sm text-navy-50/70">{{ $f['description'] }}</p>
            <div class="mt-5 flex gap-3">
                @foreach($socialMeta as $sm)
                    @php $href = trim($social[$sm['key']] ?? ''); @endphp
                    <a href="{{ $href ?: '#' }}" aria-label="{{ $sm['label'] }}"
                        @if($isExternal($href)) target="_blank" rel="noopener noreferrer" @endif
                        @if(!$href) onclick="event.preventDefault()" aria-disabled="true" @endif
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-sm font-semibold text-white transition hover:bg-brand-600">
                        {{ $sm['icon'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-2 gap-8 sm:grid-cols-3">
            @foreach($f['columns'] ?? [] as $col)
                <div>
                    <h4 class="text-sm font-semibold text-white">{{ $col['title'] }}</h4>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach($col['links'] ?? [] as $l)
                            <li>
                                @php $url = $l['url'] ?? ''; @endphp
                                @if(!$url)
                                    <a href="#" onclick="event.preventDefault()" aria-disabled="true" class="transition hover:text-brand-600">{{ $l['label'] }}</a>
                                @elseif($isExternal($url))
                                    <a href="{{ $url }}" target="_blank" rel="noreferrer" class="transition hover:text-brand-600">{{ $l['label'] }}</a>
                                @else
                                    <a href="{{ $url }}" class="transition hover:text-brand-600">{{ $l['label'] }}</a>
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
            <p>{{ $f['copyright'] }}</p>
        </div>
    </div>
</footer>
