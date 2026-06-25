import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

// Admins OR HR may pass (used for the Careers admin panel).
export default function HRRoute({ children }) {
  const { user, isAdmin, isHr, loading, roleLoading } = useAuth()

  if (loading || roleLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-100 text-slate-500">
        Loading…
      </div>
    )
  }

  if (!user) return <Navigate to="/login" replace />
  if (!isAdmin && !isHr) return <Navigate to="/dashboard" replace />

  return children
}
