{{-- A one-question, voluntary prompt. Its localStorage flag prevents it from
     interrupting the same browser again once answered or dismissed. --}}
@if($siteRating['enabled'] ?? true)
<div id="site-rating-modal" data-delay-seconds="{{ max(0, min(60, (int) ($siteRating['delaySeconds'] ?? 5))) }}" class="fixed inset-0 z-[85] hidden items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="site-rating-title" aria-describedby="site-rating-copy">
    <div class="relative w-full max-w-sm rounded-3xl bg-white p-6 text-center shadow-2xl sm:p-8" data-site-rating-card>
        <button type="button" data-site-rating-close aria-label="Close rating prompt" class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-navy-700">&#10005;</button>
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-2xl" aria-hidden="true">&#127856;</div>
        <h2 id="site-rating-title" class="mt-4 font-brand text-2xl font-bold text-navy-800">How was your visit?</h2>
        <p id="site-rating-copy" class="mt-2 text-sm text-slate-500">Your quick rating helps us make BW Superbakeshop even better.</p>
        <div class="mt-6 flex justify-center gap-1" role="radiogroup" aria-label="Rate your experience from 1 to 5 stars">
            @for($star = 1; $star <= 5; $star++)
                <button type="button" data-site-rating-star="{{ $star }}" role="radio" aria-checked="false" aria-label="{{ $star }} {{ $star === 1 ? 'star' : 'stars' }}" class="site-rating-star rounded-full p-1 text-4xl leading-none text-slate-200 transition hover:scale-110 focus:outline-none focus:ring-2 focus:ring-brand-400">&#9733;</button>
            @endfor
        </div>
        <p data-site-rating-status class="mt-5 min-h-5 text-sm font-medium text-green-600" role="status" aria-live="polite"></p>
        <button type="button" data-site-rating-close class="mt-3 text-sm font-medium text-slate-500 underline decoration-slate-300 underline-offset-4 transition hover:text-navy-800">Not now</button>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('site-rating-modal');
    if (!modal) return;

    var storageKey = 'bw_site_rating_prompt_done';
    var stars = modal.querySelectorAll('[data-site-rating-star]');
    var status = modal.querySelector('[data-site-rating-status]');
    var submitted = false;

    function close(done) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (done) localStorage.setItem(storageKey, '1');
    }

    function paint(value) {
        stars.forEach(function (star) {
            var active = Number(star.dataset.siteRatingStar) <= value;
            star.classList.toggle('text-brand-500', active);
            star.classList.toggle('text-slate-200', !active);
            star.setAttribute('aria-checked', active ? 'true' : 'false');
        });
    }

    // Wait until the visitor has had a moment to engage with the page.
    if (!localStorage.getItem(storageKey)) {
        window.setTimeout(function () {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }, Number(modal.dataset.delaySeconds || 5) * 1000);
    }

    modal.querySelectorAll('[data-site-rating-close]').forEach(function (button) {
        button.addEventListener('click', function () { close(true); });
    });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) close(true);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) close(true);
    });

    stars.forEach(function (star) {
        star.addEventListener('mouseenter', function () { paint(Number(star.dataset.siteRatingStar)); });
        star.addEventListener('focus', function () { paint(Number(star.dataset.siteRatingStar)); });
        star.addEventListener('click', function () {
            if (submitted) return;
            submitted = true;
            var rating = Number(star.dataset.siteRatingStar);
            paint(rating);

            fetch('{{ route('site-rating.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ rating: rating, page: window.location.pathname })
            }).then(function (response) {
                if (!response.ok) throw new Error('Rating could not be saved');
                status.textContent = 'Thank you for your feedback!';
                localStorage.setItem(storageKey, '1');
                window.setTimeout(function () { close(false); }, 1200);
            }).catch(function () {
                submitted = false;
                status.classList.remove('text-green-600');
                status.classList.add('text-red-600');
                status.textContent = 'We could not save that rating. Please try again.';
            });
        });
    });
})();
</script>
@endif
