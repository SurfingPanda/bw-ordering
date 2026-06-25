import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

// Anyone who may open the orders dashboard: admins OR cashiers. The dashboard
// itself hides admin-only sections (Reports, Users, Site Content) for cashiers.
export default function StaffRoute({ children }) {
  const { user, isAdmin, isCashier, loading, roleLoading } = useAuth()

  if (loading || roleLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-100 text-slate-500">
        Loading…
      </div>
    )
  }

  if (!user) return <Navigate to="/login" replace />
  if (!isAdmin && !isCashier) return <Navigate to="/dashboard" replace />

  return children
}
