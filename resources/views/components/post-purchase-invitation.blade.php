{{--
    Invitation a la notation apres un achat confirme.

    Regle absolue : la notation est FACULTATIVE.
    - Le modal est purement informatif : il ne bloque rien.
    - Il se ferme au clic sur « Plus tard », sur la croix, sur le fond,
      ou avec la touche Echap.
    - Aucun champ n'est requis, aucune note n'est pre-remplie, aucun
      formulaire n'est envoye tant que l'utilisateur n'a pas choisi
      « Noter maintenant ».
    - Fermer n'entraine AUCUNE erreur et ne modifie la commande en rien.

    @param array $invitation  ['products' => Collection, 'producers' => Collection, 'total' => int]
    @param \App\Models\Order $order
--}}
@php
    $products = $invitation['products'] ?? collect();
    $producers = $invitation['producers'] ?? collect();
    $total = (int) ($invitation['total'] ?? 0);
@endphp

@if ($total > 0)
    <div id="postPurchaseInvite"
         class="fixed inset-0 z-[60] hidden items-end sm:items-center justify-center bg-slate-900/50 p-0 sm:p-4"
         role="dialog"
         aria-modal="true"
         aria-labelledby="postPurchaseInviteTitle">

        <div class="w-full sm:max-w-md sm:rounded-2xl rounded-t-3xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto"
             data-invite-panel>

            {{-- En-tête --}}
            <div class="relative rounded-t-3xl sm:rounded-t-2xl bg-emerald-700 px-5 pt-6 pb-5 text-center text-white">
                {{-- Croix de fermeture : l'utilisateur n'est jamais piégé. --}}
                <button type="button"
                        data-invite-close
                        aria-label="Fermer l'invitation"
                        class="absolute right-3 top-3 rounded-full p-1.5 text-white/80 transition hover:bg-white/20 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/70">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <p class="text-3xl leading-none" aria-hidden="true">🎉</p>
                <h2 id="postPurchaseInviteTitle" class="mt-2 text-lg font-black">Achat confirmé !</h2>
                <p class="mt-1 text-xs text-emerald-50">
                    Votre commande <span class="font-mono font-bold">{{ $order->reference }}</span> a bien été enregistrée.
                </p>
            </div>

            <div class="px-5 py-5">
                @if ($total === 1)
                    <p class="text-center text-sm text-slate-600">
                        Comment trouvez-vous votre achat&nbsp;?
                    </p>
                @else
                    <p class="text-center text-sm text-slate-600">
                        Votre commande contient <strong class="text-slate-800">{{ $total }}</strong> élément(s) à évaluer.
                        Vous pouvez en noter un seul, plusieurs ou tous.
                    </p>
                @endif

                {{-- Produits achetés --}}
                @if ($products->isNotEmpty())
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($products as $item)
                            <li class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-2.5">
                                @if (!empty($item['image']))
                                    <img src="{{ $item['image'] }}" alt="{{ $item['nom'] }}"
                                         class="h-11 w-11 shrink-0 rounded-lg object-cover" loading="lazy" />
                                @else
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-sm font-black text-emerald-700">
                                        {{ mb_strtoupper(mb_substr((string) $item['nom'], 0, 1)) }}
                                    </span>
                                @endif

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-xs font-bold text-slate-800">{{ $item['nom'] }}</span>
                                    <span class="block truncate text-[11px] text-slate-500">
                                        {{ $item['producteur'] ?? 'Producteur' }} · x{{ $item['quantite'] }}
                                    </span>
                                </span>

                                {{-- Les étoiles sont un aperçu : cliquer « Noter maintenant » ouvre le vrai formulaire. --}}
                                <span class="flex shrink-0 items-center gap-0.5 text-amber-300" aria-hidden="true">
                                    @for ($s = 1; $s <= 5; $s++)
                                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 00.95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.58a1 1 0 00-.36 1.12l1.36 4.18c.3.92-.76 1.69-1.54 1.12l-3.56-2.58a1 1 0 00-1.18 0l-3.56 2.58c-.78.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 00-.36-1.12L1.74 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 00.95-.69L9.05 2.93z"/>
                                        </svg>
                                    @endfor
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Producteurs achetés --}}
                @if ($producers->isNotEmpty())
                    <ul class="mt-2.5 space-y-2.5">
                        @foreach ($producers as $producer)
                            <li class="flex items-center gap-3 rounded-xl border border-emerald-100 bg-emerald-50/60 p-2.5">
                                @if (!empty($producer['avatar']))
                                    <img src="{{ $producer['avatar'] }}" alt="{{ $producer['nom'] }}"
                                         class="h-11 w-11 shrink-0 rounded-full object-cover" loading="lazy" />
                                @else
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-black text-emerald-700">
                                        {{ mb_strtoupper(mb_substr((string) $producer['nom'], 0, 1)) }}
                                    </span>
                                @endif

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-xs font-bold text-slate-800">{{ $producer['nom'] }}</span>
                                    <span class="block text-[11px] text-slate-500">Producteur · {{ $producer['nombre_produits'] }} produit(s)</span>
                                </span>

                                <span class="flex shrink-0 items-center gap-0.5 text-amber-300" aria-hidden="true">
                                    @for ($s = 1; $s <= 5; $s++)
                                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 00.95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.58a1 1 0 00-.36 1.12l1.36 4.18c.3.92-.76 1.69-1.54 1.12l-3.56-2.58a1 1 0 00-1.18 0l-3.56 2.58c-.78.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 00-.36-1.12L1.74 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 00.95-.69L9.05 2.93z"/>
                                        </svg>
                                    @endfor
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Actions --}}
                <div class="mt-5 space-y-2">
                    {{-- Ouvre la page de notation du client, pré-remplie avec CETTE commande. --}}
                    <a href="{{ route('client.dashboard', ['tab' => 'reviews', 'order' => $order->id]) }}"
                       data-invite-review
                       class="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 00.95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.58a1 1 0 00-.36 1.12l1.36 4.18c.3.92-.76 1.69-1.54 1.12l-3.56-2.58a1 1 0 00-1.18 0l-3.56 2.58c-.78.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 00-.36-1.12L1.74 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 00.95-.69L9.05 2.93z"/>
                        </svg>
                        Noter maintenant
                    </a>

                    {{-- « Plus tard » : ferme et n'enregistre RIEN. Aucune erreur. --}}
                    <button type="button"
                            data-invite-close
                            class="w-full rounded-xl px-5 py-2.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-300">
                        Plus tard
                    </button>

                    <p class="pt-1 text-center text-[11px] leading-4 text-slate-400">
                        La notation est facultative. Votre commande reste valide même sans avis.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var root = document.getElementById('postPurchaseInvite');
            if (!root || root.dataset.ready) return;
            root.dataset.ready = '1';

            var panel = root.querySelector('[data-invite-panel]');
            var lastFocus = null;

            function open() {
                lastFocus = document.activeElement;
                root.classList.remove('hidden');
                root.classList.add('flex');
                document.body.style.overflow = 'hidden';
                var closeBtn = root.querySelector('[data-invite-close]');
                if (closeBtn) closeBtn.focus();
            }

            // « Plus tard », la croix, le fond ou Echap : même effet.
            // Fermer n'envoie aucune requête et ne modifie rien en base.
            function close() {
                root.classList.add('hidden');
                root.classList.remove('flex');
                document.body.style.overflow = '';
                if (lastFocus && lastFocus.focus) lastFocus.focus();
            }

            root.addEventListener('click', function (e) {
                var closer = e.target.closest('[data-invite-close]');
                if (closer) { close(); return; }
                // Clic sur le fond (mais pas à l'intérieur du panneau).
                if (!panel.contains(e.target)) close();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !root.classList.contains('hidden')) close();
            });

            // Invitation immédiate une fois le paiement confirmé.
            // différé d'un tick pour ne jamais ralentir l'affichage de la page.
            setTimeout(open, 400);
        })();
    </script>
@endif