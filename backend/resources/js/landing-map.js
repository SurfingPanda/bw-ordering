import maplibregl from 'maplibre-gl'
import 'maplibre-gl/dist/maplibre-gl.css'

// Landing page's store-locator teaser — a real, pannable/zoomable 3D map
// (same terrain + building-extrusion recipe as /stores' full locator, see
// stores.js) instead of a plain search box. Deliberately trimmed: no region
// filters, no search-driven list, no "find nearest" — that whole experience
// still lives on /stores, this is just an inviting live preview. Loaded via
// a lazy dynamic import() only once the section nears the viewport (see
// landing.blade.php), so the ~290KB maplibre-gl bundle never costs anything
// for visitors who don't scroll this far.
//
// Instead of stores.js's popup-per-pin, clicking a marker here updates the
// floating card overlaid on the map (#landing-store-card, server-rendered
// pre-filled with the first store — see landing.blade.php) — mirrors the
// "selected branch" panel /stores itself shows next to its map.

const PH_CENTER = [122.5, 12.0]
const PH_ZOOM = 4.6

const stores = JSON.parse(document.getElementById('landing-stores-data').textContent).map((s) => ({
    ...s,
    latitude: s.latitude === '' || s.latitude == null ? NaN : Number(s.latitude),
    longitude: s.longitude === '' || s.longitude == null ? NaN : Number(s.longitude),
}))

const mapContainer = document.getElementById('landing-store-map')

const dirHref = (address) => `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(address)}`

const nameEl = document.getElementById('landing-store-name')
const addressEl = document.getElementById('landing-store-address')
const hoursEl = document.getElementById('landing-store-hours')
const directionsEl = document.getElementById('landing-store-directions')

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
            // Same Terrarium tiles, but two distinct sources — sharing one
            // source between the hillshade layer and setTerrain() below
            // works, but MapLibre logs a console warning recommending
            // separate sources for better rendering quality, so it gets one
            // each rather than silencing/ignoring the warning.
            'terrain-dem': {
                type: 'raster-dem',
                tiles: ['https://s3.amazonaws.com/elevation-tiles-prod/terrarium/{z}/{x}/{y}.png'],
                tileSize: 256,
                encoding: 'terrarium',
                attribution: 'Terrain: AWS Open Data Terrarium DEM',
            },
            'hillshade-dem': {
                type: 'raster-dem',
                tiles: ['https://s3.amazonaws.com/elevation-tiles-prod/terrarium/{z}/{x}/{y}.png'],
                tileSize: 256,
                encoding: 'terrarium',
                attribution: 'Terrain: AWS Open Data Terrarium DEM',
            },
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
                source: 'hillshade-dem',
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
    attributionControl: false,
})
map.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), 'top-right')
// bottom-left (not stores.js's bottom-right) — that corner is taken by the
// floating store card here.
map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-left')

const collapseAttribution = () => {
    const el = mapContainer.querySelector('.maplibregl-ctrl-attrib')
    el?.classList.remove('maplibregl-compact-show')
    el?.removeAttribute('open')
}
collapseAttribution()
map.on('resize', collapseAttribution)

map.on('load', () => {
    map.setTerrain({ source: 'terrain-dem', exaggeration: 1.4 })

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
        select(store)
    })

    const marker = new maplibregl.Marker({ element: el }).setLngLat([store.longitude, store.latitude]).addTo(map)
    markers.set(store.name, marker)
}

function select(store, fly = true) {
    if (nameEl) nameEl.textContent = store.name
    if (addressEl) addressEl.textContent = store.address
    if (hoursEl) {
        hoursEl.textContent = store.hours || ''
        hoursEl.classList.toggle('hidden', !store.hours)
    }
    if (directionsEl) directionsEl.href = dirHref(store.address)

    for (const [name, marker] of markers) {
        const active = name === store.name
        const el = marker.getElement()
        el.style.background = active ? '#102a4f' : '#ef7d1a'
        el.style.transform = active ? 'scale(1.35)' : 'scale(1)'
        el.style.zIndex = active ? '2' : '1'
    }

    if (fly && Number.isFinite(store.longitude)) {
        map.flyTo({ center: [store.longitude, store.latitude], zoom: 12, pitch: 55, bearing: -18, speed: 0.9, essential: true })
    }
}

if (stores.length) select(stores[0], false)
