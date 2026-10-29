const dialog = document.getElementById('location-dialog');
// Keep the modal outside responsive headers, including the hidden desktop nav.
document.body.append(dialog);
const region = document.getElementById('location-region');
const search = document.getElementById('location-search');
const results = document.getElementById('location-results');
const status = document.getElementById('location-status');
const detect = document.getElementById('location-detect');
const branches = document.getElementById('location-branches');
const relocate = document.getElementById('location-relocate');
const required = dialog.dataset.required === 'true';
const closeButton = document.querySelector('[data-location-close]');
const originalOverflow = document.body.style.overflow;
const key = 'bw_location';
const MAX_BRANCH_DISTANCE_KM = 25;
let stores = null;
let selected = null;
let pendingSelection = null;
let position = null;
let requestVersion = 0;
async function updateCurrentArea(coords, version) {
    const label = document.getElementById('location-current-name');
    label.textContent = 'Finding your city or area…';
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), 8000);
    try {
        const params = new URLSearchParams({ latitude: coords.latitude, longitude: coords.longitude, localityLanguage: 'en' });
        const response = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?${params}`, { signal: controller.signal, credentials: 'omit' });
        if (!response.ok) throw new Error('Area lookup failed');
        const area = await response.json();
        const parts = [area.locality || area.city, area.city, area.principalSubdivision, area.countryName]
            .filter(value => typeof value === 'string' && value.trim());
        const name = [...new Set(parts)].join(', ');
        if (!name) throw new Error('Area name unavailable');
        if (version === requestVersion) label.textContent = name;
    } catch {
        if (version === requestVersion) label.textContent = 'Position detected · area name unavailable';
    } finally {
        window.clearTimeout(timeout);
    }
}
function showBranchStep(show) {
    branches.hidden = !show;
    ['location-illustration', 'location-instructions', 'location-privacy'].forEach(id => {
        document.getElementById(id).classList.toggle('hidden', show);
    });
    detect.classList.toggle('hidden', show);
    document.getElementById('location-heading').classList.toggle('text-center', !show);
    document.getElementById('location-help').textContent = show
        ? 'Branches are sorted by distance. Select one to start your order.'
        : 'Enable location to discover branches near you and get started with your order.';
    dialog.classList.toggle('max-w-md', !show);
    dialog.classList.toggle('max-w-xl', show);
}
try { selected = JSON.parse(localStorage.getItem(key) || 'null'); } catch { /* storage optional */ }
// Only a saved branch selection can bypass the picker on future visits.
if (!selected || typeof selected !== 'object' || !selected.id || typeof selected.name !== 'string' || !selected.name.trim()) {
    selected = null;
}

function updateLabels() {
    document.querySelectorAll('[data-location-open]').forEach(button => {
        button.title = selected ? `Current branch: ${selected.name}` : 'Choose a branch';
    });
    document.querySelectorAll('[data-location-label]').forEach(label => {
        label.textContent = selected?.name || 'Choose location';
        label.parentElement.title = selected?.name || 'Choose location';
    });
}
function save(store) {
    selected = store ? { id: store.id, name: store.name, region: store.region } : null;
    try {
        if (selected) localStorage.setItem(key, JSON.stringify(selected));
        else localStorage.removeItem(key);
    } catch { /* Selection still works for this page when storage is blocked. */ }
    updateLabels();
    window.dispatchEvent(new CustomEvent('bw:location-changed', { detail: selected }));
}
function updateContinueButton() {
    closeButton.disabled = required && !pendingSelection;
}
function coordinates(store) {
    return store.latitude != null && store.longitude != null && store.latitude !== '' && store.longitude !== ''
        && Number.isFinite(Number(store.latitude)) && Number.isFinite(Number(store.longitude));
}
function distance(store) {
    if (!position || !coordinates(store)) return Infinity;
    const rad = degrees => degrees * Math.PI / 180;
    const lat = Number(store.latitude), lng = Number(store.longitude);
    const a = Math.sin(rad(lat - position.latitude) / 2) ** 2
        + Math.cos(rad(position.latitude)) * Math.cos(rad(lat)) * Math.sin(rad(lng - position.longitude) / 2) ** 2;
    return 6371 * 2 * Math.asin(Math.sqrt(Math.min(1, a)));
}
function render() {
    if (!stores) return;
    const query = search.value.trim().toLowerCase();
    const matches = stores.filter(store => distance(store) <= MAX_BRANCH_DISTANCE_KM
        && (!region.value || store.region === region.value)
        && [store.name, store.address, store.region].some(value => String(value || '').toLowerCase().includes(query)));
    if (position) matches.sort((a, b) => distance(a) - distance(b));
    results.replaceChildren();
    status.textContent = matches.length
        ? `${matches.length} ${matches.length === 1 ? 'branch' : 'branches'} within ${MAX_BRANCH_DISTANCE_KM} km, nearest first. Select a branch below.`
        : stores.some(coordinates)
            ? `No branches within ${MAX_BRANCH_DISTANCE_KM} km match this area. Try another city or choose All regions.`
            : 'No branches with mapped locations are available right now.';
    for (const store of matches) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'relative shrink-0 rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:border-brand-400 hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500';
        const isSelected = String(pendingSelection?.id) === String(store.id);
        button.setAttribute('aria-pressed', String(isSelected));
        if (isSelected) button.classList.add('bg-brand-50', 'border-brand-400', 'ring-2', 'ring-inset', 'ring-brand-500');
        const name = document.createElement('span');
        name.className = 'block text-sm font-semibold';
        name.textContent = `${store.name}${isSelected ? ' ✓' : ''}`;
        const address = document.createElement('span');
        address.className = 'mt-1 block text-xs text-slate-500';
        address.textContent = store.address;
        const detail = document.createElement('span');
        detail.className = 'mt-1 block text-xs text-brand-600';
        const km = distance(store);
        const mode = store.fulfillment === 'pickup' ? 'Pickup' : store.fulfillment === 'delivery' ? 'Delivery' : 'Pickup and delivery';
        detail.textContent = `${mode}${Number.isFinite(km) ? ` - ${km.toFixed(1)} km away` : ''}`;
        button.append(name, address, detail);
        button.addEventListener('click', () => {
            pendingSelection = store;
            const scrollTop = results.scrollTop;
            render();
            results.scrollTop = scrollTop;
            results.querySelector('[aria-pressed="true"]')?.focus({ preventScroll: true });
        });
        results.append(button);
    }
    const params = new URLSearchParams();
    if (search.value.trim()) params.set('q', search.value.trim());
    if (region.value) params.set('region', region.value);
    document.getElementById('location-map').href = `/stores?${params}`;
    closeButton.textContent = pendingSelection && String(pendingSelection.id) !== String(selected?.id)
        ? 'Confirm branch'
        : selected ? 'Keep current branch' : 'Start ordering';
    updateContinueButton();
}
async function loadStores() {
    if (stores) return true;
    status.textContent = 'Loading branches...';
    try {
        const response = await fetch('/api/stores', { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Could not load branches');
        const data = await response.json();
        if (!Array.isArray(data)) throw new Error('Invalid branches');
        stores = data;
        [...new Set(stores.map(store => store.region).filter(Boolean))].sort().forEach(value => {
            region.add(new Option(value, value));
        });
        if (selected && !stores.some(store => String(store.id) === String(selected.id))) save(null);
        return true;
    } catch {
        status.textContent = 'Could not load branches. Try again, or continue to the menu.';
        return false;
    }
}
async function openLocation() {
    pendingSelection = selected;
    updateContinueButton();
    relocate.disabled = false;
    relocate.querySelector('span').textContent = 'Locate me';
    const version = ++requestVersion;
    let granted = false;
    try {
        granted = (await navigator.permissions.query({ name: 'geolocation' })).state === 'granted';
    } catch { /* Browsers without the Permissions API use the enable button. */ }
    if (version !== requestVersion) return;
    position = null;
    showBranchStep(false);
    detect.disabled = false;
    detect.textContent = 'Enable location';
    status.textContent = '';
    document.getElementById('location-title').textContent = 'Fresh bakes, closer to you';
    closeButton.hidden = required && !selected;
    closeButton.textContent = selected ? 'Keep current branch' : 'Close';
    document.getElementById('location-footer').hidden = required && !selected;
    document.body.style.overflow = 'hidden';
    if (!dialog.open) dialog.showModal();
    if (granted) {
        // Existing browser permission needs no additional enable step.
        ['location-illustration', 'location-instructions', 'location-privacy'].forEach(id => {
            document.getElementById(id).classList.add('hidden');
        });
        detect.classList.add('hidden');
        document.getElementById('location-title').textContent = 'Finding nearby branches';
        document.getElementById('location-help').textContent = 'Location access is enabled. Checking your current location…';
        requestLocation();
    }
}
document.querySelectorAll('[data-location-open]').forEach(button => button.addEventListener('click', openLocation));
closeButton.addEventListener('click', () => {
    if (!required || position || selected) {
        if (pendingSelection) save(pendingSelection);
        dialog.close();
    }
});
dialog.addEventListener('cancel', event => { if (required && !position && !selected) event.preventDefault(); });
dialog.addEventListener('close', () => {
    requestVersion++;
    detect.disabled = false;
    document.body.style.overflow = originalOverflow;
});
region.addEventListener('change', () => render());
search.addEventListener('input', () => render());
document.getElementById('location-clear').addEventListener('click', () => {
    region.value = ''; search.value = '';
    pendingSelection = null;
    save(null); render();
});
function requestLocation(refresh = false) {
    if (!refresh) branches.hidden = true;
    if (!refresh) detect.classList.remove('hidden');
    if (!window.isSecureContext) { status.textContent = 'Location access requires a secure connection. Open this site using HTTPS, then try again.'; return; }
    if (!navigator.geolocation) { status.textContent = 'This browser does not support location access. Please use a browser with location support.'; return; }
    const version = ++requestVersion;
    if (!refresh) position = null;
    if (!refresh) closeButton.hidden = required && !selected;
    relocate.disabled = true;
    relocate.querySelector('span').textContent = 'Locating…';
    detect.disabled = true;
    detect.classList.add('hidden');
    detect.textContent = 'Finding location…';
    status.textContent = 'Finding your location...';
    navigator.geolocation.getCurrentPosition(async location => {
        if (version !== requestVersion) return;
        relocate.disabled = false;
        relocate.querySelector('span').textContent = 'Locate me';
        position = location.coords;
        updateCurrentArea(location.coords, version);
        closeButton.hidden = false;
        document.getElementById('location-footer').hidden = false;
        closeButton.textContent = selected ? 'Keep current branch' : required ? 'Start ordering' : 'Done';
        const loaded = await loadStores();
        if (version !== requestVersion) return;
        detect.disabled = false;
        detect.textContent = 'Try again';
        if (!loaded) { detect.classList.remove('hidden'); return; }
        position = location.coords; // Coordinates stay in memory; only a chosen branch is saved.
        showBranchStep(true);
        detect.textContent = 'Refresh location';
        document.getElementById('location-title').textContent = 'Choose a nearby branch';
        region.value = ''; search.value = '';
        render();
        document.getElementById('location-title').focus({ preventScroll: true });
    }, error => {
        if (version !== requestVersion) return;
        relocate.disabled = false;
        relocate.querySelector('span').textContent = 'Locate me';
        detect.disabled = false;
        if (!refresh) detect.classList.remove('hidden');
        detect.textContent = 'Try again';
        status.textContent = error.code === 1
            ? 'Location access is blocked. Open your browser’s site settings, allow Location for this site, and turn on your device location. Then try again.'
            : error.code === 3
                ? 'Finding your location took too long. Check that device location is on, then try again.'
                : 'Your location is unavailable. Turn on your device location and check your connection, then try again.';
    }, { timeout: 10000, maximumAge: refresh ? 0 : 300000, enableHighAccuracy: refresh });
}
detect.addEventListener('click', () => requestLocation());
relocate.addEventListener('click', () => requestLocation(true));
updateLabels();
if (dialog.dataset.autoOpen === 'true' && !selected) openLocation();
