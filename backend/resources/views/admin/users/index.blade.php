@extends('layouts.site-editor')

@section('title', 'Users & Roles')

@section('editor-nav')
    @include('admin.content._editor-nav', ['activeSection' => 'users'])
@endsection

@section('header-actions')
    <input type="search" id="user-search" placeholder="Search name or email"
        class="w-full rounded-full border border-slate-300 bg-white px-4 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 sm:w-64">
@endsection

@section('content')
    @php
        // Same palette as the old AdminUsers.jsx ROLE_STYLES (brand-700 → 600).
        $roleStyle = [
            'admin' => 'bg-brand-100 text-brand-600',
            'editor' => 'bg-blue-100 text-blue-700',
            'cashier' => 'bg-green-100 text-green-700',
            'customer' => 'bg-slate-100 text-slate-600',
        ];
        $roles = ['customer' => 'Customer', 'cashier' => 'Cashier', 'editor' => 'Editor', 'admin' => 'Admin'];
        $fmt = fn ($s) => $s ? \Illuminate\Support\Carbon::parse($s)->timezone('Asia/Manila')->format('M j, Y') : '—';
        $sections = [
            'orders' => 'Orders', 'custom-cakes' => 'Custom Cakes', 'products' => 'Products',
            'contact-messages' => 'Contact Messages', 'assistant-chats' => 'Moymoy Chats',
            'content' => 'Site Content', 'stores' => 'Stores', 'vouchers' => 'Vouchers',
        ];
        $roleDefaults = \App\Http\Controllers\Controller::ROLE_SECTIONS;
    @endphp

    @if(session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 sm:hidden">{{ session('status') }}</div>
    @endif
    @error('role')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @if($error)
        <div class="rounded-2xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">
            <p class="font-semibold">Couldn't load users.</p>
            <p class="mt-1">{{ $error }}</p>
            <p class="mt-3 text-red-600/80">Make sure <code>SUPABASE_SERVICE_ROLE_KEY</code> is set in <code>backend/.env</code> and that your email is the admin.</p>
        </div>
    @elseif(empty($users))
        <div class="py-16 text-center text-sm text-slate-500">No users found.</div>
    @else
        <p class="mb-3 text-xs font-medium text-slate-500">{{ count($users) }} accounts</p>
        <p id="user-no-match" class="hidden py-16 text-center text-sm text-slate-500">No users match your search.</p>

        <div class="overflow-x-auto rounded-2xl border border-slate-100 bg-white shadow-sm">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-[0.65rem] uppercase tracking-wider text-slate-400">
                        <th class="px-4 py-3 font-semibold">User</th>
                        <th class="px-4 py-3 font-semibold">Role</th>
                        <th class="px-4 py-3 font-semibold">Access</th>
                        <th class="px-4 py-3 font-semibold">Joined</th>
                        <th class="px-4 py-3 font-semibold">Last sign-in</th>
                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($users as $u)
                        @php
                            $isSelf = strtolower($u['email'] ?? '') === $myEmail;
                            $locked = ! empty($u['is_env_admin']);
                            $userGrants = $grants[strtolower($u['email'] ?? '')] ?? [];
                        @endphp
                        <tr class="user-card transition hover:bg-slate-50/70" data-search="{{ strtolower(($u['email'] ?? '').' '.($u['name'] ?? '')) }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-navy-100 text-sm font-bold text-navy-700">
                                        {{ strtoupper(substr($u['name'] ?? ($u['email'] ?? '?'), 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="flex items-center gap-1.5 truncate font-semibold text-navy-800">
                                            {{ $u['name'] ?? '—' }}
                                            @if($isSelf)
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[0.6rem] font-semibold text-amber-700">You</span>
                                            @endif
                                        </p>
                                        <p class="truncate text-xs text-slate-500">{{ $u['email'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-[0.65rem] font-semibold capitalize {{ $roleStyle[$u['role']] ?? $roleStyle['customer'] }}">{{ $u['role'] }}</span>
                                @if($locked)
                                    <p class="mt-1 text-[0.6rem] text-slate-400" title="Set in the server .env — change it there.">🔒 Admin</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($u['role'] === 'admin')
                                    <span class="text-xs text-slate-400">Everything</span>
                                @elseif($u['role'] === 'customer')
                                    <span class="text-xs text-slate-300">—</span>
                                @else
                                    <div class="flex flex-wrap items-center gap-1">
                                        @foreach($roleDefaults[$u['role']] ?? [] as $g)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.6rem] font-medium text-slate-500" title="Included with the {{ $u['role'] }} role">{{ $sections[$g] ?? $g }}</span>
                                        @endforeach
                                        @foreach($userGrants as $g)
                                            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[0.6rem] font-semibold text-brand-600" title="Extra grant">{{ $sections[$g] ?? $g }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ $fmt($u['created_at'] ?? null) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ $fmt($u['last_sign_in_at'] ?? null) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @if($locked)
                                    <span class="text-xs text-slate-300" title="Set in the server .env — change it there.">Locked</span>
                                @else
                                    <button type="button" data-edit-user
                                        data-email="{{ $u['email'] }}" data-name="{{ $u['name'] ?? '' }}"
                                        data-role="{{ $u['role'] }}" data-grants="{{ json_encode($userGrants) }}"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3.5 py-1.5 text-xs font-semibold text-navy-700 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                                        </svg>
                                        Edit
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Edit modal: role + access grants in one save --}}
        <div id="edit-user-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Edit user">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-navy-800" id="edit-user-name">Edit user</h3>
                <p class="mt-0.5 truncate text-sm text-slate-500" id="edit-user-email-label"></p>
                <form method="POST" action="{{ route('admin.users.update') }}">
                    @csrf
                    <input type="hidden" name="email" id="edit-user-email">

                    <label class="mt-4 block">
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Role</span>
                        <select name="role" id="edit-user-role"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            @foreach($roles as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <p class="mb-1 mt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Access</p>
                    <p class="mb-2 text-xs text-slate-400" id="edit-access-hint">Sections included with the role are locked in. Tick extras to grant them.</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($sections as $key => $label)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-navy-700 transition has-[:checked]:border-brand-300 has-[:checked]:bg-brand-50/60 has-[:disabled]:cursor-not-allowed" data-access-item="{{ $key }}">
                                <input type="checkbox" name="access[]" value="{{ $key }}"
                                    class="h-4 w-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                                <span class="min-w-0 truncate">{{ $label }}</span>
                                <span class="ml-auto hidden shrink-0 text-[0.6rem] font-semibold uppercase text-slate-400" data-role-tag>role</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" data-edit-close class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-navy-700 transition hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var ROLE_DEFAULTS = @json($roleDefaults);

            var search = document.getElementById('user-search');
            if (search) search.addEventListener('input', function () {
                var q = search.value.trim().toLowerCase();
                var shown = 0;
                document.querySelectorAll('.user-card').forEach(function (row) {
                    var show = !q || row.dataset.search.includes(q);
                    row.classList.toggle('hidden', !show);
                    if (show) shown++;
                });
                var noMatch = document.getElementById('user-no-match');
                if (noMatch) noMatch.classList.toggle('hidden', shown !== 0);
            });

            var modal = document.getElementById('edit-user-modal');
            if (!modal) return;
            var roleSelect = document.getElementById('edit-user-role');
            var hint = document.getElementById('edit-access-hint');
            var currentGrants = [];

            function paintAccess() {
                var role = roleSelect.value;
                var defaults = ROLE_DEFAULTS[role] || [];
                var lockcall = role === 'admin' || role === 'customer';
                hint.textContent = role === 'admin' ? 'Admins can access everything.'
                    : role === 'customer' ? 'Customers have no admin access — pick a staff role to grant sections.'
                    : 'Sections included with the role are locked in. Tick extras to grant them.';
                modal.querySelectorAll('[data-access-item]').forEach(function (item) {
                    var key = item.dataset.accessItem;
                    var viaRole = defaults.indexOf(key) !== -1;
                    var box = item.querySelector('input[type="checkbox"]');
                    if (lockcall) {
                        box.checked = role === 'admin';
                        box.disabled = true;
                    } else {
                        box.checked = viaRole || currentGrants.indexOf(key) !== -1;
                        box.disabled = viaRole;
                    }
                    item.classList.toggle('opacity-60', box.disabled);
                    item.querySelector('[data-role-tag]').classList.toggle('hidden', !(viaRole && !lockcall));
                });
            }

            function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

            document.querySelectorAll('[data-edit-user]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    try { currentGrants = JSON.parse(btn.dataset.grants || '[]'); } catch (e) { currentGrants = []; }
                    document.getElementById('edit-user-email').value = btn.dataset.email;
                    document.getElementById('edit-user-name').textContent = btn.dataset.name || 'Edit user';
                    document.getElementById('edit-user-email-label').textContent = btn.dataset.email;
                    roleSelect.value = btn.dataset.role;
                    paintAccess();
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            roleSelect.addEventListener('change', paintAccess);
            modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
            modal.querySelector('[data-edit-close]').addEventListener('click', closeModal);
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });
        });
    </script>
@endsection
