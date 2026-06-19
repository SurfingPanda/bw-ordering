// Post-build prerenderer: renders each public route to static HTML, injects
// per-route <head> SEO tags, and writes a sitemap.xml + robots.txt.
// Run automatically by `npm run build` after the client and SSR builds.
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { render } from '../dist-server/entry-server.js'
import { PUBLIC_ROUTES, SITE_URL, canonicalFor, headTagsFor } from '../src/lib/seo.js'

const root = join(dirname(fileURLToPath(import.meta.url)), '..')
const dist = join(root, 'dist')
const template = readFileSync(join(dist, 'index.html'), 'utf-8')

for (const route of PUBLIC_ROUTES) {
  const appHtml = render(route)
  const html = template
    .replace(/<title>.*?<\/title>/s, headTagsFor(route))
    .replace('<div id="root"></div>', `<div id="root">${appHtml}</div>`)
  const outPath =
    route === '/' ? join(dist, 'index.html') : join(dist, route.slice(1), 'index.html')
  mkdirSync(dirname(outPath), { recursive: true })
  writeFileSync(outPath, html)
  console.log('prerendered', route)
}

const urls = PUBLIC_ROUTES.map((r) => `  <url><loc>${canonicalFor(r)}</loc></url>`).join('\n')
writeFileSync(
  join(dist, 'sitemap.xml'),
  `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls}\n</urlset>\n`,
)

writeFileSync(
  join(dist, 'robots.txt'),
  [
    'User-agent: *',
    'Allow: /',
    'Disallow: /admin',
    'Disallow: /dashboard',
    'Disallow: /login',
    'Disallow: /register',
    'Disallow: /complete-profile',
    '',
    `Sitemap: ${SITE_URL}/sitemap.xml`,
    '',
  ].join('\n'),
)
console.log('wrote sitemap.xml + robots.txt')
