import api from './api'

// Careers (job applications + resumes) live in the Laravel API (MySQL) now —
// Supabase is only used for auth. Applicants are anonymous, so resume upload
// and application submit are public; listing + resume download require HR/admin.

// Upload a resume/CV to the private local disk. Returns the stored filename
// (not a public URL — downloads go through a signed, HR-only route).
export async function uploadResume(file) {
  const form = new FormData()
  form.append('file', file)
  const { data } = await api.post('/resumes', form)
  return data.path
}

// Submit a job application (public — no auth required).
export async function submitApplication({ name, email, phone, position, cvPath }) {
  await api.post('/applications', { name, email, phone, position, cv_url: cvPath })
}

// HR/Admin: fetch all applications, newest first.
export async function fetchApplications() {
  const { data } = await api.get('/applications')
  return data || []
}

// HR/Admin: get a time-limited signed URL to download a private resume.
export async function getResumeUrl(cvPath) {
  const { data } = await api.get('/resumes/url', { params: { path: cvPath } })
  return data.url
}
