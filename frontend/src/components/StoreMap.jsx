import { useEffect, useRef } from 'react'
import maplibregl from 'maplibre-gl'
import 'maplibre-gl/dist/maplibre-gl.css'

// 3D store-locator map built on MapLibre GL.
//
// The 3D treatment (tilted camera + terrain relief + extruded buildings) is
// ported from the Flood Watch project:
//   - raster OSM base tiles
//   - a Terrarium raster-DEM source driving real terrain (map.setTerrain) + hillshade
//   - OpenFreeMap vector buildings rendered as fill-extrusion
// On top of that we add one marker per branch and fly to the selected store.

// Default framing: the whole Philippines, slightly tilted.
const PH_CENTER = [122.5, 12.0]
const PH_ZOOM = 4.6

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]),
  )
}

function popupHtml(store) {
  const dir = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(store.address)}`
  return `
    <div style="font-family:system-ui,sans-serif;min-width:180px">
      <strong style="display:block;font-size:13px;color:#102a4f">${escapeHtml(store.name)}</strong>
      <span style="display:block;margin-top:2px;font-size:12px;color:#475569">${escapeHtml(store.address)}</span>
      ${store.hours ? `<span style="display:block;margin-top:2px;font-size:11px;color:#64748b">${escapeHtml(store.hours)}</span>` : ''}
      <a href="${dir}" target="_blank" rel="noreferrer"
         style="display:inline-block;margin-top:6px;font-size:12px;font-weight:600;color:#ef7d1a;text-decoration:none">
        Get directions →
      </a>
    </div>`
}

export default function StoreMap({ stores = [], selected, onSelect }) {
  const containerRef = useRef(null)
  const mapRef = useRef(null)
  const markersRef = useRef(new Map())
  const onSelectRef = useRef(onSelect)

  useEffect(() => {
    onSelectRef.current = onSelect
  }, [onSelect])

  // Initialise the map once.
  useEffect(() => {
    if (!containerRef.current || mapRef.current) return

    const map = new maplibregl.Map({
      container: containerRef.current,
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
    mapRef.current = map
    map.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), 'top-right')
    map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right')

    // The compact attribution opens expanded, and MapLibre re-expands it on every
    // map.resize(). Collapse it to the small "ⓘ" button so the license credits
    // don't cover the map; clicking the button still toggles the full list open.
    const collapseAttribution = () => {
      const el = containerRef.current?.querySelector('.maplibregl-ctrl-attrib')
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

    return () => {
      map.remove()
      mapRef.current = null
      markersRef.current.clear()
    }
  }, [])

  // Sync markers whenever the store list changes.
  useEffect(() => {
    const map = mapRef.current
    if (!map) return

    // Remove markers for stores that are gone.
    for (const [name, marker] of markersRef.current) {
      if (!stores.some((s) => s.name === name)) {
        marker.remove()
        markersRef.current.delete(name)
      }
    }

    // Add markers for new stores.
    for (const store of stores) {
      if (markersRef.current.has(store.name)) continue
      if (!Number.isFinite(store.longitude) || !Number.isFinite(store.latitude)) continue

      const el = document.createElement('button')
      el.type = 'button'
      el.setAttribute('aria-label', store.name)
      el.style.cssText =
        'width:18px;height:18px;border-radius:9999px;border:2px solid #fff;' +
        'background:#ef7d1a;box-shadow:0 1px 4px rgba(0,0,0,.35);cursor:pointer;padding:0'
      el.addEventListener('click', (e) => {
        e.stopPropagation()
        onSelectRef.current?.(store.name)
      })

      const marker = new maplibregl.Marker({ element: el })
        .setLngLat([store.longitude, store.latitude])
        .setPopup(new maplibregl.Popup({ offset: 16 }).setHTML(popupHtml(store)))
        .addTo(map)

      markersRef.current.set(store.name, marker)
    }
  }, [stores])

  // Fly to (and highlight) the selected store.
  useEffect(() => {
    const map = mapRef.current
    if (!map || !selected || !Number.isFinite(selected.longitude)) return

    map.flyTo({
      center: [selected.longitude, selected.latitude],
      zoom: 15.5,
      pitch: 60,
      bearing: -18,
      speed: 0.9,
      essential: true,
    })

    for (const [name, marker] of markersRef.current) {
      const active = name === selected.name
      const el = marker.getElement()
      el.style.background = active ? '#102a4f' : '#ef7d1a'
      el.style.transform = active ? 'scale(1.35)' : 'scale(1)'
      el.style.zIndex = active ? '2' : '1'
      if (active) marker.togglePopup()
    }
  }, [selected])

  return <div ref={containerRef} className="h-72 w-full lg:h-[26rem]" />
}
