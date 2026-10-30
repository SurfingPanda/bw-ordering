const province = document.getElementById('address-province');
const city = document.getElementById('address-city');
const barangay = document.getElementById('address-barangay');
const status = document.getElementById('address-area-status');
const retry = document.getElementById('address-area-retry');
const cache = new Map();
let cities = [];
let provinces = [];
let ready = false;
let version = 0;
let retryAction = null;
const fallbackCache = new Map();
const CACHE_PREFIX = 'bw_ph_address_lists_v1:';
const CACHE_TTL = 7 * 24 * 60 * 60 * 1000;
const inFlight = new Map();
function validRows(rows) {
    return Array.isArray(rows) && rows.length > 0 && rows.every(row => row && typeof row.code === 'string' && typeof row.name === 'string');
}
function readCache(path) {
    try {
        const entry = JSON.parse(localStorage.getItem(CACHE_PREFIX + path) || 'null');
        if (entry && Number.isFinite(entry.savedAt) && entry.savedAt <= Date.now()
            && Date.now() - entry.savedAt < CACHE_TTL && validRows(entry.rows)) return entry.rows;
        if (entry) localStorage.removeItem(CACHE_PREFIX + path);
    } catch { /* Storage is optional; live requests still work. */ }
    return null;
}
function writeCache(path, rows) {
    if (!validRows(rows)) return;
    try { localStorage.setItem(CACHE_PREFIX + path, JSON.stringify({ savedAt: Date.now(), rows })); }
    catch { /* Quota or privacy settings must not prevent address selection. */ }
}

async function fallbackList(path) {
    if (fallbackCache.has(path)) return fallbackCache.get(path);
    const saved = readCache(`fallback/${path}`);
    if (saved) { fallbackCache.set(path, saved); return saved; }
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 8000);
    try {
        const response = await fetch(`https://psgc.gitlab.io/api/${path}/`, { signal: controller.signal, credentials: 'omit' });
        if (!response.ok) throw new Error('Fallback locations unavailable');
        const rows = await response.json();
        if (!validRows(rows)) throw new Error('Invalid fallback locations');
        fallbackCache.set(path, rows);
        writeCache(`fallback/${path}`, rows);
        return rows;
    } finally { clearTimeout(timeout); }
}
async function fallback(path) {
    if (path === 'provinces') return fallbackList(path);
    const [towns, provinces] = await Promise.all([fallbackList('cities-municipalities'), fallbackList('provinces')]);
    if (path === 'cities-municipalities') {
        return towns.map(row => ({ ...row, province: provinces.find(p => p.code === row.provinceCode)?.name || '' }));
    }
    const code = path.split('/')[1];
    const town = towns.find(row => row.code === code || row.psgc10DigitCode === code);
    if (!town) throw new Error('City unavailable in fallback');
    return fallbackList(`cities-municipalities/${town.code}/barangays`);
}

