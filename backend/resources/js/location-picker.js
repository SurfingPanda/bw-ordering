const dialog = document.getElementById('location-dialog');
// Keep the modal outside responsive headers, including the hidden desktop nav.
document.body.append(dialog);
const region = document.getElementById('location-region');
const search = document.getElementById('location-search');
const results = document.getElementById('location-results');
const status = document.getElementById('location-status');
const detect = document.getElementById('location-detect');
const key = 'bw_location';
let stores = null;
let selected = null;
let position = null;
let requestVersion = 0;
try { selected = JSON.parse(localStorage.getItem(key) || 'null'); } catch { /* storage optional */ }

function updateLabels() {
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
    const matches = stores.filter(store => (!region.value || store.region === region.value)
        && [store.name, store.address, store.region].some(value => String(value || '').toLowerCase().includes(query)));
    if (position) matches.sort((a, b) => distance(a) - distance(b));
    results.replaceChildren();
    status.textContent = matches.length
        ? `${matches.length} ${matches.length === 1 ? 'branch' : 'branches'}${position ? ', nearest first' : ''}. Select a branch below.`
        : 'No branches match this area. Try another city or choose All regions.';
    for (const store of matches) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'rounded-xl border border-slate-200 p-3 text-left transition hover:border-brand-400 focus-visible:ring-2 focus-visible:ring-brand-500';
        button.setAttribute('aria-pressed', String(String(selected?.id) === String(store.id)));
        if (String(selected?.id) === String(store.id)) button.classList.add('bg-brand-50', 'border-brand-400');
        const name = document.createElement('span');
        name.className = 'block text-sm font-semibold';
        name.textContent = store.name;
        const address = document.createElement('span');
        address.className = 'mt-1 block text-xs text-slate-500';
        address.textContent = store.address;
        const detail = document.createElement('span');
        detail.className = 'mt-1 block text-xs text-brand-600';
        const km = distance(store);
        const mode = store.fulfillment === 'pickup' ? 'Pickup' : store.fulfillment === 'delivery' ? 'Delivery' : 'Pickup and delivery';
        detail.textContent = `${mode}${Number.isFinite(km) ? ` - ${km.toFixed(1)} km away` : ''}`;
        button.append(name, address, detail);
        button.addEventListener('click', () => { save(store); dialog.close(); });
        results.append(button);
    }
    const params = new URLSearchParams();
    if (search.value.trim()) params.set('q', search.value.trim());
    if (region.value) params.set('region', region.value);
    document.getElementById('location-map').href = `/stores?${params}`;
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
        status.textContent = 'Could not load branches. Close and reopen to retry, or use the store map.';
        return false;
    }
}
document.querySelectorAll('[data-location-open]').forEach(button => button.addEventListener('click', async () => {
    dialog.showModal();
    if (await loadStores()) render();
}));
document.querySelector('[data-location-close]').addEventListener('click', () => dialog.close());
dialog.addEventListener('close', () => { requestVersion++; detect.disabled = false; });
region.addEventListener('change', () => { requestVersion++; detect.disabled = false; position = null; render(); });
search.addEventListener('input', () => { requestVersion++; detect.disabled = false; position = null; render(); });
document.getElementById('location-clear').addEventListener('click', () => {
    requestVersion++; detect.disabled = false; position = null; region.value = ''; search.value = '';
    save(null); render();
});
detect.addEventListener('click', async () => {
    if (!navigator.geolocation) { status.textContent = 'Location is unavailable. Choose a region or search your city below.'; return; }
    if (!await loadStores()) return;
    const version = ++requestVersion;
    detect.disabled = true;
    status.textContent = 'Finding your location...';
    navigator.geolocation.getCurrentPosition(location => {
        if (version !== requestVersion) return;
        detect.disabled = false;
        position = location.coords; // Coordinates stay in memory; only a chosen branch is saved.
        region.value = ''; search.value = '';
        render();
        if (!stores.some(coordinates)) status.textContent = 'Branches have no map pins yet. Search your city or choose a region.';
    }, () => {
        if (version !== requestVersion) return;
        detect.disabled = false;
        status.textContent = 'Location could not be accessed. Choose a region or search your city below.';
    }, { timeout: 10000, maximumAge: 300000 });
});
updateLabels();
