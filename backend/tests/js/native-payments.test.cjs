const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { webcrypto } = require('node:crypto');
const source = fs.readFileSync(require('node:path').join(__dirname, '../../resources/js/native-payments.js'), 'utf8');

const tick = async () => { for (let i = 0; i < 5; i++) await new Promise(setImmediate); };
function fixture(configId, config, fetcher, storage = new Map()) {
    const nodes = new Map(), calls = [], navigation = [];
    function get(id) {
        if (!nodes.has(id)) {
            const classes = new Set(['hidden']);
            nodes.set(id, { value: '', disabled: false, textContent: '', events: {},
                classList: { add: x => classes.add(x), remove: x => classes.delete(x), contains: x => classes.has(x), toggle: (x, on) => on ? classes.add(x) : classes.delete(x) },
                addEventListener(name, fn) { this.events[name] = fn; }, querySelector() { return {}; },
            });
        }
        return nodes.get(id);
    }
    get(configId).textContent = JSON.stringify(config);
    get('native-channel').value = config.channel || 'card';
    const location = { origin: 'http://localhost:8000', href: 'http://localhost:8000/checkout/payment/order', assign: url => navigation.push(url), replace: url => navigation.push(url) };
    const context = vm.createContext({
        document: { getElementById: id => id.endsWith('-config') && id !== configId ? null : get(id), hidden: false },
        window: { addEventListener() {} }, location, history: { state: null, replaceState() {} },
        sessionStorage: { getItem: key => storage.get(key) || null, setItem: (key, value) => storage.set(key, value), removeItem: key => storage.delete(key) },
        crypto: webcrypto, TextEncoder, URL, AbortController,
        setTimeout: () => 1, clearTimeout() {}, btoa: x => Buffer.from(x).toString('base64'),
        FormData: class { constructor(form) { this.fields = form.fields; } get(key) { return this.fields[key]; } },
        fetch: async (url, options = {}) => { calls.push({ url, options }); return fetcher(url, options); },
    });
    vm.runInContext(source, context);
    return { get, context, calls, navigation, storage };
}
const response = (data, ok = true) => ({ ok, json: async () => data });
const base = { statusUrl: '/status', payUrl: '/pay', csrf: 'csrf', publicKey: 'pk_test_public', amount: 112, billing: { name: 'Customer' }, autoStart: false };

test('card details go only to PayMongo; our server receives only a payment token', async () => {
    const app = fixture('native-payment-config', base, async url => {
        if (url === '/status') return response({ status: 'awaiting_payment_method', channel: 'card' });
        if (url === 'https://api.paymongo.com/v1/payment_methods') return response({ data: { id: 'pm_card' } });
        if (url === '/pay') return response({ status: 'awaiting_next_action', channel: 'card', authorization_url: 'https://bank.example/3ds' });
        throw Error('Unexpected request');
    });
    await tick();
    app.get('native-card-number').value = '4343 4343 4343 4345';
    app.get('native-card-expiry').value = '12 / 30';
    app.get('native-card-cvc').value = '123';
    app.get('native-payment-form').events.submit({ preventDefault() {} });
    await tick();
    const tokenization = app.calls.find(call => call.url.includes('api.paymongo.com'));
    assert.equal(JSON.parse(tokenization.options.body).data.attributes.details.card_number, '4343434343434345');
    const payment = app.calls.find(call => call.url === '/pay');
    assert.deepEqual(JSON.parse(payment.options.body), { channel: 'card', payment_method_id: 'pm_card' });
    assert.equal(app.get('native-card-number').value, '');
    assert.equal(app.get('native-card-cvc').value, '');
    assert.deepEqual(app.navigation, ['https://bank.example/3ds']);
});

test('QR Ph renders on our page and never treats an unconfirmed scan as paid', async () => {
    const app = fixture('native-payment-config', { ...base, channel: 'qrph', autoStart: true }, async url => {
        if (url === '/status') return response({ status: 'awaiting_payment_method', channel: 'qrph' });
        return response({ status: 'awaiting_next_action', channel: 'qrph', paid: false, qr_url: 'data:image/png;base64,AAAA' });
    });
    await tick();
    assert.equal(app.get('native-qr-panel').classList.contains('hidden'), false);
    assert.equal(app.get('native-qr-image').src, 'data:image/png;base64,AAAA');
    assert.equal(app.get('native-pay-button').disabled, true);
    assert.equal(app.navigation.length, 0);
});

