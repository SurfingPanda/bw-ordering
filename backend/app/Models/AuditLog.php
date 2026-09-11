<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One recorded staff action in the Site Editor / Admin area — who changed
 * what, when, from where. Append-only: rows are created by the
 * RecordsAuditLog trait on the admin controllers and never updated or
 * deleted in-app. Read back on the Audit Log page (editors + admins).
 */
class AuditLog extends Model
{
    /** Append-only — Eloquent manages `created_at`, there is no `updated_at`. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_email',
        'actor_name',
        'actor_role',
        'action',
        'target',
        'summary',
        'meta',
        'ip_address',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Known action verbs → short human labels, for the filter dropdown and
     * the table's action pill. Anything not listed still logs and displays
     * (falls back to the raw verb), this is just the friendly copy.
     */
    public const ACTIONS = [
        'content.updated' => 'Site content saved',
        'content.stores_page_updated' => 'Stores page header saved',
        'content.categories_saved' => 'Menu categories saved',
        'content.category_renamed' => 'Menu category renamed',
        'content.category_deleted' => 'Menu category deleted',
        'asset.uploaded' => 'Image uploaded',
        'product.saved' => 'Products saved',
        'store.saved' => 'Stores saved',
        'voucher.saved' => 'Vouchers saved',
        'user.role_updated' => 'User role changed',
        'order.status_updated' => 'Order status changed',
        'order.payment_updated' => 'Order payment changed',
        'custom_cake.status_updated' => 'Custom cake status changed',
        'contact_message.status_updated' => 'Contact message status changed',
    ];

    public function label(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
