{{-- Small role pill for the dark sidebar account footers. Var: $email.
     Resolves the role with the same precedence as
     Controller::effectiveRole() — env allowlists win, then the editable
     user_roles table; no role means a customer. HR and cashier both read as
     "Staff" for at-a-glance scanning. --}}
@php
    $rbEmail = strtolower(trim((string) ($email ?? '')));
    $rbInList = fn (string $key) => in_array($rbEmail, array_map('strtolower', config("supabase.{$key}", [])), true);
    $rbRole = $rbEmail === '' ? null
        : ($rbInList('admin_emails') ? 'admin'
        : ($rbInList('editor_emails') ? 'editor'
        : \App\Models\UserRole::roleFor($rbEmail)));
    [$rbLabel, $rbClass] = match ($rbRole) {
        'admin' => ['Admin', 'bg-brand-500/20 text-brand-400 ring-brand-400/40'],
        'editor' => ['Editor', 'bg-blue-500/20 text-blue-300 ring-blue-400/40'],
        'cashier' => ['Staff', 'bg-emerald-500/20 text-emerald-300 ring-emerald-400/40'],
        default => ['Customer', 'bg-white/10 text-navy-50/70 ring-white/20'],
    };
@endphp
<span class="inline-flex w-fit items-center rounded-full px-2 py-0.5 text-[0.6rem] font-semibold uppercase tracking-wide ring-1 {{ $rbClass }}">{{ $rbLabel }}</span>
