import { lazy, Suspense } from 'react'
import { Route, Routes } from 'react-router-dom'

// Eager imports — prerendered public pages (must be available during SSR)
import Landing from './pages/Landing'
import Menu from './pages/Menu'
import Careers from './pages/Careers'
import Openings from './pages/Openings'
import Franchise from './pages/Franchise'
import Stores from './pages/Stores'
import NotFound from './pages/NotFound'

// Lazy imports — auth, dashboard, and admin pages (never prerendered)
const Login = lazy(() => import('./pages/Login'))
const Register = lazy(() => import('./pages/Register'))
const ForgotPassword = lazy(() => import('./pages/ForgotPassword'))
const ResetPassword = lazy(() => import('./pages/ResetPassword'))
const CompleteProfile = lazy(() => import('./pages/CompleteProfile'))
const Dashboard = lazy(() => import('./pages/Dashboard'))
const Profile = lazy(() => import('./pages/Profile'))
const MyOrders = lazy(() => import('./pages/MyOrders'))
const Checkout = lazy(() => import('./pages/Checkout'))
const AdminDashboard = lazy(() => import('./pages/AdminDashboard'))
const AdminContent = lazy(() => import('./pages/AdminContent'))
const AdminCareers = lazy(() => import('./pages/AdminCareers'))
const AdminUsers = lazy(() => import('./pages/AdminUsers'))

// Route guards (tiny, keep eager)
import ProtectedRoute from './components/ProtectedRoute'
import AdminRoute from './components/AdminRoute'
import EditorRoute from './components/EditorRoute'
import HRRoute from './components/HRRoute'
import StaffRoute from './components/StaffRoute'
import ScrollToTop from './components/ScrollToTop'

function Loading() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-navy-50/40">
      <div className="h-8 w-8 animate-spin rounded-full border-3 border-brand-500 border-t-transparent" />
    </div>
  )
}

export default function App() {
  return (
    <>
      <ScrollToTop />
      <Routes>
      <Route path="/" element={<Landing />} />
      <Route path="/menu" element={<Menu />} />
      <Route path="/careers" element={<Careers />} />
      <Route path="/careers/openings" element={<Openings />} />
      <Route path="/franchise" element={<Franchise />} />
      <Route path="/stores" element={<Stores />} />
      <Route path="/login" element={<Suspense fallback={<Loading />}><Login /></Suspense>} />
      <Route path="/register" element={<Suspense fallback={<Loading />}><Register /></Suspense>} />
      <Route path="/forgot-password" element={<Suspense fallback={<Loading />}><ForgotPassword /></Suspense>} />
      <Route path="/reset-password" element={<Suspense fallback={<Loading />}><ResetPassword /></Suspense>} />
      <Route path="/complete-profile" element={<Suspense fallback={<Loading />}><CompleteProfile /></Suspense>} />
      <Route
        path="/dashboard"
        element={
          <ProtectedRoute>
            <Suspense fallback={<Loading />}><Dashboard /></Suspense>
          </ProtectedRoute>
        }
      />
      <Route
        path="/checkout"
        element={
          <ProtectedRoute>
            <Suspense fallback={<Loading />}><Checkout /></Suspense>
          </ProtectedRoute>
        }
      />
      <Route
        path="/profile"
        element={
          <ProtectedRoute>
            <Suspense fallback={<Loading />}><Profile /></Suspense>
          </ProtectedRoute>
        }
      />
      <Route
        path="/my-orders"
        element={
          <ProtectedRoute>
            <Suspense fallback={<Loading />}><MyOrders /></Suspense>
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin"
        element={
          <StaffRoute>
            <Suspense fallback={<Loading />}><AdminDashboard /></Suspense>
          </StaffRoute>
        }
      />
      <Route
        path="/admin/users"
        element={
          <AdminRoute>
            <Suspense fallback={<Loading />}><AdminUsers /></Suspense>
          </AdminRoute>
        }
      />
      <Route
        path="/admin/content"
        element={
          <EditorRoute>
            <Suspense fallback={<Loading />}><AdminContent /></Suspense>
          </EditorRoute>
        }
      />
      <Route
        path="/admin/careers"
        element={
          <HRRoute>
            <Suspense fallback={<Loading />}><AdminCareers /></Suspense>
          </HRRoute>
        }
      />
      <Route path="*" element={<NotFound />} />
      </Routes>
    </>
  )
}
