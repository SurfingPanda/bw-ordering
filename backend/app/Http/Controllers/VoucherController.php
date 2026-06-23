<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /** Public: active, non-expired vouchers (for the checkout client-side preview). */
    public function active()
    {
        return Voucher::where('active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString());
            })
            ->orderBy('code')
            ->get(['code', 'type', 'value', 'label']);
    }

    /** Admin/editor: list all vouchers. */
    public function index(Request $request)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isEditor($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return Voucher::orderBy('code')->get();
    }

    /**
     * Admin/editor save (Site Editor). Body: { vouchers: [...], originalIds: [...] }.
     * Updates rows with an id, inserts new ones, and deletes any removed id.
     */
    public function sync(Request $request)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isEditor($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'vouchers' => 'present|array',
            'vouchers.*.id' => 'nullable',
            'vouchers.*.code' => 'required|string|max:50',
            'vouchers.*.type' => 'required|in:percent,amount,freedel',
            'vouchers.*.value' => 'nullable|numeric',
            'vouchers.*.label' => 'nullable|string|max:255',
            'vouchers.*.active' => 'nullable|boolean',
            'vouchers.*.expires_at' => 'nullable|date',
            'originalIds' => 'nullable|array',
        ]);

        $keptIds = [];
        foreach ($data['vouchers'] as $v) {
            $attrs = [
                'code' => strtoupper(trim($v['code'])),
                'type' => $v['type'],
                'value' => $v['type'] === 'freedel' ? 0 : ($v['value'] ?? 0),
                'label' => $v['label'] ?? null,
                'active' => $v['active'] ?? true,
                'expires_at' => $v['expires_at'] ?: null,
            ];
            if (! empty($v['id'])) {
                Voucher::where('id', $v['id'])->update($attrs);
                $keptIds[] = $v['id'];
            } else {
                $created = Voucher::create($attrs);
                $keptIds[] = $created->id;
            }
        }

        $removed = array_diff($data['originalIds'] ?? [], $keptIds);
        if (! empty($removed)) {
            Voucher::whereIn('id', array_values($removed))->delete();
        }

        return Voucher::orderBy('code')->get();
    }
}
