{{-- Per-section Typography style panel: font/color/spacing/alignment
     controls whose saved values are applied on the matching public page via
     SiteContent::typographyStyle(). Vars: $name (input name prefix, e.g.
     "whatsNew[typography]"), $value (current typography array, may be
     empty/missing — every field then renders blank, which is the "inherit,
     no visual change" state). Wiring: admin/content/_form-scripts. --}}
@php
    $ty = (array) ($value ?? []);
    $tyInput = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20';
    $tyLabel = 'mb-1 block text-xs font-medium text-slate-500';
    $tyOpacity = ($ty['opacity'] ?? '') !== '' ? (int) $ty['opacity'] : 100;
@endphp
<details class="mt-4 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3" data-typography-panel>
    <summary class="flex cursor-pointer select-none items-center justify-between text-sm font-semibold text-navy-800">
        Typography
        <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9" /></svg>
    </summary>
    <div class="mt-3 grid gap-3 sm:grid-cols-2">
        <label class="block">
            <span class="{{ $tyLabel }}">Size</span>
            <input type="text" name="{{ $name }}[size]" value="{{ $ty['size'] ?? '' }}" placeholder="e.g. 1.25rem" class="{{ $tyInput }}">
        </label>
        <label class="block">
            <span class="{{ $tyLabel }}">Weight</span>
            <select name="{{ $name }}[weight]" class="{{ $tyInput }} bg-white">
                <option value="" @selected(($ty['weight'] ?? '') === '')>Default</option>
                <option value="300" @selected(($ty['weight'] ?? '') === '300')>Light</option>
                <option value="400" @selected(($ty['weight'] ?? '') === '400')>Regular</option>
                <option value="500" @selected(($ty['weight'] ?? '') === '500')>Medium</option>
                <option value="600" @selected(($ty['weight'] ?? '') === '600')>Semibold</option>
                <option value="700" @selected(($ty['weight'] ?? '') === '700')>Bold</option>
                <option value="800" @selected(($ty['weight'] ?? '') === '800')>Extrabold</option>
            </select>
        </label>
        <label class="block">
            <span class="{{ $tyLabel }}">Color</span>
            <div class="flex items-center gap-2" data-typography-color>
                <input type="color" value="{{ ($ty['color'] ?? '') !== '' ? $ty['color'] : '#000000' }}" title="Pick a color" data-color-swatch class="h-9 w-11 shrink-0 cursor-pointer rounded-lg border border-slate-300 bg-white p-0.5">
                <input type="text" name="{{ $name }}[color]" value="{{ $ty['color'] ?? '' }}" placeholder="Default" data-color-hex class="{{ $tyInput }}">
            </div>
        </label>
        <label class="block">
            <span class="{{ $tyLabel }}">Font</span>
            <select name="{{ $name }}[font]" class="{{ $tyInput }} bg-white">
                <option value="" @selected(($ty['font'] ?? '') === '')>Default</option>
                <option value="brand" @selected(($ty['font'] ?? '') === 'brand')>Fredoka (Brand)</option>
                <option value="script" @selected(($ty['font'] ?? '') === 'script')>Pacifico (Script)</option>
            </select>
        </label>
        <label class="block">
            <span class="{{ $tyLabel }}">Line Height</span>
            <input type="text" name="{{ $name }}[lineHeight]" value="{{ $ty['lineHeight'] ?? '' }}" placeholder="e.g. 1.5" class="{{ $tyInput }}">
        </label>
        <label class="block">
            <span class="{{ $tyLabel }}">Letter Spacing</span>
            <input type="text" name="{{ $name }}[letterSpacing]" value="{{ $ty['letterSpacing'] ?? '' }}" placeholder="e.g. 0.05em" class="{{ $tyInput }}">
        </label>
        <label class="block">
            <span class="{{ $tyLabel }}">Word Spacing</span>
            <input type="text" name="{{ $name }}[wordSpacing]" value="{{ $ty['wordSpacing'] ?? '' }}" placeholder="e.g. 0.1em" class="{{ $tyInput }}">
        </label>
        <label class="block">
            <span class="{{ $tyLabel }}">Opacity (<span data-opacity-readout>{{ $tyOpacity }}</span>%)</span>
            <input type="range" min="0" max="100" name="{{ $name }}[opacity]" value="{{ $tyOpacity }}" data-opacity-range class="w-full accent-brand-500">
        </label>
    </div>

    <div class="mt-3">
        <span class="{{ $tyLabel }}">Alignment</span>
        <div class="grid grid-cols-4 overflow-hidden rounded-lg border border-slate-300 text-xs font-semibold" data-segmented-field>
            <input type="hidden" name="{{ $name }}[align]" value="{{ $ty['align'] ?? '' }}" data-segmented-input>
            @foreach(['left' => 'Left', 'center' => 'Center', 'right' => 'Right', 'justify' => 'Justify'] as $val => $label)
                <button type="button" data-segmented-option="{{ $val }}" class="border-r border-slate-300 px-2 py-1.5 text-center transition last:border-r-0 {{ ($ty['align'] ?? '') === $val ? 'bg-navy-800 text-white' : 'bg-white text-navy-700 hover:bg-slate-50' }}">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="mt-3 grid gap-3 sm:grid-cols-2">
        <div>
            <span class="{{ $tyLabel }}">Italic</span>
            <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                <input type="hidden" name="{{ $name }}[italic]" value="0">
                <input type="checkbox" name="{{ $name }}[italic]" value="1" class="peer sr-only" @checked(! empty($ty['italic']))>
                <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
            </span>
        </div>
        <label class="block">
            <span class="{{ $tyLabel }}">Decoration</span>
            <select name="{{ $name }}[decoration]" class="{{ $tyInput }} bg-white">
                <option value="" @selected(($ty['decoration'] ?? '') === '')>Default</option>
                <option value="underline" @selected(($ty['decoration'] ?? '') === 'underline')>Underline</option>
                <option value="line-through" @selected(($ty['decoration'] ?? '') === 'line-through')>Line-through</option>
            </select>
        </label>
    </div>

    <div class="mt-3">
        <span class="{{ $tyLabel }}">Transform</span>
        <div class="grid grid-cols-4 overflow-hidden rounded-lg border border-slate-300 text-xs font-semibold" data-segmented-field>
            <input type="hidden" name="{{ $name }}[transform]" value="{{ $ty['transform'] ?? '' }}" data-segmented-input>
            @foreach(['uppercase' => 'AA', 'lowercase' => 'aa', 'capitalize' => 'Aa', 'none' => '—'] as $val => $label)
                <button type="button" data-segmented-option="{{ $val }}" class="border-r border-slate-300 px-2 py-1.5 text-center transition last:border-r-0 {{ ($ty['transform'] ?? '') === $val ? 'bg-navy-800 text-white' : 'bg-white text-navy-700 hover:bg-slate-50' }}">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <label class="mt-3 block">
        <span class="{{ $tyLabel }}">Text Shadow</span>
        <select name="{{ $name }}[textShadow]" class="{{ $tyInput }} bg-white">
            <option value="" @selected(($ty['textShadow'] ?? '') === '')>None</option>
            <option value="sm" @selected(($ty['textShadow'] ?? '') === 'sm')>Subtle</option>
            <option value="md" @selected(($ty['textShadow'] ?? '') === 'md')>Strong</option>
        </select>
    </label>
</details>
