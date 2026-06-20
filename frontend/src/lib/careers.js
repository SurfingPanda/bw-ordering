import { supabase } from './supabase'

// Upload a resume/CV to the private "resumes" storage bucket.
// Returns the storage path (not a public URL — the bucket is private).
export async function uploadResume(file) {
  const safe = file.name.replace(/[^a-zA-Z0-9.\-_]/g, '_')
  const path = `${Date.now()}-${safe}`
  const { error } = await supabase.storage.from('resumes').upload(path, file, {
    cacheControl: '3600',
    upsert: false,
  })
  if (error) throw error
  return path
}

// Submit a job application (public — no auth required).
export async function submitApplication({ name, email, phone, position, cvPath }) {
  const { error } = await supabase
    .from('applications')
    .insert({ name, email, phone, position, cv_url: cvPath })
  if (error) throw error
}

// HR/Admin: fetch all applications, newest first.
export async function fetchApplications() {
  const { data, error } = await supabase
    .from('applications')
    .select('*')
    .order('created_at', { ascending: false })
  if (error) throw error
  return data || []
}

// HR/Admin: generate a time-limited download URL for a private resume.
export async function getResumeUrl(cvPath) {
  const { data, error } = await supabase.storage
    .from('resumes')
    .createSignedUrl(cvPath, 3600)
  if (error) throw error
  return data.signedUrl
}
