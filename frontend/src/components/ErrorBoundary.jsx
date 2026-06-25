import { Component } from 'react'

// Catches render-time errors anywhere below it so a single broken component
// shows a friendly fallback instead of unmounting the whole app to a blank
// white screen. Renders its children unchanged (no wrapper DOM node) when
// healthy, so it's hydration-safe even though the prerender doesn't include it.
export default class ErrorBoundary extends Component {
  constructor(props) {
    super(props)
    this.state = { hasError: false }
  }

  static getDerivedStateFromError() {
    return { hasError: true }
  }

  componentDidCatch(error, info) {
    // Surface it for debugging; a real app could forward this to a logger.
    console.error('Unhandled UI error:', error, info)
  }

  handleReload = () => {
    window.location.reload()
  }

  render() {
    if (!this.state.hasError) return this.props.children

    return (
      <div className="flex min-h-screen flex-col items-center justify-center gap-4 bg-navy-50/40 px-6 text-center text-navy-800">
        <div className="flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-2xl">
          ⚠️
        </div>
        <div>
          <h1 className="text-xl font-bold">Something went wrong</h1>
          <p className="mt-1 max-w-sm text-sm text-slate-500">
            An unexpected error stopped this page from loading. Reloading usually fixes it.
          </p>
        </div>
        <div className="flex gap-3">
          <button
            type="button"
            onClick={this.handleReload}
            className="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"
          >
            Reload page
          </button>
          <a
            href="/"
            className="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-navy-700 transition hover:bg-slate-50"
          >
            Go home
          </a>
        </div>
      </div>
    )
  }
}
