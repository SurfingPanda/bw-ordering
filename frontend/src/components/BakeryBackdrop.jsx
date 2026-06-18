// Full-screen photographic backdrop for the auth pages: a bakery interior,
// dimmed with a navy gradient so the white card stays readable.
export default function BakeryBackdrop() {
  return (
    <div className="pointer-events-none absolute inset-0 overflow-hidden">
      <img
        src="/images/bakery-interior.jpg"
        alt=""
        aria-hidden="true"
        className="h-full w-full object-cover"
      />
      {/* navy tint for contrast + brand warmth */}
      <div className="absolute inset-0 bg-gradient-to-br from-navy-900/85 via-navy-900/80 to-navy-800/80" />
      <div className="absolute -bottom-44 -right-24 h-[30rem] w-[30rem] rounded-full bg-brand-600/15 blur-3xl" />
    </div>
  )
}
