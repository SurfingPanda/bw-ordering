import { Route, Routes } from 'react-router-dom'
import Landing from './pages/Landing'
import Menu from './pages/Menu'
import Careers from './pages/Careers'
import Openings from './pages/Openings'
import Franchise from './pages/Franchise'
import Stores from './pages/Stores'
import NotFound from './pages/NotFound'
import Login from './pages/Login'
import Register from './pages/Register'
import CompleteProfile from './pages/CompleteProfile'
import Dashboard from './pages/Dashboard'
import AdminDashboard from './pages/AdminDashboard'
import AdminContent from './pages/AdminContent'
import AdminCareers from './pages/AdminCareers'
import ProtectedRoute from './components/ProtectedRoute'
import AdminRoute from './components/AdminRoute'
import EditorRoute from './components/EditorRoute'
import HRRoute from './components/HRRoute'

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Landing />} />
      <Route path="/menu" element={<Menu />} />
      <Route path="/careers" element={<Careers />} />
      <Route path="/careers/openings" element={<Openings />} />
      <Route path="/franchise" element={<Franchise />} />
      <Route path="/stores" element={<Stores />} />
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />
      <Route path="/complete-profile" element={<CompleteProfile />} />
      <Route
        path="/dashboard"
        element={
          <ProtectedRoute>
            <Dashboard />
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin"
        element={
          <AdminRoute>
            <AdminDashboard />
          </AdminRoute>
        }
      />
      <Route
        path="/admin/content"
        element={
          <EditorRoute>
            <AdminContent />
          </EditorRoute>
        }
      />
      <Route
        path="/admin/careers"
        element={
          <HRRoute>
            <AdminCareers />
          </HRRoute>
        }
      />
      <Route path="*" element={<NotFound />} />
    </Routes>
  )
}
