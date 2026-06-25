import { useEffect, useState } from 'react'

// Lightweight auto-playing carousel — no dependencies. Cross-fades between
// arbitrary slide nodes, auto-advances, pauses on hover, and exposes prev/next
// arrows plus clickable dots. Honors prefers-reduced-motion (no autoplay).
//
// Pass `slides` as an array of React nodes (each becomes one full slide).
export default function Carousel({
  slides = [],
  interval = 5000,
  className = '',
  arrows = true,
  dots = true,
}) {
  const [index, setIndex] = useState(0)
  const [paused, setPaused] = useState(false)
  const count = slides.length

  const go = (next) => setIndex(() => (next + count) % count)

  useEffect(() => {
    if (paused || count <= 1) return
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    if (reduce) return

    const id = setInterval(() => setIndex((i) => (i + 1) % count), interval)
    return () => clearInterval(id)
  }, [paused, count, interval])

  if (count === 0) return null

  return (
    <div
      className={`group relative ${className}`}
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
    >
      {/* slides — active one sits in normal flow so the carousel sizes to it,
          the rest are stacked behind it and faded out */}
      {slides.map((slide, i) => (
        <div
          key={i}
          aria-hidden={i !== index}
          className={`transition-opacity duration-700 ease-out ${
            i === index
              ? 'opacity-100'
              : 'pointer-events-none absolute inset-0 opacity-0'
          }`}
        >
          {slide}
        </div>
      ))}

      {count > 1 && (
        <>
          {arrows && (
            <>
              <button
                type="button"
                onClick={() => go(index - 1)}
                aria-label="Previous slide"
                className="absolute left-3 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-navy-800 opacity-0 shadow-md transition hover:bg-white group-hover:opacity-100"
              >
                <ChevronIcon className="h-5 w-5 rotate-180" />
              </button>
              <button
                type="button"
                onClick={() => go(index + 1)}
                aria-label="Next slide"
                className="absolute right-3 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-navy-800 opacity-0 shadow-md transition hover:bg-white group-hover:opacity-100"
              >
                <ChevronIcon className="h-5 w-5" />
              </button>
            </>
          )}

          {/* dots */}
          {dots && (
          <div className="absolute inset-x-0 bottom-5 z-20 flex justify-center gap-2">
            {slides.map((_, i) => (
              <button
                key={i}
                type="button"
                onClick={() => setIndex(i)}
                aria-label={`Go to slide ${i + 1}`}
                className={`h-2 rounded-full transition-all ${
                  i === index ? 'w-6 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'
                }`}
              />
            ))}
          </div>
          )}
        </>
      )}
    </div>
  )
}

function ChevronIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <polyline points="9 18 15 12 9 6" />
    </svg>
  )
}
