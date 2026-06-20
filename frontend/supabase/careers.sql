-- ============================================================================
-- bw Superbakeshop — job applications + resume storage
-- Run this in Supabase: SQL Editor -> New query -> Run.
-- Depends on: is_admin() and is_hr() (from orders.sql / site_content.sql).
-- ============================================================================

create table if not exists public.applications (
  id          uuid primary key default gen_random_uuid(),
  name        text not null,
  email       text not null,
  phone       text,
  position    text not null,
  cv_url      text,
  created_at  timestamptz not null default now()
);

create index if not exists applications_created_at_idx
  on public.applications (created_at desc);

alter table public.applications enable row level security;

-- Anyone (even anonymous / unauthenticated visitors) can submit an application.
drop policy if exists "applications_insert_public" on public.applications;
create policy "applications_insert_public"
  on public.applications for insert
  to anon, authenticated
  with check (true);

-- Only admin and HR can read applications.
drop policy if exists "applications_select_hr" on public.applications;
create policy "applications_select_hr"
  on public.applications for select
  to authenticated
  using (public.is_admin() or public.is_hr());

-- ----------------------------------------------------------------------------
-- Private storage bucket for resumes / CVs
-- ----------------------------------------------------------------------------
insert into storage.buckets (id, name, public)
values ('resumes', 'resumes', false)
on conflict (id) do nothing;

-- Anyone can upload a resume (applicants are anonymous).
drop policy if exists "resumes_upload" on storage.objects;
create policy "resumes_upload"
  on storage.objects for insert
  to anon, authenticated
  with check (bucket_id = 'resumes');

-- Only admin/HR can download resumes.
drop policy if exists "resumes_read_hr" on storage.objects;
create policy "resumes_read_hr"
  on storage.objects for select
  to authenticated
  using (bucket_id = 'resumes' and (public.is_admin() or public.is_hr()));
