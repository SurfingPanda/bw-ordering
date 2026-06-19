import { Link } from 'react-router-dom'
import { useSeo } from '../lib/seo'

// Public 404 — replaces the old "redirect everything unknown to /login", which
// bounced crawlers and shared links off real URLs.
export default function NotFound() {
  useSeo('/')
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-navy-50/40 px-6 text-center text-navy-800">
      <span className="font-script text-6xl text-brand-500">404</span>
      <h1 className="mt-4 text-2xl font-bold sm:text-3xl">This page didn’t bake</h1>
      <p className="mt-3 max-w-md text-sm text-slate-500">
        The page you’re looking for doesn’t exist or may have moved. Let’s get you
        back to something fresh.
      </p>
      <div className="mt-7 flex flex-wrap justify-center gap-3">
        <Link
          to="/"
          className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
        >
          Back to home
        </Link>
        <Link
          to="/menu"
          className="rounded-full border border-slate-300 px-7 py-3 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600"
        >
          Browse the menu
        </Link>
      </div>
    </div>
  )
}
