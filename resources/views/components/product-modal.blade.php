<!-- Product Detail Modal (hidden by default) -->
<div id="product-modal" class="fixed inset-0 z-50 hidden items-center justify-center px-4 py-6">
    <div id="product-modal-backdrop" class="absolute inset-0 bg-black/40"></div>

    <div class="relative z-10 w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-lg">
        <div class="flex items-center justify-between p-4 border-b sticky top-0 bg-white z-10">
            <h3 id="pm-name" class="text-lg font-bold text-slate-900 truncate pr-2">Produit</h3>
            <button id="pm-close" class="text-slate-500 hover:text-slate-800 p-1 rounded-lg hover:bg-slate-100 transition flex-shrink-0" aria-label="Fermer">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <div class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <div>
                <img id="pm-image" src="" alt="" class="w-full h-48 sm:h-64 object-cover rounded-xl" />
            </div>

            <div class="flex flex-col gap-4">
                <div>
                    <p id="pm-region" class="text-xs sm:text-sm text-slate-500"></p>
                    <p id="pm-price" class="text-xl sm:text-2xl font-black text-emerald-700"></p>
                </div>

                <p id="pm-desc" class="text-xs sm:text-sm text-slate-600 leading-relaxed"></p>

                <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3">
                    @if (Auth::check() && Auth::user()->role === 'client')
                        <!-- Add to cart -->
                        <div>
                            <form id="pm-add-form" method="POST" action="">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button id="pm-add" type="submit" class="w-full rounded-xl bg-emerald-600 px-3 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-white hover:bg-emerald-700 transition">Ajouter au panier</button>
                            </form>
                        </div>
                    @endif

                    <!-- Discuss -->
                    <div>
                        <a id="pm-discuss" href="#" class="w-full inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-white px-3 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-emerald-700 hover:bg-slate-50 transition">Discuter</a>
                    </div>

                    <!-- View profile -->
                    <div>
                        <a id="pm-profile" href="#" class="w-full inline-flex items-center justify-center rounded-xl bg-sky-500 px-3 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-white hover:brightness-95 transition">Profil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    (function(){
        const modal = document.getElementById('product-modal');
        const backdrop = document.getElementById('product-modal-backdrop');
        const closeBtn = document.getElementById('pm-close');

        function openModal(product){
            document.getElementById('pm-name').textContent = product.nom || '';
            document.getElementById('pm-image').src = product.image || '';
            document.getElementById('pm-image').alt = product.nom || '';
            document.getElementById('pm-region').textContent = product.region || '';
            document.getElementById('pm-price').textContent = product.prix || '';
            document.getElementById('pm-desc').textContent = product.description || '';

            const addForm = document.getElementById('pm-add-form');
            if (addForm) {
                addForm.action = '/panier/ajouter/' + (product.slug || '');
            }

            // discuss link - if product has producer_id use it else fallback to 1
            const producerId = product.producer_id || product.producteur_id || 1;
            document.getElementById('pm-discuss').href = '/discussion/' + producerId;

            // profile link - fallback to producer id 1
            document.getElementById('pm-profile').href = '/profil/' + (producerId);

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal(){
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // open when clicking a product-card button
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-open-product');
            if (!btn) return;
            e.preventDefault();
            try {
                const product = JSON.parse(btn.getAttribute('data-product'));
                openModal(product);
            } catch (err) {
                console.error('Invalid product data', err);
            }
        });

        // keyboard accessibility: open modal when focused product-card receives Enter/Space
        document.addEventListener('keydown', function (e) {
            const active = document.activeElement;
            if (!active) return;
            if (active.classList && active.classList.contains('js-open-product') && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                try {
                    const product = JSON.parse(active.getAttribute('data-product'));
                    openModal(product);
                } catch (err) {
                    console.error('Invalid product data', err);
                }
            }
        });

        // close handlers
        closeBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', closeModal);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });
    })();
</script>
