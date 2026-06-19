-- ============================================================================
-- bw Superbakeshop — editable landing-page content (CMS)
-- Run this in Supabase: SQL Editor → New query → Run.
-- (Re-uses the public.is_admin() helper; it's re-created here so this file is
--  self-contained even if orders.sql hasn't been run.)
-- ============================================================================

create table if not exists public.site_content (
  id         int primary key,
  data       jsonb not null default '{}'::jsonb,
  updated_at timestamptz not null default now()
);

alter table public.site_content enable row level security;

-- Admin check — keep this email list in sync with VITE_ADMIN_EMAILS.
create or replace function public.is_admin()
returns boolean language sql stable as $$
  select (auth.jwt() ->> 'email') = any (array[
    'bw.redeem@gmail.com',
    'admin@bwsuperbakeshop.com'
  ]);
$$;

-- Content-editor check — keep in sync with VITE_EDITOR_EMAILS. These accounts
-- can edit landing content but NOT orders.
create or replace function public.is_editor()
returns boolean language sql stable as $$
  select (auth.jwt() ->> 'email') = any (array[
    'editor@bwsuperbakeshop.com'
  ]);
$$;

-- Anyone (even logged-out visitors) can read the landing content.
drop policy if exists "site_content_read" on public.site_content;
create policy "site_content_read"
  on public.site_content for select
  to anon, authenticated
  using (true);

-- Admins and editors can create/update it.
drop policy if exists "site_content_write" on public.site_content;
create policy "site_content_write"
  on public.site_content for all
  to authenticated
  using (public.is_admin() or public.is_editor())
  with check (public.is_admin() or public.is_editor());

-- ----------------------------------------------------------------------------
-- Storage bucket for uploaded images (banners, product photos, etc.)
-- ----------------------------------------------------------------------------
insert into storage.buckets (id, name, public)
values ('site-images', 'site-images', true)
on conflict (id) do nothing;

-- Public read of the bucket.
drop policy if exists "site_images_read" on storage.objects;
create policy "site_images_read"
  on storage.objects for select
  to anon, authenticated
  using (bucket_id = 'site-images');

-- Admins and editors can upload / update / delete images.
drop policy if exists "site_images_write" on storage.objects;
create policy "site_images_write"
  on storage.objects for all
  to authenticated
  using (bucket_id = 'site-images' and (public.is_admin() or public.is_editor()))
  with check (bucket_id = 'site-images' and (public.is_admin() or public.is_editor()));
