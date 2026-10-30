import 'maplibre-gl/dist/maplibre-gl.css'

const container = document.getElementById('delivery-address-map')
if (container) {
    const locateButton = document.getElementById('locate-delivery-address')
    const locateLabel = locateButton.querySelector('span')
    const status = document.getElementById('delivery-address-map-status')
    const latitudeInput = document.getElementById('new-address-latitude')
    const longitudeInput = document.getElementById('new-address-longitude')
    let map = null
    let marker = null
    let maplibregl = null
    let loading = null

    function readPin() {
        if (!latitudeInput.value || !longitudeInput.value) return null
        const latitude = Number(latitudeInput.value)
        const longitude = Number(longitudeInput.value)
        return Number.isFinite(latitude) && Number.isFinite(longitude)
            && Math.abs(latitude) <= 90 && Math.abs(longitude) <= 180
            ? { latitude, longitude }
            : null
    }

    function updatePin(latitude, longitude, moveMap = false) {
        latitudeInput.value = latitude.toFixed(7)
        longitudeInput.value = longitude.toFixed(7)
        const coordinates = [longitude, latitude]
        if (!marker) {
            marker = new maplibregl.Marker({ color: '#f97316', draggable: true })
                .setLngLat(coordinates)
                .addTo(map)
            marker.on('dragend', () => {
                const point = marker.getLngLat()
                updatePin(point.lat, point.lng)
            })
        } else {
            marker.setLngLat(coordinates)
        }
        status.textContent = `Pinned location: ${latitude.toFixed(5)}, ${longitude.toFixed(5)}. Drag the pin to adjust.`
        if (moveMap) map.flyTo({ center: coordinates, zoom: 16 })
    }

    async function initializeMap() {
        if (map) return
        if (!loading) {
            loading = import('maplibre-gl').then(module => {
                maplibregl = module.default
                map = new maplibregl.Map({
                    container,
                    style: {
                        version: 8,
                        sources: {
                            osm: {
                                type: 'raster',
                                tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                                tileSize: 256,
                                attribution: '© OpenStreetMap contributors',
                            },
                        },
                        layers: [{ id: 'osm', type: 'raster', source: 'osm' }],
                    },
                    center: [121.774, 12.8797],
                    zoom: 5,
                    attributionControl: false,
                })
                map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right')
                map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right')
                map.on('click', event => updatePin(event.lngLat.lat, event.lngLat.lng))
                map.on('load', () => {
                    const pin = readPin()
                    if (pin) updatePin(pin.latitude, pin.longitude, true)
                })
            }).catch(() => {
                status.textContent = 'Could not load the map. You can still use Locate me and enter your address below.'
                loading = null
            })
        }
        await loading
        if (map) requestAnimationFrame(() => {
            map.resize()
            const pin = readPin()
            if (pin) {
                updatePin(pin.latitude, pin.longitude)
            } else {
                marker?.remove()
                marker = null
                status.textContent = 'Tap the map to drop a pin.'
                map.flyTo({ center: [121.774, 12.8797], zoom: 5 })
            }
        })
    }

    document.addEventListener('bw:address-open', initializeMap)
    locateButton.addEventListener('click', async () => {
        if (!navigator.geolocation) {
            status.textContent = 'Location is not supported by this browser. Tap the map to place a pin.'
            return
        }
        locateButton.disabled = true
        locateLabel.textContent = 'Locating…'
        status.textContent = 'Finding your current location…'
        try {
            await initializeMap()
            if (!map) {
                locateButton.disabled = false
                locateLabel.textContent = 'Locate me'
                return
            }
            navigator.geolocation.getCurrentPosition(position => {
                updatePin(position.coords.latitude, position.coords.longitude, true)
                document.dispatchEvent(new CustomEvent('bw:address-pin-located', {
                    detail: { latitude: position.coords.latitude, longitude: position.coords.longitude },
                }))
                locateButton.disabled = false
                locateLabel.textContent = 'Locate me'
            }, error => {
                locateButton.disabled = false
                locateLabel.textContent = 'Locate me'
                status.textContent = error.code === error.PERMISSION_DENIED
                    ? 'Location access was denied. Allow it in your browser settings, or tap the map to place a pin.'
                    : 'Could not find your location. Try again or tap the map to place a pin.'
            }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 })
        } catch {
            locateButton.disabled = false
            locateLabel.textContent = 'Locate me'
        }
    })
}
