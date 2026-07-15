import maplibregl from 'maplibre-gl'
import 'maplibre-gl/dist/maplibre-gl.css'

// "Find a Store" page — 3D store-locator map + list filtering, ported from the
// SPA's Stores.jsx + StoreMap.jsx.
//
// The 3D treatment (tilted camera + terrain relief + extruded buildings):
//   - raster OSM base tiles
//   - a Terrarium raster-DEM source driving real terrain (map.setTerrain) + hillshade
//   - OpenFreeMap vector buildings rendered as fill-extrusion
// On top of that we add one marker per branch and fly to the selected store.
// Store cards are server-rendered in the Blade view; this script only filters,
// selects, and drives the map.

// Default framing: the whole Philippines, slightly tilted.
const PH_CENTER = [122.5, 12.0]
const PH_ZOOM = 4.6

const stores = JSON.parse(document.getElementById('stores-data').textContent).map((s) => ({
    ...s,
    latitude: s.latitude === '' || s.latitude == null ? NaN : Number(s.latitude),
    longitude: s.longitude === '' || s.longitude == null ? NaN : Number(s.longitude),
}))

const mapContainer = document.getElementById('store-map')
const cards = Array.from(document.querySelectorAll('[data-store-card]'))
const regionButtons = Array.from(document.querySelectorAll('[data-region]'))
const searchInput = document.getElementById('store-search')
const countEl = document.getElementById('store-count')
const emptyEl = document.getElementById('stores-empty')
const gridEl = document.getElementById('stores-grid')
const selNameEl = document.getElementById('selected-name')
const selAddressEl = document.getElementById('selected-address')
const selDirectionsEl = document.getElementById('selected-directions')

let region = 'All'
let query = ''
let selectedName = null

const dirHref = (address) => `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(address)}`

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]),
    )
}

function popupHtml(store) {
    return `
    <div style="font-family:system-ui,sans-serif;min-width:180px">
      <strong style="display:block;font-size:13px;color:#102a4f">${escapeHtml(store.name)}</strong>
      <span style="display:block;margin-top:2px;font-size:12px;color:#475569">${escapeHtml(store.address)}</span>
      ${store.hours ? `<span style="display:block;margin-top:2px;font-size:11px;color:#64748b">${escapeHtml(store.hours)}</span>` : ''}
      <a href="${dirHref(store.address)}" target="_blank" rel="noreferrer"
         style="display:inline-block;margin-top:6px;font-size:12px;font-weight:600;color:#ef7d1a;text-decoration:none">
        Get directions →
      </a>
    </div>`
}

/* ---- map ------------------------------------------------------------- */

const map = new maplibregl.Map({
    container: mapContainer,
    style: {
        version: 8,
        sources: {
            osm: {
                type: 'raster',
                tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                tileSize: 256,
                attribution: '© OpenStreetMap contributors',
            },
            'terrain-dem': {
                type: 'raster-dem',
                tiles: ['https://s3.amazonaws.com/elevation-tiles-prod/terrarium/{z}/{x}/{y}.png'],
                tileSize: 256,
                encoding: 'terrarium',
                attribution: 'Terrain: AWS Open Data Terrarium DEM',
            },
            // OpenFreeMap exposes OSM building footprints as vector tiles, rendered
            // natively as MapLibre fill-extrusion layers.
            buildings: {
                type: 'vector',
                url: 'https://tiles.openfreemap.org/planet',
                attribution: 'OpenFreeMap / OpenMapTiles / OpenStreetMap',
            },
        },
        layers: [
            { id: 'osm', type: 'raster', source: 'osm' },
            {
                id: 'hillshade',
                type: 'hillshade',
                source: 'terrain-dem',
                paint: {
                    'hillshade-shadow-color': '#0f172a',
                    'hillshade-highlight-color': '#e0f2fe',
                    'hillshade-accent-color': '#0891b2',
                },
            },
        ],
    },
    center: PH_CENTER,
    zoom: PH_ZOOM,
    pitch: 55,
    bearing: -18,
    maxPitch: 85,
    // Add the source credits ourselves (below) so they start collapsed.
    attributionControl: false,
})
map.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), 'top-right')
map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right')

