{{--
    Formulaire de notation d'un producteur (étoiles interactives).

    N'est affiché que si le client est éligible (achat réel vérifié côté
    serveur). Le contrôle d'éligibilité fait autorité : ProducerReviewService.

    @param array $ord  commande enrichie (order_id, producteur, producteur_id, peut_noter, mon_avis)
--}}
@php
    $orderId = $ord['order_id'];
    $producerId = $ord['producteur_id'];
    $existing = $ord['mon_avis'] ?? null;
    $isUpdate = $existing !== null;
    $initial = $isUpdate ? (int) $existing['note'] : 0;
@endphp

<div class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50/60 p-3 rating-form" data-review-form>
    <form method="POST"
          action="{{ $isUpdate
              ? route('client.producer-review.update', ['review' => $existing['id']])
              : route('client.producer-review.store', ['order' => $orderId, 'producer' => $producerId]) }}">
        @csrf
        @if ($isUpdate) @method('PUT') @endif

        {{-- Étoiles --}}
        <div>
            <span class="block text-xs font-semibold text-slate-700">
                {{ $isUpdate ? 'Modifier votre note' : 'Votre note pour ce producteur' }}
            </span>
            <div class="mt-1.5 flex items-center gap-0.5" role="radiogroup" aria-label="Note de 1 à 5 étoiles" data-stars>
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button"
                            data-star="{{ $i }}"
                            role="radio"
                            aria-checked="false"
                            aria-label="{{ $i }} étoile{{ $i > 1 ? 's' : '' }}"
                            class="pr-star cursor-pointer rounded p-0.5 transition-transform duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 active:scale-90">
                        <svg class="h-7 w-7 text-slate-300 transition-colors duration-150" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 00.95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.58a1 1 0 00-.36 1.12l1.36 4.18c.3.92-.76 1.69-1.54 1.12l-3.56-2.58a1 1 0 00-1.18 0l-3.56 2.58c-.78.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 00-.36-1.12L1.74 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 00.95-.69L9.05 2.93z"/>
                        </svg>
                    </button>
                @endfor
                <span class="ml-1.5 text-xs font-bold text-amber-600" data-star-label aria-live="polite"></span>
            </div>
            <input type="hidden" name="rating" value="{{ $initial }}" data-rating required>
            @error('rating') <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p> @enderror
        </div>

        {{-- Commentaire --}}
        <textarea name="comment"
                  rows="2"
                  maxlength="1000"
                  placeholder="Partagez votre expérience avec ce producteur (optionnel)…"
                  class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs leading-5 focus:border-emerald-600 focus:outline-none transition">{{ old('comment', $isUpdate ? $existing['commentaire'] : '') }}</textarea>
        @error('comment') <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p> @enderror

        <button type="submit"
                data-submit
                class="mt-2.5 inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-60">
            <span data-submit-label>{{ $isUpdate ? 'Enregistrer ma note' : 'Noter le producteur' }}</span>
        </button>
    </form>
</div>

{{-- Script inline : cette vue n'a pas de layout avec @stack, le script est
     donc rendu ici. Il doit rester IMMEDIATEMENT APRÈS le div racine, car
     document.currentScript sert de point d'ancrage au formulaire. --}}
<script>
    (function () {
        // Le <script> est le frère direct du div racine : on remonte au premier
        // élément .rating-form qui le précède. (Un simple closest() échouerait,
        // le script n'étant pas un descendant du formulaire.)
        var root = document.currentScript.previousElementSibling;
        if (!root || !root.matches('.rating-form') || root.dataset.ready) return;
        root.dataset.ready = '1';

        var stars  = Array.from(root.querySelectorAll('[data-star]'));
        var input  = root.querySelector('[data-rating]');
        var label  = root.querySelector('[data-star-label]');
        var form   = root.querySelector('form');
        var submit = root.querySelector('[data-submit]');
        var sLabel = root.querySelector('[data-submit-label]');
        var initial = parseInt(input.value || '0', 10) || 0;
        var WORDS = ['', 'Très mauvais', 'Décevant', 'Correct', 'Bon', 'Excellent'];
        var selected = initial;
        var sending = false;

        function paint(value) {
            stars.forEach(function (star) {
                var n = parseInt(star.dataset.star, 10);
                var svg = star.querySelector('svg');
                var on = value >= n;
                svg.classList.toggle('text-amber-400', on);
                svg.classList.toggle('text-slate-300', !on);
                star.setAttribute('aria-checked', String(n === value));
            });
            label.textContent = value > 0 ? value + '/5 — ' + WORDS[value] : '';
        }

        function pick(value) {
            selected = value;
            input.value = value;
            paint(value);
        }

        stars.forEach(function (star) {
            star.addEventListener('mouseenter', function () { paint(parseInt(star.dataset.star, 10)); });
            star.addEventListener('mouseleave', function () { paint(selected); });
            star.addEventListener('focus', function () { paint(parseInt(star.dataset.star, 10)); });
            star.addEventListener('blur', function () { paint(selected); });
            star.addEventListener('click', function () { pick(parseInt(star.dataset.star, 10)); });
            star.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    var next = Math.min(5, (selected || 1) + 1);
                    pick(next); stars[next - 1].focus();
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    var prev = Math.max(1, (selected || 1) - 1);
                    pick(prev); stars[prev - 1].focus();
                }
            });
        });

        if (initial > 0) paint(initial);

        // Anti double-envoi + note obligatoire.
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
            submit.classList.add('opacity-70');
            sLabel.textContent = 'Envoi…';
        });
    })();
</script>
