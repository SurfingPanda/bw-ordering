import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

// Admins OR content editors may pass (used for the Site Content editor).
export default function EditorRoute({ children }) {
  const { user, isAdmin, isEditor, loading, roleLoading } = useAuth()

  if (loading || roleLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-100 text-slate-500">
        Loading…
      </div>
    )
  }

  if (!user) return <Navigate to="/login" replace />
  if (!isAdmin && !isEditor) return <Navigate to="/dashboard" replace />

  return children
}
