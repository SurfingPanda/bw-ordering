-- ============================================================================
-- bw Superbakeshop — order integrity + product write access
-- Run this in the Supabase dashboard: SQL Editor → New query → Run.
--
-- Server-authoritative order pricing: createOrder() only sends line items
-- (product_id + qty) and an optional voucher code. A BEFORE INSERT trigger on
-- `orders` recomputes every money field from the REAL products table (UUID id)
-- and the vouchers table below, overwriting anything the client sent — so a
-- tampered client can't set its own prices, discounts, or totals.
--
-- This matches the existing `products` catalogue schema (UUID id, price,
-- archived_at soft-delete, status). It does NOT create or reseed products.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- App vouchers (trusted discount definitions)
-- ----------------------------------------------------------------------------
create table if not exists public.vouchers (
  code   text primary key,
  type   text not null check (type in ('percent', 'amount', 'freedel')),
  value  numeric(10,2) not null default 0,
  label  text,
  active boolean not null default true
);

alter table public.vouchers enable row level security;

drop policy if exists "vouchers_read" on public.vouchers;
create policy "vouchers_read"
  on public.vouchers for select
  to anon, authenticated
  using (true);

insert into public.vouchers (code, type, value, label) values
  ('BW10',    'percent', 10, '10% off'),
  ('SAVE50',  'amount',  50, '₱50 off'),
  ('FREEDEL', 'freedel',  0, 'Free delivery')
on conflict (code) do update
  set type = excluded.type, value = excluded.value,
      label = excluded.label, active = true;

-- ----------------------------------------------------------------------------
-- Optional per-product calories, shown on the Menu cards + product modal.
-- Additive column only (nullable) so the other app sharing this catalogue is
-- unaffected. Idempotent — safe to re-run.
-- ----------------------------------------------------------------------------
alter table public.products add column if not exists calories integer;

-- ----------------------------------------------------------------------------
-- Let admins and content editors manage products from the Site Editor.
-- Additive policy only — RLS enable/disable is left untouched so the other app
-- that shares this catalogue is unaffected. (Removals archive, not hard-delete.)
-- ----------------------------------------------------------------------------
drop policy if exists "products_write_admin_editor" on public.products;
create policy "products_write_admin_editor"
  on public.products for all
  to authenticated
  using (public.is_admin() or public.is_editor())
  with check (public.is_admin() or public.is_editor());

-- ----------------------------------------------------------------------------
-- Recompute order money from the trusted products + vouchers tables.
-- Pricing rules mirror frontend/src/pages/Menu.jsx: VAT 12% on the post-discount
-- amount, ₱79 delivery, free over ₱1000.
-- ----------------------------------------------------------------------------
create or replace function public.compute_order_totals()
returns trigger
language plpgsql
as $$
declare
  vat_rate          constant numeric := 0.12;
  delivery_fee      constant numeric := 79;
  free_delivery_min constant numeric := 1000;

  item          jsonb;
  pid           uuid;
  qty           int;
  v_price       numeric;
  v_name        text;
  v_status      text;
  vrec          public.vouchers%rowtype;
  built_items   jsonb := '[]'::jsonb;
  subtotal      numeric := 0;
  discount      numeric := 0;
  delivery      numeric := 0;
  free_delivery boolean := false;
begin
  for item in select * from jsonb_array_elements(coalesce(new.items, '[]'::jsonb))
  loop
    begin
      pid := (item->>'product_id')::uuid;
    exception when others then
      pid := null;
    end;
    qty := floor(coalesce(nullif(item->>'qty', ''), '0')::numeric)::int;

    if qty is null or qty <= 0 then
      raise exception 'Invalid quantity in order line: %', item;
    end if;
    if pid is null then
      raise exception 'Missing product_id in order line: %', item;
    end if;

    select price, name, status into v_price, v_name, v_status
      from public.products
      where id = pid and archived_at is null;
    if not found then
      raise exception 'Unknown or unavailable product: %', pid;
    end if;
    if v_status = 'sold_out' then
      raise exception 'Product is sold out: %', v_name;
    end if;

    subtotal := subtotal + (v_price * qty);
    built_items := built_items || jsonb_build_object(
      'product_id', pid, 'name', v_name, 'price', v_price, 'qty', qty
    );
  end loop;

  if subtotal <= 0 then
    raise exception 'Order has no valid items';
  end if;

  -- Validate the voucher against the trusted table; ignore unknown/inactive.
  if new.voucher is not null and length(trim(new.voucher)) > 0 then
    select * into vrec from public.vouchers
      where upper(code) = upper(trim(new.voucher)) and active;
    if found then
      if vrec.type = 'percent' then
        discount := round(subtotal * vrec.value / 100, 2);
      elsif vrec.type = 'amount' then
        discount := least(vrec.value, subtotal);
      elsif vrec.type = 'freedel' then
        free_delivery := true;
      end if;
      new.voucher := upper(vrec.code);
    else
      new.voucher := null;
    end if;
  else
    new.voucher := null;
  end if;

  if subtotal >= free_delivery_min then
    free_delivery := true;
  end if;
  delivery := case when free_delivery then 0 else delivery_fee end;

  new.items    := built_items;
  new.subtotal := subtotal;
  new.discount := discount;
  new.delivery := delivery;
  new.vat      := round((subtotal - discount) * vat_rate, 2);
  new.total    := (subtotal - discount) + new.vat + delivery;
  new.status   := 'pending';

  return new;
end;
$$;

drop trigger if exists orders_compute_totals on public.orders;
create trigger orders_compute_totals
  before insert on public.orders
  for each row execute function public.compute_order_totals();
