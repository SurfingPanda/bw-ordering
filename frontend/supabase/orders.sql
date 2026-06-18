-- ============================================================================
-- bw Superbakeshop — orders table + row level security
-- Run this in the Supabase dashboard: SQL Editor → New query → Run.
-- ============================================================================

create table if not exists public.orders (
  id             uuid primary key default gen_random_uuid(),
  user_id        uuid not null references auth.users (id) on delete cascade,
  customer_name  text,
  customer_email text,
  items          jsonb not null default '[]'::jsonb,
  subtotal       numeric(10,2) not null default 0,
  discount       numeric(10,2) not null default 0,
  delivery       numeric(10,2) not null default 0,
  vat            numeric(10,2) not null default 0,
  total          numeric(10,2) not null default 0,
  voucher        text,
  status         text not null default 'pending',
  created_at     timestamptz not null default now()
);

create index if not exists orders_created_at_idx on public.orders (created_at desc);

-- Enable row level security.
alter table public.orders enable row level security;

-- Helper: is the current user an admin? Edit the email list to match
-- VITE_ADMIN_EMAILS in frontend/.env.
create or replace function public.is_admin()
returns boolean
language sql
stable
as $$
  select (auth.jwt() ->> 'email') = any (array[
    'bw.redeem@gmail.com',
    'admin@bwsuperbakeshop.com'
  ]);
$$;

-- Customers may create their own orders.
drop policy if exists "orders_insert_own" on public.orders;
create policy "orders_insert_own"
  on public.orders for insert
  to authenticated
  with check (auth.uid() = user_id);

-- Customers may read their own orders; admins may read everyone's.
drop policy if exists "orders_select_own_or_admin" on public.orders;
create policy "orders_select_own_or_admin"
  on public.orders for select
  to authenticated
  using (auth.uid() = user_id or public.is_admin());

-- Admins may update order status.
drop policy if exists "orders_update_admin" on public.orders;
create policy "orders_update_admin"
  on public.orders for update
  to authenticated
  using (public.is_admin())
  with check (public.is_admin());
