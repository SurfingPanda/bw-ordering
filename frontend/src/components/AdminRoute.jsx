import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

// Only signed-in admins (email in VITE_ADMIN_EMAILS) may pass.
export default function AdminRoute({ children }) {
  const { user, isAdmin, loading } = useAuth()

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-100 text-slate-500">
        Loading…
      </div>
    )
  }

  if (!user) return <Navigate to="/login" replace />
  if (!isAdmin) return <Navigate to="/dashboard" replace />

  return children
}
