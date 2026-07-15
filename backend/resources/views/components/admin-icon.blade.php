@props(['name'])
@php
    $paths = [
        'chart' => '<line x1="12" y1="20" x2="12" y2="10" /><line x1="18" y1="20" x2="18" y2="4" /><line x1="6" y1="20" x2="6" y2="16" />',
        'list' => '<line x1="8" y1="6" x2="21" y2="6" /><line x1="8" y1="12" x2="21" y2="12" /><line x1="8" y1="18" x2="21" y2="18" /><line x1="3" y1="6" x2="3.01" y2="6" /><line x1="3" y1="12" x2="3.01" y2="12" /><line x1="3" y1="18" x2="3.01" y2="18" />',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />',
        'edit' => '<path d="M12 20h9" /><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />',
        'clock' => '<circle cx="12" cy="12" r="9" /><polyline points="12 7 12 12 15 14" />',
        'utensils' => '<path d="M3 2v7a3 3 0 0 0 3 3v10" /><path d="M9 2v7a3 3 0 0 1-3 3" /><path d="M6 2v6" /><path d="M18 2c-1.7 0-3 1.8-3 4s1.3 4 3 4v11" />',
        'check' => '<circle cx="12" cy="12" r="9" /><polyline points="8.5 12 11 14.5 15.5 9.5" />',
        'ban' => '<circle cx="12" cy="12" r="9" /><line x1="5.6" y1="5.6" x2="18.4" y2="18.4" />',
        // Site Editor section icons — ported from AdminContent.jsx.
        'megaphone' => '<path d="m3 11 18-5v12L3 14v-3z" /><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6" />',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2" /><circle cx="9" cy="9" r="2" /><path d="m21 15-4.5-4.5L3 21" />',
        'sparkle' => '<path d="M12 2l1.9 5.6a3 3 0 0 0 1.9 1.9L21.4 11.4l-5.6 1.9a3 3 0 0 0-1.9 1.9L12 20.8l-1.9-5.6a3 3 0 0 0-1.9-1.9L2.6 11.4l5.6-1.9a3 3 0 0 0 1.9-1.9L12 2z" />',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" />',
        'layout' => '<rect x="3" y="3" width="18" height="18" rx="2" /><line x1="3" y1="15" x2="21" y2="15" /><line x1="9" y1="15" x2="9" y2="21" />',
        'star' => '<path d="m12 2 3 6.3 6.9.9-5 4.8 1.2 6.8L12 17.8 5.9 20.8 7.1 14l-5-4.8 6.9-.9Z" />',
        'login' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" /><polyline points="10 17 15 12 10 7" /><line x1="15" y1="12" x2="3" y2="12" />',
        'ticket' => '<path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2 2 2 0 0 0 0 4 2 2 0 0 1-2 2H5a2 2 0 0 1-2-2 2 2 0 0 0 0-4Z" /><path d="M13 7v2M13 15v2" />',
        'toggle' => '<rect x="2" y="7" width="20" height="10" rx="5" /><circle cx="9" cy="12" r="2.5" />',
        'tag' => '<path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z" /><circle cx="7" cy="7" r="1.2" />',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2" /><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /><path d="M2 13h20" />',
        'pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z" /><circle cx="12" cy="10" r="3" />',
        'chevron-right' => '<polyline points="9 18 15 12 9 6" />',
        'menu' => '<line x1="3" y1="6" x2="21" y2="6" /><line x1="3" y1="12" x2="21" y2="12" /><line x1="3" y1="18" x2="21" y2="18" />',
        'close' => '<line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />',
        'cake' => '<path d="M4 21h16v-7a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3Z" /><path d="M4 16c1.5 0 1.5 1.5 3 1.5s1.5-1.5 3-1.5 1.5 1.5 3 1.5 1.5-1.5 3-1.5 1.5 1.5 3 1.5" /><path d="M12 8V4M12 4l-1.2 1M12 4l1.2 1" />',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" />',
        'qr' => '<rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><path d="M14 14h3v3M21 21v.01M17 21h.01M21 17h.01" />',
        'share' => '<circle cx="18" cy="5" r="3" /><circle cx="6" cy="12" r="3" /><circle cx="18" cy="19" r="3" /><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4" />',
    ];
    // The sparkle glyph is a filled shape in the original icon set.
    $fill = $name === 'sparkle' ? 'currentColor' : 'none';
@endphp
<svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => $fill, 'stroke' => 'currentColor', 'stroke-width' => '2', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    {!! $paths[$name] ?? '' !!}
</svg>
