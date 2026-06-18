import { useEffect, useRef, useState } from 'react'

// Reveal-on-scroll wrapper. Fades + slides its children in when they enter the
// viewport and back out when they leave, so it re-animates on scroll up *and*
// down. Honors prefers-reduced-motion. No dependencies — just IntersectionObserver.
export default function Reveal({
  children,
  direction = 'up',
  delay = 0,
  className = '',
  as: Tag = 'div',
}) {
  const ref = useRef(null)
  const [visible, setVisible] = useState(false)

  useEffect(() => {
    const el = ref.current
    if (!el) return

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    if (reduce) {
      setVisible(true)
      return
    }

    const observer = new IntersectionObserver(
      ([entry]) => setVisible(entry.isIntersecting),
      { threshold: 0.15, rootMargin: '0px 0px -10% 0px' },
    )
    observer.observe(el)
    return () => observer.disconnect()
  }, [])

  const offsets = {
    up: 'translate-y-8',
    down: '-translate-y-8',
    left: 'translate-x-8',
    right: '-translate-x-8',
  }

  return (
    <Tag
      ref={ref}
      style={{ transitionDelay: `${delay}ms` }}
      className={[
        'transition-all duration-700 ease-out motion-reduce:transition-none',
        visible ? 'translate-x-0 translate-y-0 opacity-100' : `${offsets[direction]} opacity-0`,
        className,
      ].join(' ')}
    >
      {children}
    </Tag>
  )
}
