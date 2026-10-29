const province = document.getElementById('address-province');
const city = document.getElementById('address-city');
const barangay = document.getElementById('address-barangay');
const status = document.getElementById('address-area-status');
const retry = document.getElementById('address-area-retry');
const cache = new Map();
let cities = [];
let ready = false;
let version = 0;
let retryAction = null;
const fallbackCache = new Map();

async function fallbackList(path) {
    if (fallbackCache.has(path)) return fallbackCache.get(path);
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 8000);
    try {
        const response = await fetch(`https://psgc.gitlab.io/api/${path}/`, { signal: controller.signal, credentials: 'omit' });
        if (!response.ok) throw new Error('Fallback locations unavailable');
        const rows = await response.json();
        if (!Array.isArray(rows)) throw new Error('Invalid fallback locations');
        fallbackCache.set(path, rows);
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
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 5000);
    try {
        const response = await fetch(`https://psgc.cloud/api/v2/${path}`, { signal: controller.signal, credentials: 'omit', headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Location service unavailable');
        const body = await response.json();
        const rows = Array.isArray(body) ? body : body.data;
        if (!Array.isArray(rows) || !rows.every(row => typeof row.code === 'string' && typeof row.name === 'string')) throw new Error('Invalid locations');
        cache.set(path, rows);
        return rows;
    } catch {
        const rows = await fallback(path);
        cache.set(path, rows);
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
        const [provinces, allCities] = await Promise.all([load('provinces'), load('cities-municipalities')]);
        if (current !== version) return;
        cities = allCities;
        populate(province, [...provinces, { code: 'NCR', name: 'Metro Manila' }], 'Select province / Metro Manila');
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
    if (!city.value) return;
    try {
        const rows = await load(`cities-municipalities/${encodeURIComponent(city.value)}/barangays`);
        if (current !== version) return;
        populate(barangay, rows, rows.length ? 'Select barangay' : 'No barangays available');
        if (!rows.length) error(loadBarangays);
    } catch { if (current === version) error(loadBarangays); }
}
city.addEventListener('change', loadBarangays);
retry.addEventListener('click', () => retryAction?.());
document.addEventListener('bw:address-open', initialize);
