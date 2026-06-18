// The navy/orange "Bakery Ordering System" showcase panel shown on the left
// of the auth screens on large viewports.
export default function BrandPanel() {
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
        <div className="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20">
          <ChefHat className="h-9 w-9 text-brand-400" />
        </div>
        <h1 className="font-script text-5xl text-white drop-shadow-sm">Bakery</h1>
        <div className="mt-1 flex items-center gap-3 text-[0.7rem] font-semibold tracking-[0.35em] text-brand-400">
          <span className="h-px w-8 bg-brand-400/60" />
          ORDERING SYSTEM
          <span className="h-px w-8 bg-brand-400/60" />
        </div>
        <p className="mt-6 text-sm font-medium text-navy-50/90">
          Freshly baked. Made with love.
        </p>
        <p className="font-script text-xl text-brand-400">Ordered with ease.</p>
      </div>

      {/* hero photo (warm fallback shows if the image is slow to load) */}
      <div className="relative z-10 mt-auto h-64 w-full bg-gradient-to-br from-brand-500 to-brand-600">
        <div className="absolute inset-x-0 top-0 z-10 h-24 bg-gradient-to-b from-navy-900/90 to-transparent" />
        <img
          src="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80"
          alt="Assorted freshly baked bread"
          className="h-64 w-full object-cover"
        />
      </div>

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

function ChefHat({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M7 21h10a1 1 0 0 0 1-1v-5H6v5a1 1 0 0 0 1 1Z" />
      <path d="M18.5 4.5a3.5 3.5 0 0 0-3.2 2.07 3.5 3.5 0 0 0-6.6 0A3.5 3.5 0 1 0 6 13.5h12a3.5 3.5 0 0 0 .5-9Z" />
    </svg>
  )
}