// The compact attribution opens expanded, and MapLibre re-expands it on every
// map.resize(). Collapse it to the small "ⓘ" button so the license credits
// don't cover the map; clicking the button still toggles the full list open.
const collapseAttribution = () => {
    const el = mapContainer.querySelector('.maplibregl-ctrl-attrib')
    el?.classList.remove('maplibregl-compact-show')
    el?.removeAttribute('open')
}
collapseAttribution()
map.on('resize', collapseAttribution)

map.on('load', () => {
    // Real 3D terrain relief from the DEM.
    map.setTerrain({ source: 'terrain-dem', exaggeration: 1.4 })

    // Extruded 3D buildings (visible once zoomed into a city).
    map.addLayer({
        id: 'building-extrusions',
        type: 'fill-extrusion',
        source: 'buildings',
        'source-layer': 'building',
        minzoom: 13,
        paint: {
            'fill-extrusion-color': ['coalesce', ['get', 'colour'], '#dbeafe'],
            'fill-extrusion-opacity': 0.6,
            'fill-extrusion-height': ['coalesce', ['get', 'render_height'], 8],
            'fill-extrusion-base': ['coalesce', ['get', 'render_min_height'], 0],
        },
    })
})

const markers = new Map()
for (const store of stores) {
    if (!Number.isFinite(store.longitude) || !Number.isFinite(store.latitude)) continue

    const el = document.createElement('button')
    el.type = 'button'
    el.setAttribute('aria-label', store.name)
    el.style.cssText =
        'width:18px;height:18px;border-radius:9999px;border:2px solid #fff;' +
        'background:#ef7d1a;box-shadow:0 1px 4px rgba(0,0,0,.35);cursor:pointer;padding:0'
    el.addEventListener('click', (e) => {
        e.stopPropagation()
        select(store.name)
    })

    const marker = new maplibregl.Marker({ element: el })
        .setLngLat([store.longitude, store.latitude])
        .setPopup(new maplibregl.Popup({ offset: 16 }).setHTML(popupHtml(store)))
        .addTo(map)

    markers.set(store.name, marker)
}

/* ---- filtering + selection ------------------------------------------- */

function visibleStores() {
    const q = query.trim().toLowerCase()
    return stores.filter((s) => {
        const inRegion = region === 'All' || s.region === region
        const matches = !q || s.name.toLowerCase().includes(q) || (s.address || '').toLowerCase().includes(q)
        return inRegion && matches
    })
}

const CARD_ON = ['border-brand-400', 'ring-2', 'ring-brand-500/30']
const CARD_OFF = ['border-slate-100']

function select(name, fly = true) {
    const store = stores.find((s) => s.name === name)
    if (!store) return
    selectedName = name

    cards.forEach((card) => {
        const on = card.dataset.storeCard === name
        CARD_ON.forEach((c) => card.classList.toggle(c, on))
        CARD_OFF.forEach((c) => card.classList.toggle(c, !on))
        const badge = card.querySelector('[data-show-on-map]')
        if (badge) {
            badge.classList.toggle('bg-brand-50', on)
            badge.classList.toggle('text-brand-600', on)
            badge.classList.toggle('bg-navy-50', !on)
            badge.classList.toggle('text-navy-700', !on)
            badge.querySelector('span').textContent = on ? 'Showing on map' : 'Show on map'
        }
    })

    selNameEl.textContent = store.name
    selAddressEl.textContent = store.address
    selDirectionsEl.href = dirHref(store.address)

    for (const [markerName, marker] of markers) {
        const active = markerName === name
        const el = marker.getElement()
        el.style.background = active ? '#102a4f' : '#ef7d1a'
        el.style.transform = active ? 'scale(1.35)' : 'scale(1)'
        el.style.zIndex = active ? '2' : '1'
        if (active && fly && !marker.getPopup().isOpen()) marker.togglePopup()
    }

    if (fly && Number.isFinite(store.longitude)) {
        map.flyTo({
            center: [store.longitude, store.latitude],
            zoom: 15.5,
            pitch: 60,
            bearing: -18,
            speed: 0.9,
            essential: true,
        })
    }
}

function applyFilters() {
    const visible = visibleStores()
    const names = new Set(visible.map((s) => s.name))

    cards.forEach((card) => card.classList.toggle('hidden', !names.has(card.dataset.storeCard)))
    for (const [name, marker] of markers) {
        marker.getElement().style.display = names.has(name) ? '' : 'none'
    }

    countEl.textContent = `${visible.length} ${visible.length === 1 ? 'store' : 'stores'} found`
    emptyEl.classList.toggle('hidden', visible.length > 0)
    gridEl.classList.toggle('hidden', visible.length === 0)

    // Selected store always falls back to the first one currently visible.
    if (visible.length && !names.has(selectedName)) select(visible[0].name, false)

    map.resize() // layout may have shifted (empty state toggled)
}

regionButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
        region = btn.dataset.region
        const ON = ['bg-gradient-to-r', 'from-brand-500', 'to-brand-600', 'text-white', 'shadow-md', 'shadow-brand-500/30']
        const OFF = ['border', 'border-slate-200', 'bg-white', 'text-navy-700']
        regionButtons.forEach((b) => {
            const on = b === btn
            ON.forEach((c) => b.classList.toggle(c, on))
            OFF.forEach((c) => b.classList.toggle(c, !on))
        })
        applyFilters()
    })
})

searchInput.addEventListener('input', () => {
    query = searchInput.value
    applyFilters()
})

cards.forEach((card) => {
    card.addEventListener('click', (e) => {
        if (e.target.closest('a')) return // let the Directions link through
        select(card.dataset.storeCard)
    })
})

/* ---- find nearest store ----------------------------------------------- */
// Geolocate the visitor, pick the closest branch (great-circle distance),
// select it, and frame both points on the map. Needs HTTPS or localhost.

const nearestBtn = document.getElementById('find-nearest')
const nearestStatus = document.getElementById('nearest-status')
let userMarker = null

function setNearestStatus(text) {
    nearestStatus.textContent = text
    nearestStatus.classList.toggle('hidden', !text)
}

function haversineKm(lat1, lng1, lat2, lng2) {
    const toRad = (d) => (d * Math.PI) / 180
    const a =
        Math.sin(toRad(lat2 - lat1) / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(toRad(lng2 - lng1) / 2) ** 2
    return 2 * 6371 * Math.asin(Math.sqrt(a))
}

nearestBtn.addEventListener('click', () => {
    if (!navigator.geolocation) {
        setNearestStatus('Location is not supported by this browser.')
        return
    }
    nearestBtn.disabled = true
    setNearestStatus('Locating you…')

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            nearestBtn.disabled = false
            const { latitude: lat, longitude: lng } = pos.coords

            // Blue "you are here" dot.
            if (!userMarker) {
                const dot = document.createElement('div')
                dot.style.cssText =
                    'width:16px;height:16px;border-radius:9999px;border:3px solid #fff;' +
                    'background:#2563eb;box-shadow:0 0 0 6px rgba(37,99,235,.25)'
                userMarker = new maplibregl.Marker({ element: dot }).setLngLat([lng, lat]).addTo(map)
            } else {
                userMarker.setLngLat([lng, lat])
            }

            const pinned = stores.filter((s) => Number.isFinite(s.latitude) && Number.isFinite(s.longitude))
            if (!pinned.length) {
                setNearestStatus('No stores have map pins yet.')
                return
            }
            const nearest = pinned.reduce((best, s) =>
                haversineKm(lat, lng, s.latitude, s.longitude) < haversineKm(lat, lng, best.latitude, best.longitude) ? s : best)
            const km = haversineKm(lat, lng, nearest.latitude, nearest.longitude)

            // Clear any filter that would hide the nearest store, then select
            // it ("All" click also resets the region buttons' styling).
            searchInput.value = ''
            query = ''
            regionButtons.find((b) => b.dataset.region === 'All')?.click()
            select(nearest.name, false)
            setNearestStatus(`Nearest store: ${nearest.name} — ${km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(1) + ' km'} away`)

            // Frame you + the store together, and bring its card into view.
            map.fitBounds(
                new maplibregl.LngLatBounds([lng, lat], [lng, lat]).extend([nearest.longitude, nearest.latitude]),
                { padding: 80, maxZoom: 14, pitch: 55, bearing: -18, essential: true },
            )
            cards.find((c) => c.dataset.storeCard === nearest.name)?.scrollIntoView({ behavior: 'smooth', block: 'center' })
        },
        (err) => {
            nearestBtn.disabled = false
            setNearestStatus(err.code === err.PERMISSION_DENIED
                ? 'Location permission denied — allow location access and try again.'
                : 'Could not get your location. Please try again.')
        },
        { enableHighAccuracy: true, timeout: 10000 },
    )
})

if (stores.length) select(stores[0].name, false)
