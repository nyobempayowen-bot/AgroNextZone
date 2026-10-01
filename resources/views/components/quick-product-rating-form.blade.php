{{--
    Notation rapide d'un PRODUIT acheté, depuis « Vos achats à évaluer ».

    L'utilisateur n'a pas à chercher le produit : il vient de l'acheter.
    Le contrôle « achat réel » est refait côté serveur à chaque POST
    (ReviewService::create → assertPurchased) : ce formulaire ne fait
    que proposer, il n'autorise rien.

    @param array $item  ['id','slug','nom','image','producteur','quantite','order_id']
--}}
@php
    $productId = (int) $item['id'];
@endphp

<form method="POST"
      action="{{ route('produit.avis', ['slug' => $item['slug']]) }}"
      class="quick-product-rating"
      data-order-id="{{ $item['order_id'] ?? '' }}"
      data-product-id="{{ $productId }}">
    @csrf

    <input type="hidden" name="note" value="" data-qpr-rating>

    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-0.5" role="radiogroup" aria-label="Note de 1 à 5 étoiles" data-qpr-stars>
            @for ($i = 1; $i <= 5; $i++)
                <button type="button"
                        data-qpr-star="{{ $i }}"
                        role="radio"
                        aria-checked="false"
                        aria-label="{{ $i }} étoile{{ $i > 1 ? 's' : '' }}"
                        class="cursor-pointer rounded p-0.5 transition-transform duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 active:scale-90">
                    <svg class="h-6 w-6 text-slate-300 transition-colors duration-150" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 00.95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.58a1 1 0 00-.36 1.12l1.36 4.18c.3.92-.76 1.69-1.54 1.12l-3.56-2.58a1 1 0 00-1.18 0l-3.56 2.58c-.78.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 00-.36-1.12L1.74 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 00.95-.69L9.05 2.93z"/>
                    </svg>
                </button>
            @endfor
        </div>

        <span class="text-xs font-bold text-amber-600" data-qpr-label aria-live="polite"></span>
    </div>

    {{-- Titre et commentaire sont exigés par StoreReviewRequest (règle existante). --}}
    <input type="text"
           name="titre"
           maxlength="150"
           required
           placeholder="Titre de votre avis (ex. : très bon cacao)"
           class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs focus:border-emerald-600 focus:outline-none transition">
    @error('titre') <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p> @enderror

    <textarea name="commentaire"
              rows="2"
              maxlength="1000"
              required
              placeholder="Décrivez votre expérience (qualité, livraison…)"
              class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs leading-5 focus:border-emerald-600 focus:outline-none transition">{{ old('commentaire') }}</textarea>
    @error('commentaire') <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p> @enderror

    <button type="submit"
            data-qpr-submit
            class="mt-2.5 inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-60">
        <span data-qpr-submit-label>Publier mon avis</span>
    </button>
</form>

<script>
    (function () {
        // Un script par formulaire : on traite tous les blocs de la page.
        var forms = document.querySelectorAll('.quick-product-rating');

        forms.forEach(function (form) {
            if (form.dataset.ready) return;
            form.dataset.ready = '1';

            var stars = Array.from(form.querySelectorAll('[data-qpr-star]'));
            var input = form.querySelector('[data-qpr-rating]');
            var label = form.querySelector('[data-qpr-label]');
            var submit = form.querySelector('[data-qpr-submit]');
            var submitLabel = form.querySelector('[data-qpr-submit-label]');
            var WORDS = ['', 'Très mauvais', 'Décevant', 'Correct', 'Bon', 'Excellent'];
            var selected = 0;
            var sending = false;

            function paint(value) {
                stars.forEach(function (star) {
                    var n = parseInt(star.dataset.qprStar, 10);
                    var svg = star.querySelector('svg');
                    var on = value >= n;
                    svg.classList.toggle('text-amber-400', on);
                    svg.classList.toggle('text-slate-300', !on);
                    star.setAttribute('aria-checked', String(n === value));
                });
                label.textContent = value > 0 ? value + '/5 — ' + WORDS[value] : '';
            }

            stars.forEach(function (star) {
                var n = parseInt(star.dataset.qprStar, 10);
                star.addEventListener('mouseenter', function () { paint(n); });
                star.addEventListener('mouseleave', function () { paint(selected); });
                star.addEventListener('focus', function () { paint(n); });
                star.addEventListener('blur', function () { paint(selected); });
                star.addEventListener('click', function () {
                    selected = n;
                    input.value = n;
                    paint(n);
                });
            });

            form.addEventListener('submit', function (e) {
                if (sending) { e.preventDefault(); return; }
                if (!selected) {
                    e.preventDefault();
                    label.textContent = 'Choisissez une note.';
                    stars[0].focus();
                    return;
                }
                sending = true;
                submit.disabled = true;
                submitLabel.textContent = 'Envoi…';
            });
        });
    })();
</script>