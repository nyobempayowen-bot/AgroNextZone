{{--
    Formulaire de dépôt d'un avis produit.
    Affiche uniquement pour un client connecté. Le contrôle « achat réel »
    reste côté serveur (ReviewService) : l'interface ne fait que proposer.
--}}
@php
    $produitId = $produit['id'];
    $hasReview = ! empty($myReview);
@endphp

<section id="avis" class="mt-6 scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
    <div class="border-b border-slate-100 pb-4">
        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-emerald-700">Votre expérience</p>
        <h2 class="mt-1 text-2xl font-black text-slate-950">
            {{ $hasReview ? 'Modifier votre avis' : 'Donner votre avis' }}
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Seuls les clients ayant réellement acheté ce produit peuvent laisser un avis.
        </p>
    </div>

    {{-- Messages de succès / erreur globaux --}}
    @if (session('success'))
        <div role="status" class="mt-4 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div role="alert" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
            <p class="font-semibold">Votre avis n'a pas pu être envoyé :</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formulaire --}}
    <form method="POST"
          action="{{ $hasReview ? route('avis.update', ['review' => $myReview->id]) : route('produit.avis', ['slug' => $produit['slug']]) }}"
          class="mt-5 space-y-5"
          id="review-form">
        @csrf
        @if ($hasReview)
            @method('PUT')
        @endif

        {{-- Note : étoiles interactives, accessibles au clavier --}}
        <div>
            <span class="block text-sm font-semibold text-slate-800">Votre note</span>
            <div class="mt-2 flex items-center gap-1" id="review-stars" role="radiogroup" aria-label="Note sur 5">
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button"
                            data-star="{{ $i }}"
                            role="radio"
                            aria-checked="false"
                            aria-label="{{ $i }} étoile{{ $i > 1 ? 's' : '' }}"
                            class="review-star cursor-pointer rounded p-0.5 transition-transform duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 active:scale-90">
                        <svg class="h-9 w-9 text-slate-300 transition-colors duration-150 sm:h-10 sm:w-10" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 00.95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.58a1 1 0 00-.36 1.12l1.36 4.18c.3.92-.76 1.69-1.54 1.12l-3.56-2.58a1 1 0 00-1.18 0l-3.56 2.58c-.78.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 00-.36-1.12L1.74 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 00.95-.69L9.05 2.93z"/>
                        </svg>
                    </button>
                @endfor
                <span id="review-star-label" class="ml-2 text-sm font-bold text-amber-600" aria-live="polite"></span>
            </div>
            <input type="hidden" name="{{ $hasReview ? 'rating' : 'note' }}"
                   id="review-rating-input"
                   value="{{ $hasReview ? (int) $myReview->rating : '' }}"
                   required>
            @error('note') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('rating') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        {{-- Titre --}}
        <div>
            <label for="review-title" class="block text-sm font-semibold text-slate-800">Titre de votre avis</label>
            <input type="text"
                   id="review-title"
                   name="{{ $hasReview ? 'title' : 'titre' }}"
                   maxlength="150"
                   required
                   placeholder="Ex. : produits frais et bien livrés"
                   value="{{ $hasReview ? old('title', $myReview->title) : old('titre') }}"
                   class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
            @error('titre') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        {{-- Commentaire --}}
        <div>
            <label for="review-comment" class="block text-sm font-semibold text-slate-800">Votre commentaire</label>
            <textarea id="review-comment"
                      name="{{ $hasReview ? 'comment' : 'commentaire' }}"
                      rows="4"
                      maxlength="1000"
                      required
                      placeholder="Décrivez la qualité des produits, le délai de livraison, l'état à la réception…"
                      class="mt-1.5 w-full resize-y rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm leading-6 text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">{{ $hasReview ? old('comment', $myReview->comment) : old('commentaire') }}</textarea>
            <p class="mt-1 flex items-center justify-between text-xs text-slate-400">
                <span>Minimum 10 caractères.</span>
                <span id="review-char-count">0 / 1000</span>
            </p>
            @error('commentaire') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('comment') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
                id="review-submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
            <svg id="review-submit-icon" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <span id="review-submit-label">{{ $hasReview ? 'Enregistrer les modifications' : 'Publier mon avis' }}</span>
        </button>
    </form>
</section>

@once
    {{-- Script inline : cette page n'utilise pas de layout avec @stack,
         le script est donc rendu directement ici (une seule fois). --}}
    <script>
            (function () {
                const form    = document.getElementById('review-form');
                if (!form) return;

                const stars   = Array.from(document.querySelectorAll('#review-stars .review-star'));
                const input   = document.getElementById('review-rating-input');
                const label   = document.getElementById('review-star-label');
                const submit  = document.getElementById('review-submit');
                const sLabel  = document.getElementById('review-submit-label');
                const sIcon   = document.getElementById('review-submit-icon');
                const comment = document.getElementById('review-comment');
                const counter = document.getElementById('review-char-count');
                const INITIAL = parseInt(input.value || '0', 10) || 0;
                const WORDS   = ['', 'Très mauvais', 'Décevant', 'Correct', 'Bon', 'Excellent'];

                let selected = INITIAL;
                let sending  = false;

                function paint(value) {
                    stars.forEach((star) => {
                        const n = parseInt(star.dataset.star, 10);
                        const svg = star.querySelector('svg');
                        const on = value >= n;
                        svg.classList.toggle('text-amber-400', on);
                        svg.classList.toggle('text-slate-300', !on);
                        star.setAttribute('aria-checked', String(n === value));
                        star.classList.toggle('scale-110', n === value);
                    });
                    label.textContent = value > 0 ? `${value}/5 — ${WORDS[value]}` : '';
                }

                function pick(value) {
                    selected = value;
                    input.value = value;
                    paint(value);
                }

                stars.forEach((star, index) => {
                    // Survol : aperçu de la note sans valider.
                    star.addEventListener('mouseenter', () => paint(parseInt(star.dataset.star, 10)));
                    star.addEventListener('mouseleave', () => paint(selected));
                    star.addEventListener('focus', () => paint(parseInt(star.dataset.star, 10)));
                    star.addEventListener('blur', () => paint(selected));
                    // Clic : sélection réelle.
                    star.addEventListener('click', () => pick(parseInt(star.dataset.star, 10)));
                    // Clavier : flèches pour ajuster la note.
                    star.addEventListener('keydown', (e) => {
                        if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
                            e.preventDefault();
                            const next = Math.min(5, (selected || 1) + 1);
                            pick(next);
                            stars[next - 1].focus();
                        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
                            e.preventDefault();
                            const prev = Math.max(1, (selected || 1) - 1);
                            pick(prev);
                            stars[prev - 1].focus();
                        }
                    });
                });

                if (INITIAL > 0) paint(INITIAL);

                if (comment && counter) {
                    const sync = () => { counter.textContent = `${comment.value.length} / 1000`; };
                    comment.addEventListener('input', sync);
                    sync();
                }

                // Anti double-envoi : le bouton se verrouille dès le premier clic.
                form.addEventListener('submit', (e) => {
                    if (sending) { e.preventDefault(); return; }

                    if (!selected) {
                        e.preventDefault();
                        label.textContent = 'Choisissez d\'abord une note.';
                        stars[0].focus();
                        return;
                    }
                    if (comment && comment.value.trim().length < 10) {
                        e.preventDefault();
                        comment.focus();
                        return;
                    }

                    sending = true;
                    submit.disabled = true;
                    submit.classList.add('opacity-70');
                    sLabel.textContent = 'Envoi en cours…';
                    if (sIcon) sIcon.classList.add('animate-spin');
                });
            })();
        </script>
@endonce
