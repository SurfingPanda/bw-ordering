import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'

// React Router preserves the window scroll position across navigations, so
// clicking a link near the bottom of a page (e.g. the footer "Careers" link)
// lands you at the bottom of the next page. Reset to the top on every pathname
// change. Skips when the URL has a hash (so in-page anchors still work) and is
// keyed on pathname only, so search-param updates (e.g. Menu's ?add= cleanup)
// don't yank the page to the top.
export default function ScrollToTop() {
  const { pathname, hash } = useLocation()

  useEffect(() => {
    if (hash) return
    window.scrollTo(0, 0)
  }, [pathname, hash])

  return null
}
