-- ============================================================================
-- bw Superbakeshop — profiles table (enforces unique contact numbers)
-- Run this in the Supabase dashboard: SQL Editor → New query → Run.
--
-- Contact numbers also live in auth.users.user_metadata for display, but that
-- metadata can't be queried across users from the client, so uniqueness is
-- enforced here with a UNIQUE constraint on a normalized (digits-only) number.
-- ============================================================================

create table if not exists public.profiles (
  id             uuid primary key references auth.users (id) on delete cascade,
  contact_number text not null unique,
  created_at     timestamptz not null default now(),
  updated_at     timestamptz not null default now()
);

-- Enable row level security.
alter table public.profiles enable row level security;

-- Users may create their own profile row.
drop policy if exists "profiles_insert_own" on public.profiles;
create policy "profiles_insert_own"
  on public.profiles for insert
  to authenticated
  with check (auth.uid() = id);

-- Users may read their own profile.
drop policy if exists "profiles_select_own" on public.profiles;
create policy "profiles_select_own"
  on public.profiles for select
  to authenticated
  using (auth.uid() = id);

-- Users may update their own profile.
drop policy if exists "profiles_update_own" on public.profiles;
create policy "profiles_update_own"
  on public.profiles for update
  to authenticated
  using (auth.uid() = id)
  with check (auth.uid() = id);

-- ----------------------------------------------------------------------------
-- Uniqueness pre-check. RLS only lets a user see their own row, so the register
-- form (where the user isn't signed in yet) can't query profiles directly.
-- This SECURITY DEFINER function answers "is this number taken?" with a plain
-- boolean — it never returns any other user's data. Pass a normalized
-- (digits-only) number; the UNIQUE constraint is still the hard guarantee.
-- ----------------------------------------------------------------------------
create or replace function public.contact_number_taken(p_number text)
returns boolean
language sql
security definer
set search_path = public
as $$
  select exists (
    select 1 from public.profiles where contact_number = p_number
  );
$$;

grant execute on function public.contact_number_taken(text) to anon, authenticated;