test('a failed checkout request can retry after reload with the same idempotency token', async () => {
    const storage = new Map();
    const form = { fields: { items_json: '[{"product_id":"bread","qty":1}]', payment_channel: 'gcash', delivery_type: 'delivery', delivery_speed: 'grab', phone: '09171234567', address: 'Home' } };
    function page(ok) {
        const app = fixture('native-checkout-config', { createUrl: '/create', csrf: 'csrf' }, async () => response(ok ? { payment_url: '/checkout/payment/order' } : { message: 'Try again' }, ok), storage);
        app.get('cust_name').value = 'Customer'; app.get('cust_email').value = 'customer@example.com';
        return app;
    }
    const first = page(false);
    await first.context.window.BWNativeCheckout(form);
    assert.equal(first.get('place-order').disabled, false);
    const second = page(true);
    await second.context.window.BWNativeCheckout(form);
    assert.equal(JSON.parse(first.calls[0].options.body).checkout_token, JSON.parse(second.calls[0].options.body).checkout_token);
    assert(![...storage.values()].join('').includes('customer@example.com'));
    assert.match(second.navigation[0], /\/checkout\/payment\/order\?start=1$/);
});


test('confirmed decline displays the reason and clears after successful status check', async () => {
    let failed = true;
    const app = fixture('native-payment-config', base, async () => response({
        status: 'awaiting_payment_method', channel: 'card', failed,
        error_message: failed ? 'Your card was declined. Use another card or contact your bank.' : null,
    }));
    await tick();
    assert.match(app.get('native-error').textContent, /card was declined/);
    assert.equal(app.get('native-pay-button').disabled, false);
    failed = false;
    app.get('native-check').events.click();
    await tick();
    assert.equal(app.get('native-error').classList.contains('hidden'), true);
});

test('invalid security code identifies the field without suggesting an uncertain charge', async () => {
    const app = fixture('native-payment-config', base, async url => {
        if (url === '/status') return response({ status: 'awaiting_payment_method', channel: 'card' });
        return response({ errors: [{ code: 'parameter_invalid', source: { attribute: 'details.cvc' } }] }, false);
    });
    await tick();
    app.get('native-card-number').value = '4343434343434345';
    app.get('native-card-expiry').value = '12 / 30';
    app.get('native-card-cvc').value = '123';
    app.get('native-payment-form').events.submit({ preventDefault() {} });
    await tick();
    assert.match(app.get('native-error').textContent, /security code/);
    assert.match(app.get('native-status').textContent, /Check your payment details/);
    assert(!app.calls.some(call => call.url === '/pay'));
});


test('active QR survives a transient status reset and cannot generate another payment', async () => {
    let reads = 0;
    const app = fixture('native-payment-config', { ...base, channel: 'qrph' }, async () => {
        reads++;
        return response(reads === 1 || reads === 3
            ? { status: 'awaiting_next_action', channel: 'qrph', qr_url: 'data:image/png;base64,AAAA' }
            : { status: 'awaiting_payment_method', channel: 'qrph' });
    });
    await tick();
    assert.equal(app.get('native-pay-button').classList.contains('hidden'), true);
    app.get('native-check').events.click();
    await tick();
    assert.equal(app.get('native-qr-panel').classList.contains('hidden'), false);
    assert.equal(app.get('native-pay-button').disabled, true);
    app.get('native-payment-form').events.submit({ preventDefault() {} });
    await tick();
    assert.equal(app.calls.length, 2);
    app.get('native-check').events.click();
    await tick();
    assert.equal(app.get('native-qr-panel').classList.contains('hidden'), false);
    app.get('native-check').events.click();
    await tick();
    app.get('native-check').events.click();
    await tick();
    assert.equal(app.get('native-qr-panel').classList.contains('hidden'), true);
    assert.match(app.get('native-status').textContent, /no longer active/);
    assert.equal(app.get('native-pay-button').disabled, false);
});
