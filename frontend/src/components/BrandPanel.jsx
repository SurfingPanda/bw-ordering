import { useEffect, useState } from 'react'
import { DEFAULT_CONTENT, getCachedContent, getSiteContent } from '../lib/content'

// The navy/orange bw Superbakeshop showcase panel shown on the left
// of the auth screens on large viewports. Logo / text / image are editable
// from the Site Editor (Login Page tab → content.authPanel).
export default function BrandPanel({ content: controlled }) {
  const [fetched, setFetched] = useState(getCachedContent)

  useEffect(() => {
    if (!controlled) getSiteContent().then(setFetched)
  }, [controlled])

  const content = controlled || fetched
  const ap = content.authPanel || DEFAULT_CONTENT.authPanel

  return (
    <div className="relative hidden flex-col overflow-hidden bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 lg:flex">
      {/* faint baking-icon pattern */}
      <div className="pointer-events-none absolute inset-0 select-none text-5xl leading-[3.5rem] opacity-[0.06]">
        <div className="absolute left-6 top-10">🥐</div>
        <div className="absolute right-10 top-16">🧁</div>
        <div className="absolute left-16 top-40">🍞</div>
        <div className="absolute right-6 top-44">🥖</div>
        <div className="absolute left-8 top-72">🥨</div>
        <div className="absolute right-16 top-80">🍰</div>
      </div>

      {/* brand lock-up */}
      <div className="relative z-10 flex flex-col items-center px-10 pt-12 text-center text-white">
        <img
          src={ap.logo}
          alt="BW Superbakeshop"
          className="h-36 w-auto drop-shadow-lg"
        />
        {ap.tagline && (
          <p className="mt-6 text-sm font-medium text-navy-50/90">{ap.tagline}</p>
        )}
        {ap.script && <p className="font-script text-xl text-brand-400">{ap.script}</p>}
      </div>

      {/* showcase image — transparent PNG so it blends into the navy panel */}
      {ap.image && (
        <img
          src={ap.image}
          alt=""
          className="relative z-10 mt-auto w-full max-w-xs self-center px-8 pb-8 drop-shadow-2xl"
        />
      )}

      {/* gentle wave divider toward the form panel — flush at top & bottom
          corners, bulging left only through the middle */}
      <svg
        className="absolute right-[-1px] top-0 h-full w-10 text-white"
        viewBox="0 0 40 600"
        preserveAspectRatio="none"
        fill="currentColor"
      >
        <path d="M40 0 C40 130 8 200 8 300 C8 400 40 470 40 600 Z" />
      </svg>
    </div>
  )
}
