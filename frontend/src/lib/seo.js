import { useEffect } from 'react'

// Central SEO config. CHANGE SITE_URL to your real production domain — it drives
// canonical URLs, the sitemap, and Open Graph/Twitter tags.
export const SITE_URL = 'https://www.bwsuperbakeshop.com'
const OG_IMAGE = `${SITE_URL}/images/promo-cake.png`

// Per-route metadata. Keys are the routes we prerender + list in the sitemap.
export const SEO = {
  '/': {
    title: 'BW Superbakeshop',
    description:
      'Order freshly baked cakes, breads, and pastries from bw Superbakeshop. Nationwide branches, custom cakes, and delivery.',
  },
  '/menu': {
    title: 'Order Online — Menu | bw Superbakeshop',
    description:
      'Browse the full bw Superbakeshop menu — cakes, breads, pastries, drinks and more — and order online for pickup or delivery.',
  },
  '/franchise': {
    title: 'Partner with us | bw Superbakeshop',
    description:
      'Own a bw Superbakeshop. Partner with a trusted bakeshop brand — training, supply chain, and marketing support included.',
  },
  '/careers': {
    title: 'Careers — Join Our Team | bw Superbakeshop',
    description:
      'Build your career with bw Superbakeshop. Explore open roles in baking, retail, logistics, and more.',
  },
  '/careers/openings': {
    title: 'Open Positions — Careers | bw Superbakeshop',
    description:
      'Browse all open positions at bw Superbakeshop. Filter by department and apply online today.',
  },
  '/stores': {
    title: 'Store Locator — Find a Branch | bw Superbakeshop',
    description:
      'Find the bw Superbakeshop branch nearest you. Branches nationwide with directions, hours, and contact details.',
  },
}

export const PUBLIC_ROUTES = Object.keys(SEO)

export function seoFor(pathname) {
  return SEO[pathname] || SEO['/']
}

export function canonicalFor(pathname) {
  return SITE_URL + (pathname === '/' ? '' : pathname)
}

const esc = (s) =>
  String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')

const LOGO = `${SITE_URL}/images/logo%20(1).png`
const SAME_AS = ['https://www.facebook.com/bwsuperbakeshop']
const BREADCRUMB_LABEL = {
  '/menu': 'Menu',
  '/franchise': 'Partner with us',
  '/careers': 'Careers',
  '/stores': 'Stores',
}

// schema.org structured data for a route, as a JSON-LD <script>. A single
// @graph carries the Bakery (LocalBusiness), the WebSite, this WebPage, and a
// breadcrumb trail. Returned ready to drop into <head>.
export function structuredDataFor(pathname) {
  const { title, description } = seoFor(pathname)
  const canonical = canonicalFor(pathname)

  const graph = [
    {
      '@type': ['Bakery', 'LocalBusiness'],
      '@id': `${SITE_URL}/#bakery`,
      name: 'bw Superbakeshop',
      url: SITE_URL,
      logo: LOGO,
      image: `${SITE_URL}/images/promo-cake.png`,
      description: SEO['/'].description,
      servesCuisine: 'Bakery',
      priceRange: '₱₱',
      sameAs: SAME_AS,
    },
    {
      '@type': 'WebSite',
      '@id': `${SITE_URL}/#website`,
      url: SITE_URL,
      name: 'bw Superbakeshop',
      publisher: { '@id': `${SITE_URL}/#bakery` },
    },
    {
      '@type': 'WebPage',
      '@id': `${canonical}#webpage`,
      url: canonical,
      name: title,
      description,
      isPartOf: { '@id': `${SITE_URL}/#website` },
      about: { '@id': `${SITE_URL}/#bakery` },
    },
  ]

  if (pathname !== '/' && BREADCRUMB_LABEL[pathname]) {
    graph.push({
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Home', item: SITE_URL },
        { '@type': 'ListItem', position: 2, name: BREADCRUMB_LABEL[pathname], item: canonical },
      ],
    })
  }

  const json = JSON.stringify({ '@context': 'https://schema.org', '@graph': graph }).replace(
    /</g,
    '\\u003c',
  )
  return `<script type="application/ld+json">${json}</script>`
}

// Head tags for a route as an HTML string — used by the prerenderer (Node) to
// build the static <head>. Kept free of browser APIs so it runs server-side.
export function headTagsFor(pathname) {
  const { title, description } = seoFor(pathname)
  const canonical = canonicalFor(pathname)
  return [
    `<title>${esc(title)}</title>`,
    `<meta name="description" content="${esc(description)}" />`,
    `<link rel="canonical" href="${esc(canonical)}" />`,
    `<meta property="og:type" content="website" />`,
    `<meta property="og:site_name" content="bw Superbakeshop" />`,
    `<meta property="og:title" content="${esc(title)}" />`,
    `<meta property="og:description" content="${esc(description)}" />`,
    `<meta property="og:url" content="${esc(canonical)}" />`,
    `<meta property="og:image" content="${esc(OG_IMAGE)}" />`,
    `<meta name="twitter:card" content="summary_large_image" />`,
    `<meta name="twitter:title" content="${esc(title)}" />`,
    `<meta name="twitter:description" content="${esc(description)}" />`,
    `<meta name="twitter:image" content="${esc(OG_IMAGE)}" />`,
    structuredDataFor(pathname),
  ].join('\n    ')
}

// Client-side: keep the document head correct as the SPA navigates. Updates the
// same tags the prerenderer injected (by selector) so there are no duplicates.
function upsertMeta(attr, key, content) {
  let el = document.head.querySelector(`meta[${attr}="${key}"]`)
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute(attr, key)
    document.head.appendChild(el)
  }
  el.setAttribute('content', content)
}

function upsertLink(rel, href) {
  let el = document.head.querySelector(`link[rel="${rel}"]`)
  if (!el) {
    el = document.createElement('link')
    el.setAttribute('rel', rel)
    document.head.appendChild(el)
  }
  el.setAttribute('href', href)
}

export function useSeo(pathname, enabled = true) {
  useEffect(() => {
    if (!enabled) return
    const { title, description } = seoFor(pathname)
    const canonical = canonicalFor(pathname)
    document.title = title
    upsertMeta('name', 'description', description)
    upsertLink('canonical', canonical)
    upsertMeta('property', 'og:title', title)
    upsertMeta('property', 'og:description', description)
    upsertMeta('property', 'og:url', canonical)
  }, [pathname, enabled])
}