function reset(select, message) {
    select.replaceChildren(new Option(message, ''));
    select.disabled = true;
}
function populate(select, rows, message) {
    reset(select, message);
    rows.sort((a, b) => a.name.localeCompare(b.name)).forEach(row => {
        select.add(new Option(row.name.trim(), row.code));
    });
    select.disabled = rows.length === 0;
}
async function load(path) {
    if (cache.has(path)) return cache.get(path);
    const saved = readCache(path);
    if (saved) { cache.set(path, saved); return saved; }
    if (inFlight.has(path)) return inFlight.get(path);
    const request = fetchLocations(path).finally(() => inFlight.delete(path));
    inFlight.set(path, request);
    return request;
}
async function fetchLocations(path) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 5000);
    try {
        const response = await fetch(`https://psgc.cloud/api/v2/${path}`, { signal: controller.signal, credentials: 'omit', headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Location service unavailable');
        const body = await response.json();
        const rows = Array.isArray(body) ? body : body.data;
        if (!validRows(rows)) throw new Error('Invalid locations');
        cache.set(path, rows);
        writeCache(path, rows);
        return rows;
    } catch {
        const rows = await fallback(path);
        cache.set(path, rows);
        writeCache(path, rows);
        return rows;
    } finally { clearTimeout(timeout); }
}
function error(action) {
    status.textContent = 'Could not load locations. Check your connection and try again.';
    retryAction = action;
    retry.hidden = false;
}
async function initialize() {
    const current = ++version;
    reset(city, 'Select province first'); reset(barangay, 'Select city first');
    retry.hidden = true;
    if (ready) { province.value = ''; status.textContent = ''; return; }
    reset(province, 'Loading provinces…');
    status.textContent = 'Loading Philippine locations…';
    try {
        const [provinceRows, allCities] = await Promise.all([load('provinces'), load('cities-municipalities')]);
        if (current !== version) return;
        cities = allCities;
        provinces = [...provinceRows, { code: 'NCR', name: 'Metro Manila' }];
        populate(province, [...provinces], 'Select province / Metro Manila');
        ready = true; status.textContent = '';
    } catch { if (current === version) error(initialize); }
}
const isNcr = row => /National Capital Region|\bNCR\b/i.test(row.region || '') || row.code.startsWith('13');
province.addEventListener('change', () => {
    version++;
    retry.hidden = true; status.textContent = '';
    reset(barangay, 'Select city first');
    if (!province.value) { reset(city, 'Select province first'); return; }
    const name = province.selectedOptions[0].textContent;
    const matches = cities.filter(row => province.value === 'NCR' ? isNcr(row) : !isNcr(row) && row.province?.trim() === name);
    populate(city, matches, matches.length ? 'Select city / municipality' : 'No cities available');
});
async function loadBarangays() {
    const current = ++version;
    retry.hidden = true; status.textContent = '';
    reset(barangay, city.value ? 'Loading barangays…' : 'Select city first');
    if (!city.value) return false;
    try {
        const rows = await load(`cities-municipalities/${encodeURIComponent(city.value)}/barangays`);
        if (current !== version) return false;
        populate(barangay, rows, rows.length ? 'Select barangay' : 'No barangays available');
        if (!rows.length) error(loadBarangays);
        return rows.length > 0;
    } catch {
        if (current === version) error(loadBarangays);
        return false;
    }
}
city.addEventListener('change', loadBarangays);
retry.addEventListener('click', () => retryAction?.());
document.addEventListener('bw:address-open', initialize);

function normalizeAreaName(value) {
    return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
        .replace(/\b(city|municipality|province|region|of)\b/g, ' ')
        .replace(/[^a-z0-9]+/g, ' ').trim();
}

async function fillAreasFromCurrentLocation(coords) {
    if (!ready) await initialize();
    const current = ++version;
    retry.hidden = true;
    status.textContent = 'Finding your province, city, and barangay…';
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 10000);
    try {
        const params = new URLSearchParams({
            latitude: coords.latitude,
            longitude: coords.longitude,
            localityLanguage: 'en',
        });
        const response = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?${params}`, {
            signal: controller.signal,
            credentials: 'omit',
        });
        if (!response.ok) throw new Error('Could not find address areas');
        const area = await response.json();
        if (current !== version) return;
        if (area.countryCode !== 'PH') {
            status.textContent = 'Location detected. Choose the Philippine address fields manually.';
            return;
        }

        const administrative = (area.localityInfo?.administrative || []).map(item => item?.name).filter(Boolean);
        const provinceNames = [area.principalSubdivision, ...administrative];
        const provinceRow = provinces.find(row => provinceNames.some(name => normalizeAreaName(name) === normalizeAreaName(row.name)));
        if (!provinceRow) {
            status.textContent = 'Could not match the province. Please choose it from the list.';
            return;
        }

        province.value = provinceRow.code;
        province.dispatchEvent(new Event('change'));
        const cityCandidates = [area.city, area.locality, ...administrative.slice().reverse()].filter(Boolean);
        const provinceCities = cities.filter(row => provinceRow.code === 'NCR'
            ? isNcr(row)
            : !isNcr(row) && normalizeAreaName(row.province) === normalizeAreaName(provinceRow.name));
        const cityRow = cityCandidates
            .map(name => provinceCities.find(row => normalizeAreaName(row.name) === normalizeAreaName(name)))
            .find(Boolean);
        if (!cityRow) {
            status.textContent = `Province set to ${provinceRow.name}. Choose the city or municipality from the list.`;
            return;
        }

        city.value = cityRow.code;
        if (!await loadBarangays()) return;
        const barangayCandidates = [area.locality, ...administrative.slice().reverse()].filter(Boolean);
        const barangayRow = barangayCandidates
            .map(name => [...barangay.options].find(option => option.value && normalizeAreaName(option.textContent) === normalizeAreaName(name)))
            .find(Boolean);
        if (barangayRow) barangay.value = barangayRow.value;
        status.textContent = barangayRow
            ? 'Address areas filled from your pin. Check them before saving.'
            : `Province and city filled for ${cityRow.name}. Choose the barangay from the list.`;
    } catch {
        if (current === version) status.textContent = 'Could not look up the address areas. You can choose them from the lists.';
    } finally {
        clearTimeout(timeout);
    }
}

document.addEventListener('bw:address-pin-located', event => {
    fillAreasFromCurrentLocation(event.detail);
});
