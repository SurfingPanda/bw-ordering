import { useEffect, useRef, useState } from 'react'

// Image with a skeleton shimmer while it loads, fading in once ready.
// `wrapperClassName` sizes/shapes the box; `className` tweaks the <img> itself
// (e.g. grayscale for sold-out). Keeps native lazy-loading + async decoding.
//
// SSR-safe: initial state is `loaded=false` (matches the prerendered HTML), and
// the cache reconciliation runs in an effect (not during render).
export default function LazyImage({ src, alt, wrapperClassName = '', className = '' }) {
  const [loaded, setLoaded] = useState(false)
  const imgRef = useRef(null)

  // A cached image can finish loading before React attaches onLoad below, which
  // would leave it stuck at opacity-0. Reconcile against the DOM on mount / src
  // change so it never gets stuck invisible.
  useEffect(() => {
    const el = imgRef.current
    if (el?.complete && el.naturalWidth > 0) setLoaded(true)
    else setLoaded(false)
  }, [src])

  return (
    <span className={`relative block overflow-hidden bg-slate-100 ${wrapperClassName}`}>
      <span
        aria-hidden="true"
        className={`pointer-events-none absolute inset-0 animate-pulse bg-gradient-to-br from-slate-200 to-slate-100 transition-opacity duration-500 ${
          loaded ? 'opacity-0' : 'opacity-100'
        }`}
      />
      <img
        ref={imgRef}
        src={src}
        alt={alt}
        loading="lazy"
        decoding="async"
        onLoad={() => setLoaded(true)}
        onError={() => setLoaded(true)}
        className={`relative h-full w-full object-cover transition-opacity duration-500 ${
          loaded ? 'opacity-100' : 'opacity-0'
        } ${className}`}
      />
    </span>
  )
}
