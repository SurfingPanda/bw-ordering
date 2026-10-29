async function jsonRequest(url, options = {}) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 35000);
    try {
        const response = await fetch(url, { ...options, cache: 'no-store', signal: controller.signal, headers: { Accept: 'application/json', ...options.headers } });
        const data = await response.json().catch(() => { throw new Error('Please refresh the page and sign in again.'); });
        if (!response.ok) {
            const error = new Error(data.message || 'Payment service is unavailable. Please try again.');
            error.paymentFailed = data.payment_failed === true;
            throw error;
        }
        return data;
    } catch (error) {
        if (error.name === 'AbortError') throw new Error('Payment is taking longer than expected. Check its status before trying again.');
        throw error;
    } finally { clearTimeout(timeout); }
}
const attemptStorage = 'bw_native_checkout_attempt';
let memoryAttempt = null;
const checkoutConfig = document.getElementById('native-checkout-config');
if (checkoutConfig) {
    const config = JSON.parse(checkoutConfig.textContent);
    window.BWNativeCheckout = async form => {
        const button = document.getElementById('place-order');
        if (button.disabled) return;
        const errorBox = document.getElementById('payment-error');
        errorBox.classList.add('hidden');
        const label = button.textContent;
        button.disabled = true; button.textContent = 'Preparing secure payment…';
        try {
            const fields = new FormData(form);
            const payload = {
                items: JSON.parse(fields.get('items_json') || '[]'),
                payment_method: 'paymongo', payment_channel: fields.get('payment_channel'),
                delivery_type: fields.get('delivery_type'), delivery_speed: fields.get('delivery_speed'),
                fulfillment_store_id: fields.get('fulfillment_store_id') || null,
                voucher: fields.get('voucher') || null, phone: fields.get('phone'),
                address: fields.get('address'), notes: fields.get('notes'),
                customer_name: document.getElementById('cust_name').value.trim(),
                customer_email: document.getElementById('cust_email').value.trim(),
            };
            const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(JSON.stringify(payload)));
            const fingerprint = Array.from(new Uint8Array(digest), n => n.toString(16).padStart(2, '0')).join('');
            let attempt = memoryAttempt;
            try { attempt = JSON.parse(sessionStorage.getItem(attemptStorage)) || attempt; } catch { /* optional */ }
            if (!attempt || attempt.fingerprint !== fingerprint) attempt = { fingerprint, token: crypto.randomUUID() };
            memoryAttempt = attempt;
            try { sessionStorage.setItem(attemptStorage, JSON.stringify(attempt)); } catch { /* optional */ }
            payload.checkout_token = attempt.token;
            const result = await jsonRequest(config.createUrl, {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf }, body: JSON.stringify(payload),
            });
            const next = new URL(result.payment_url, location.origin);
            if (next.origin !== location.origin) throw new Error('Could not open payment. Please try again.');
            next.searchParams.set('start', '1');
            location.assign(next.href);
        } catch (error) {
            errorBox.textContent = error.message; errorBox.classList.remove('hidden');
            button.disabled = false; button.textContent = label;
        }
    };
}

