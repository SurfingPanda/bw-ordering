<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    protected $table = 'site_content';

    public $incrementing = false;

    protected $fillable = ['id', 'data'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * Resolve a CTA button's 3-state value (mirrors frontend/src/lib/content.js
     * buttonState()). Stored as `false`/'off' (hidden), 'disabled' (visible but
     * inert), or anything else / missing (on) — so legacy boolean saves and
     * unknown keys still behave sensibly.
     */
    public static function buttonState(?array $buttons, string $key): string
    {
        $v = $buttons[$key] ?? null;
        if ($v === false || $v === 'off') {
            return 'off';
        }
        if ($v === 'disabled') {
            return 'disabled';
        }

        return 'on';
    }

    /** True unless the button is hidden ('off'). Disabled buttons are still visible. */
    public static function isButtonVisible(?array $buttons, string $key): bool
    {
        return self::buttonState($buttons, $key) !== 'off';
    }

    /** True when the button should render but be inert (clicks do nothing). */
    public static function isButtonDisabled(?array $buttons, string $key): bool
    {
        return self::buttonState($buttons, $key) === 'disabled';
    }

    /**
     * Fallback delivery-fee / VAT settings. The Site Editor's "Fees & Tax"
     * section (admin/content → `pricing`) overrides any of these; these values
     * are what a fresh site (no `pricing` saved yet) charges. Kept in sync
     * with OrderCreationService's own constants.
     */
    public const PRICING_DEFAULTS = [
        'vatEnabled' => true,
        'vatRate' => 12.0,          // percent
        'deliveryEnabled' => true,
        'deliveryFee' => 79.0,      // standard delivery, pesos
        'expressFee' => 149.0,      // express delivery, pesos (flat)
        'freeDeliveryMin' => 1000.0, // subtotal for free standard delivery; 0 = never
    ];

    /**
     * Normalize the Site Editor's `pricing` blob into a fully-populated,
     * type-safe array. Pass the CMS blob to read it from there (so a live
     * preview draft is honoured); omit to load the saved row.
     *
     * Consumed by both OrderCreationService (the authoritative order math)
     * and partials/order-pricing (the client-side checkout preview) so the
     * two can never disagree on what a delivery fee or VAT rate is.
     */
    public static function pricingConfig(?array $blob = null): array
    {
        if ($blob === null) {
            $blob = self::find(1)?->data ?? [];
        }
        $p = (array) ($blob['pricing'] ?? []);
        $d = self::PRICING_DEFAULTS;
        // Blank / non-numeric ⇒ fall back to the default; only an explicit
        // number (including 0) is taken as-is. So clearing a fee field is
        // "leave it at the default", while typing 0 is "make it free".
        $num = fn ($v, float $default) => is_numeric($v) ? max(0, (float) $v) : $default;

        return [
            'vatEnabled' => (bool) ($p['vatEnabled'] ?? $d['vatEnabled']),
            'vatRate' => min(100, $num($p['vatRate'] ?? null, $d['vatRate'])),
            'deliveryEnabled' => (bool) ($p['deliveryEnabled'] ?? $d['deliveryEnabled']),
            'deliveryFee' => $num($p['deliveryFee'] ?? null, $d['deliveryFee']),
            'expressFee' => $num($p['expressFee'] ?? null, $d['expressFee']),
            'freeDeliveryMin' => $num($p['freeDeliveryMin'] ?? null, $d['freeDeliveryMin']),
        ];
    }

    /** Fixed option lists for the admin Typography panel's enum fields (admin/content/_typography-panel.blade.php). */
    public const TYPOGRAPHY_WEIGHTS = ['300', '400', '500', '600', '700', '800'];

    public const TYPOGRAPHY_FONTS = ['brand', 'script'];

    public const TYPOGRAPHY_ALIGNS = ['left', 'center', 'right', 'justify'];

    public const TYPOGRAPHY_DECORATIONS = ['underline', 'line-through'];

    public const TYPOGRAPHY_TRANSFORMS = ['uppercase', 'lowercase', 'capitalize', 'none'];

    public const TYPOGRAPHY_SHADOWS = ['sm', 'md'];

    /**
     * Whitelist + coerce a submitted typography sub-array (admin Content
     * editor's per-section Typography panel) before it's saved — an unknown
     * enum value or malformed color falls back to '' (inherit) rather than
     * being persisted as-is and breaking typographyStyle()'s output.
     */
    public static function normalizeTypography(?array $t): array
    {
        $t = (array) $t;
        $enum = fn ($value, array $allowed) => in_array($value, $allowed, true) ? $value : '';
        $color = strtolower(trim((string) ($t['color'] ?? '')));

        return [
            'size' => trim((string) ($t['size'] ?? '')),
            'weight' => $enum($t['weight'] ?? '', self::TYPOGRAPHY_WEIGHTS),
            'color' => preg_match('/^#[0-9a-f]{3,8}$/', $color) ? $color : '',
            'font' => $enum($t['font'] ?? '', self::TYPOGRAPHY_FONTS),
            'lineHeight' => trim((string) ($t['lineHeight'] ?? '')),
            'letterSpacing' => trim((string) ($t['letterSpacing'] ?? '')),
            'wordSpacing' => trim((string) ($t['wordSpacing'] ?? '')),
            'opacity' => ($t['opacity'] ?? '') === '' ? '' : max(0, min(100, (int) $t['opacity'])),
            'align' => $enum($t['align'] ?? '', self::TYPOGRAPHY_ALIGNS),
            'italic' => ! empty($t['italic']),
            'decoration' => $enum($t['decoration'] ?? '', self::TYPOGRAPHY_DECORATIONS),
            'transform' => $enum($t['transform'] ?? '', self::TYPOGRAPHY_TRANSFORMS),
            'textShadow' => $enum($t['textShadow'] ?? '', self::TYPOGRAPHY_SHADOWS),
        ];
    }

    /**
     * Build a `style=""`-ready CSS string from an already-normalized
     * typography array. Blank fields are skipped, so a section with no saved
     * typography renders with an empty (harmless) style attribute — visually
     * identical to not having one.
     */
    public static function typographyStyle(?array $t): string
    {
        if (empty($t)) {
            return '';
        }

        $css = [];
        if (($t['size'] ?? '') !== '') {
            $css[] = 'font-size:'.$t['size'];
        }
        if (($t['weight'] ?? '') !== '') {
            $css[] = 'font-weight:'.$t['weight'];
        }
        if (($t['color'] ?? '') !== '') {
            $css[] = 'color:'.$t['color'];
        }
        if (($t['font'] ?? '') === 'brand') {
            $css[] = 'font-family:var(--font-brand)';
        } elseif (($t['font'] ?? '') === 'script') {
            $css[] = 'font-family:var(--font-script)';
        }
        if (($t['lineHeight'] ?? '') !== '') {
            $css[] = 'line-height:'.$t['lineHeight'];
        }
        if (($t['letterSpacing'] ?? '') !== '') {
            $css[] = 'letter-spacing:'.$t['letterSpacing'];
        }
        if (($t['wordSpacing'] ?? '') !== '') {
            $css[] = 'word-spacing:'.$t['wordSpacing'];
        }
        // 100 is the neutral/inherited value (the range input's own default),
        // so treat it the same as unset rather than emitting a no-op opacity:1.
        if (($t['opacity'] ?? '') !== '' && (int) $t['opacity'] !== 100) {
            $css[] = 'opacity:'.(max(0, min(100, (int) $t['opacity'])) / 100);
        }
        if (($t['align'] ?? '') !== '') {
            $css[] = 'text-align:'.$t['align'];
        }
        if (! empty($t['italic'])) {
            $css[] = 'font-style:italic';
        }
        if (($t['decoration'] ?? '') !== '') {
            $css[] = 'text-decoration:'.$t['decoration'];
        }
        if (($t['transform'] ?? '') !== '') {
            $css[] = 'text-transform:'.$t['transform'];
        }
        if (($t['textShadow'] ?? '') === 'sm') {
            $css[] = 'text-shadow:0 1px 2px rgba(0,0,0,.15)';
        } elseif (($t['textShadow'] ?? '') === 'md') {
            $css[] = 'text-shadow:0 2px 6px rgba(0,0,0,.25)';
        }

        return $css ? implode(';', $css).';' : '';
    }
}
