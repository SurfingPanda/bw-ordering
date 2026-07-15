import { StrictMode } from 'react'
import { renderToString } from 'react-dom/server'
import { StaticRouter } from 'react-router-dom'
import App from './App.jsx'
import { AuthProvider } from './context/AuthContext.jsx'
import { StaticRevealContext } from './components/Reveal.jsx'

// Server entry used by scripts/prerender.mjs to produce static HTML per route.
// StaticRevealContext=true keeps reveal-on-scroll sections rendered (visible)
// rather than waiting on an IntersectionObserver that doesn't exist server-side.
export function render(url) {
  return renderToString(
    <StrictMode>
      <StaticRouter location={url}>
        <AuthProvider>
          <StaticRevealContext.Provider value={true}>
            <App />
          </StaticRevealContext.Provider>
        </AuthProvider>
      </StaticRouter>
    </StrictMode>,
  )
}