const paymentConfig = document.getElementById('native-payment-config');
if (paymentConfig) {
    const config = JSON.parse(paymentConfig.textContent);
    const form = document.getElementById('native-payment-form');
    const channel = document.getElementById('native-channel');
    const pay = document.getElementById('native-pay-button');
    const check = document.getElementById('native-check');
    const status = document.getElementById('native-status');
    const errorBox = document.getElementById('native-error');
    const cardFields = document.getElementById('native-card-fields');
    const number = document.getElementById('native-card-number');
    const expiry = document.getElementById('native-card-expiry');
    const cvc = document.getElementById('native-card-cvc');
    const qr = document.getElementById('native-qr-panel');
    const authorization = document.getElementById('native-authorize');
    let busy = false, currentState = null, poll = null, checks = 0, qrResetChecks = 0;
    const amount = Number(config.amount).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
    function error(message) { errorBox.textContent = message; errorBox.classList.remove('hidden'); }
    function clearError() { errorBox.classList.add('hidden'); }
    function displayMethod() {
        const card = channel.value === 'card';
        cardFields.classList.toggle('hidden', !card);
        [number, expiry, cvc].forEach(input => { input.disabled = !card || busy; input.required = card; });
        pay.textContent = channel.value === 'qrph' ? `Generate QR · ${amount}` : `Pay ${amount}`;
    }
    function setBusy(value) {
        busy = value; check.disabled = value;
        const locked = currentState && !['awaiting_payment_method'].includes(currentState.status);
        pay.disabled = value || Boolean(locked) || !channel.value;
        channel.disabled = value || Boolean(locked);
        displayMethod();
        pay.classList.toggle('hidden', channel.value === 'qrph' && Boolean(locked));
        if (value) pay.textContent = channel.value === 'qrph' ? 'Preparing QR?' : 'Please wait?';
    }
    function stopPolling() { if (poll) clearTimeout(poll); poll = null; }
    function schedule() {
        stopPolling();
        if (++checks > 450) return;
        poll = setTimeout(async () => {
            if (!document.hidden && !busy) await refresh();
            else schedule();
        }, 4000);
    }
    function render(state, redirect = false) {
        // Recheck a transient reset before discarding a QR already shown to the customer.
        const hadQr = currentState?.status === 'awaiting_next_action' && currentState?.qr_url;
        if (hadQr && state.status === 'awaiting_payment_method' && !state.failed && !state.paid && ++qrResetChecks < 2) {
            status.textContent = 'Checking your QR payment. Please wait?';
            setBusy(false); schedule(); return;
        }
        const qrUnavailable = hadQr && state.status === 'awaiting_payment_method';
        qrResetChecks = 0;
        currentState = state;
        clearError();
        qr.classList.add('hidden'); authorization.hidden = true; authorization.classList.add('hidden');
        if (state.paid) {
            stopPolling();
            status.textContent = 'Payment confirmed. Opening your order…';
            try { sessionStorage.removeItem(attemptStorage); } catch { /* optional */ }
            const next = new URL(state.success_url, location.origin);
            if (next.origin === location.origin) location.replace(next.href);
            return;
        }
        if (['gcash', 'card', 'qrph'].includes(state.channel)) {
            channel.value = state.channel;
            document.getElementById('native-channel-label').textContent = { gcash: 'GCash', card: 'Credit / debit card', qrph: 'QR Ph' }[state.channel];
        }
        const action = state.authorization_url;
        if (state.status === 'awaiting_next_action' && state.qr_url) {
            let image = state.qr_url;
            if (!image.startsWith('data:') && !image.startsWith('https://')) image = `data:image/png;base64,${image}`;
            if (/^(data:image\/(png|jpeg);base64,|https:\/\/)/.test(image)) {
                document.getElementById('native-qr-image').src = image;
                qr.classList.remove('hidden');
            }
            status.textContent = 'Scan your QR Ph code to pay. Waiting for confirmation…';
            schedule();
        } else if (state.status === 'awaiting_next_action' && action) {
            const url = new URL(action);
            if (url.protocol !== 'https:') throw new Error('Could not open payment authorization.');
            authorization.href = url.href; authorization.hidden = false; authorization.classList.remove('hidden'); authorization.classList.add('block');
            authorization.textContent = state.channel === 'gcash' ? 'Continue to GCash' : 'Continue to bank authorization';
            status.textContent = 'Complete the approval step to finish your payment.';
            if (redirect) location.assign(url.href);
            else schedule();
        } else if (state.status === 'processing') {
            status.textContent = 'Your payment is processing. Please wait for confirmation.'; schedule();
        } else if (['canceled', 'cancelled'].includes(state.status)) {
            status.textContent = 'This payment was canceled. Return to the menu to start again.'; stopPolling();
            try { sessionStorage.removeItem(attemptStorage); } catch { /* optional */ }
        } else {
            stopPolling();
            status.textContent = state.failed ? 'Payment was not completed. Check the guidance below before retrying.' : 'Ready to complete your payment.';
            if (state.failed) error(state.error_message || 'Payment was not completed. Check your details or choose another method.');
            else if (qrUnavailable) status.textContent = 'This QR code is no longer active. Generate a new code to continue.';
        }
        setBusy(false);
        if (state.status === 'awaiting_next_action' || state.status === 'processing') cardFields.classList.add('hidden');
    }
    async function refresh() {
        if (busy) return;
        setBusy(true);
        try { render(await jsonRequest(config.statusUrl)); }
        catch (e) { error(e.message); setBusy(false); stopPolling(); }
    }
    async function tokenizeCard() {
        if (!config.publicKey?.startsWith('pk_')) throw new Error('Card payments are temporarily unavailable. Please choose GCash or QR Ph.');
        const digits = number.value.replace(/\D/g, '');
        const match = expiry.value.match(/^\s*(\d{1,2})\s*\/\s*(\d{2}|\d{4})\s*$/);
        if (digits.length < 13 || digits.length > 19 || !match || !/^\d{3,4}$/.test(cvc.value)) throw new Error('Please check your card number, expiry date, and security code.');
        const month = Number(match[1]), year = Number(match[2]) + (match[2].length === 2 ? 2000 : 0);
        if (month < 1 || month > 12 || new Date(year, month) <= new Date()) throw new Error('Please enter a valid future expiry date.');
        const attributes = { type: 'card', details: { card_number: digits, exp_month: month, exp_year: year, cvc: cvc.value }, billing: config.billing };
        // This request goes directly from the browser to PayMongo; no raw card data enters our application.
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 20000);
        let response;
        try {
            response = await fetch('https://api.paymongo.com/v1/payment_methods', {
                method: 'POST', signal: controller.signal,
                headers: { Authorization: `Basic ${btoa(config.publicKey + ':')}`, 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ data: { attributes } }),
            });
        } catch { throw new Error('Could not reach the card payment service. Please try again.'); }
        finally { clearTimeout(timeout); }
        const result = await response.json();
        if (!response.ok || !/^pm_[a-zA-Z0-9]+$/.test(result.data?.id || '')) {
            const detail = result.errors?.[0] || {};
            const code = detail.sub_code || detail.code;
            const field = detail.source?.attribute || detail.source?.pointer || '';
            let message = 'Your card could not be validated. Check the details or use another payment method.';
            if (['card_number_invalid', 'invalid_number', 'incorrect_number'].includes(code) || /card_number/.test(field)) message = 'Check your card number and try again.';
            else if (['cvc_invalid', 'cvc_incorrect', 'invalid_cvc', 'incorrect_cvc'].includes(code) || /cvc/.test(field)) message = 'Check your card security code (CVC) and try again.';
            else if (['expired_card', 'card_expired'].includes(code)) message = 'This card has expired. Use a different card.';
            else if (/exp_month|exp_year/.test(field)) message = 'Check your card expiry date and try again.';
            throw new Error(message);
        }
        number.value = ''; expiry.value = ''; cvc.value = '';
        return result.data.id;
    }
    async function submit() {
        if (busy || (currentState && currentState.status !== 'awaiting_payment_method')) return;
        clearError();
        // Read the channel before controls are disabled, and never include card fields in our request.
        const chosen = channel.value;
        setBusy(true); stopPolling(); status.textContent = 'Preparing your payment…';
        let submitted = false;
        try {
            const method = chosen === 'card' ? await tokenizeCard() : null;
            submitted = true;
            const state = await jsonRequest(config.payUrl, {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                body: JSON.stringify({ channel: chosen, payment_method_id: method }),
            });
            render(state, true);
        } catch (e) {
            error(e.message);
            status.textContent = !submitted || e.paymentFailed
                ? 'Check your payment details before retrying.'
                : 'Check payment status before trying again.';
            setBusy(false);
        }
    }
    form.addEventListener('submit', event => { event.preventDefault(); submit(); });
    channel.addEventListener('change', displayMethod);
    check.addEventListener('click', () => { clearError(); refresh(); });
    window.addEventListener('pagehide', stopPolling);
    window.addEventListener('pageshow', event => { if (event.persisted) { busy = false; refresh(); } });
    number.addEventListener('input', () => { number.value = number.value.replace(/\D/g, '').slice(0, 19).replace(/(.{4})/g, '$1 ').trim(); });
    expiry.addEventListener('input', () => { const digits = expiry.value.replace(/\D/g, '').slice(0, 4); expiry.value = digits.length > 2 ? digits.slice(0, 2) + ' / ' + digits.slice(2) : digits; });
    displayMethod();
    const url = new URL(location.href); url.searchParams.delete('start'); history.replaceState(history.state, '', url);
    refresh().then(() => {
        if (config.autoStart && currentState?.status === 'awaiting_payment_method' && !currentState.failed && channel.value !== 'card') submit();
    });
}
