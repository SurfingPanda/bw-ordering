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
}
