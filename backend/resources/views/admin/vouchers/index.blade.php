@extends('layouts.site-editor')

@section('title', 'Vouchers')

@section('editor-nav')
    @include('admin.content._editor-nav', ['activeSection' => 'vouchers'])
@endsection

@section('preview-label', 'Live preview — saved content (updates on save)')
@section('preview')
    @include('admin.content._preview', ['url' => route('menu', absolute: false)])
@endsection

@section('header-actions')
    @include('admin.content._save-reset', ['formId' => 'vouchers-form'])
@endsection

@section('content')
@php($input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20')

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="vouchers-form" method="POST" action="{{ route('admin.vouchers.sync') }}">
        @csrf
        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-navy-800">Vouchers</h2>
            <p class="mb-5 mt-0.5 text-sm text-slate-500">Discount codes customers can apply at checkout. These are the real codes — order totals are validated against them. Click “Save changes” to apply.</p>

            {{-- The ids loaded into the editor: anything here that's missing
                 from the submitted cards was removed → deleted on save. --}}
            @foreach($vouchers as $voucher)
                <input type="hidden" name="originalIds[]" value="{{ $voucher->id }}">
            @endforeach

            <div data-repeater>
                <div data-rows class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($vouchers as $i => $voucher)
                        @include('admin.vouchers._voucher-row', ['i' => $i, 'voucher' => array_merge($voucher->toArray(), ['expires_at' => $voucher->expires_at?->format('Y-m-d')])])
                    @endforeach
                </div>
                <template>@include('admin.vouchers._voucher-row', ['i' => '__IDX__', 'voucher' => ['type' => 'percent', 'active' => true]])</template>
                <button type="button" data-add class="mt-4 w-full rounded-xl border-2 border-dashed border-slate-300 py-3 text-sm font-semibold text-slate-500 transition hover:border-brand-400 hover:text-brand-600">
                    + Add voucher
                </button>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    @include('admin.content._form-scripts')
    <script>
        // Free-delivery vouchers have no value; the value label follows the
        // type (% vs ₱) — same behavior as the old editor. Delegated so it
        // also covers cards added after page load.
        document.addEventListener('change', (e) => {
            if (!e.target.matches('[data-voucher-type]')) return
            const card = e.target.closest('[data-row]')
            const value = card.querySelector('[data-voucher-value]')
            value.classList.toggle('hidden', e.target.value === 'freedel')
            value.querySelector('[data-value-label]').textContent =
                e.target.value === 'amount' ? 'Amount off (₱)' : 'Percent off (%)'
        })
    </script>
@endsection
