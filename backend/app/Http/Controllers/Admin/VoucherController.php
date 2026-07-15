<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The Site Editor's "Vouchers" section — Blade port of the old
 * AdminContent.jsx vouchers editor: one grid of voucher cards edited freely
 * and persisted in a single "Save changes" (bulk upsert; cards removed from
 * the grid are deleted, mirroring the JSON API's VoucherController::sync()).
 * Same normalization as sync(): codes are uppercased/trimmed and a `freedel`
 * voucher always stores value = 0.
 */
class VoucherController extends Controller
{
    private function authorize(Request $request): void
    {
        $email = $this->supabaseUser($request)['email'] ?? null;
        abort_unless($this->canAccess($email, 'vouchers'), 403, 'Forbidden.');
    }

    /** Sidebar data for the Site Editor shell this page renders inside. */
    private function shell(Request $request): array
    {
        return [
            'navCounts' => SiteContentController::navCounts(),
            'isAdminUser' => $this->isAdmin($this->supabaseUser($request)['email'] ?? null),
            'navAccess' => $this->editorNavAccess($this->supabaseUser($request)['email'] ?? null),
        ];
    }

    public function index(Request $request)
    {
        $this->authorize($request);

        return view('admin.vouchers.index', [
            'vouchers' => Voucher::orderBy('code')->get(),
        ] + $this->shell($request));
    }

    /**
     * Bulk save the whole grid: rows with an id update, rows without insert,
     * and any voucher in `originalIds` that no longer appears was removed in
     * the editor → delete it (same as the API sync). Blank new cards (no
     * code) are ignored.
     */
    public function sync(Request $request)
    {
        $this->authorize($request);

        $request->validate([
            'vouchers' => 'nullable|array',
            'vouchers.*.id' => 'nullable|integer',
            'vouchers.*.type' => ['nullable', Rule::in(['percent', 'amount', 'freedel'])],
            'vouchers.*.value' => 'nullable|numeric|min:0',
            'vouchers.*.expires_at' => 'nullable|date',
            'originalIds' => 'nullable|array',
            'originalIds.*' => 'integer',
        ]);

        $keptIds = [];
        foreach ((array) $request->input('vouchers', []) as $v) {
            $v = (array) $v;
            $code = strtoupper(trim((string) ($v['code'] ?? '')));
            if ($code === '') {
                continue;
            }

            $type = $v['type'] ?? 'percent';
            $attrs = [
                'code' => $code,
                'type' => $type,
                'value' => $type === 'freedel' ? 0 : (float) ($v['value'] ?? 0),
                'label' => trim((string) ($v['label'] ?? '')) ?: null,
                'active' => ! empty($v['active']),
                'expires_at' => ($v['expires_at'] ?? '') ?: null,
            ];

            if (! empty($v['id'])) {
                Voucher::where('id', $v['id'])->update($attrs);
                $keptIds[] = (int) $v['id'];
            } else {
                $keptIds[] = Voucher::create($attrs)->id;
            }
        }

        $removed = array_diff(array_map('intval', (array) $request->input('originalIds', [])), $keptIds);
        if (! empty($removed)) {
            Voucher::whereIn('id', array_values($removed))->delete();
        }

        return redirect()->route('admin.vouchers.index')->with('status', 'Vouchers saved.');
    }
}
