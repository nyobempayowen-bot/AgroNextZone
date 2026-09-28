<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant repas | AgroNextZone</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-100 text-slate-800 antialiased min-h-screen flex flex-col">
    <header class="border-b border-emerald-100 bg-white/90 backdrop-blur-sm sticky top-0 z-30">
        <div class="max-w-3xl mx-auto px-4 flex items-center justify-between h-20">
            <h1 class="text-lg font-black text-emerald-900">🤖 Assistant repas IA</h1>
            <div class="flex gap-3 text-sm">
                <a href="{{ route('home') }}" class="font-semibold text-slate-600 hover:text-emerald-700">Marketplace</a>
                <button id="chat-reset" class="font-semibold text-slate-500 hover:text-rose-600">Effacer</button>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full max-w-3xl mx-auto px-4 py-6 flex flex-col">
        <div id="chat-box" class="flex-1 space-y-4 overflow-y-auto">
            @forelse ($history as $turn)
                <div class="{{ $turn['role'] === 'client' ? 'flex justify-end' : 'flex justify-start' }}">
                    <div class="{{ $turn['role'] === 'client'
                        ? 'bg-emerald-600 text-white rounded-2xl rounded-br-sm'
                        : 'bg-white text-slate-800 rounded-2xl rounded-bl-sm ring-1 ring-black/5' }} max-w-[85%] px-4 py-3 text-sm whitespace-pre-line">{{ $turn['text'] }}</div>
                </div>
            @empty
                <div class="flex justify-start">
                    <div class="bg-white text-slate-800 rounded-2xl rounded-bl-sm ring-1 ring-black/5 max-w-[85%] px-4 py-3 text-sm">
                        Bonjour ! Dites-moi quel plat vous voulez préparer (ex. « je veux faire du Ndolé » ou « un plat avec manioc et plantain ») et je vous liste les ingrédients disponibles chez nos producteurs.
                    </div>
                </div>
            @endforelse
        </div>

        <div id="chat-typing" class="hidden py-2 text-xs text-slate-400">L'assistant réfléchit…</div>

        <form id="chat-form" class="mt-4 flex gap-2 sticky bottom-4">
            <input id="chat-input" type="text" autocomplete="off" placeholder="Ex : Qu'est-ce qu'il faut pour du Ndolé ?"
                   class="flex-1 rounded-xl border-slate-300 bg-white px-4 py-3 text-sm shadow-sm" />
            <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Envoyer</button>
        </form>

        @if (session('error'))
            <p class="mt-2 text-xs text-rose-600">{{ session('error') }}</p>
        @endif
    </main>

    <template id="tpl-products">
        <div class="flex justify-start">
            <div class="bg-emerald-50 ring-1 ring-emerald-200 rounded-2xl rounded-bl-sm px-4 py-3 max-w-[85%] w-full">
                <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-700 mb-2">Produits disponibles (mieux notés en premier)</p>
                <div class="tpl-list space-y-2"></div>
            </div>
        </div>
    </template>

    <script>
        (function () {
            const box = document.getElementById('chat-box');
            const form = document.getElementById('chat-form');
            const input = document.getElementById('chat-input');
            const typing = document.getElementById('chat-typing');

            const scroll = () => box.scrollTop = box.scrollHeight;
            scroll();

            function addBubble(role, text) {
                const wrap = document.createElement('div');
                wrap.className = role === 'client' ? 'flex justify-end' : 'flex justify-start';
                const bub = document.createElement('div');
                bub.className = (role === 'client'
                    ? 'bg-emerald-600 text-white rounded-2xl rounded-br-sm'
                    : 'bg-white text-slate-800 rounded-2xl rounded-bl-sm ring-1 ring-black/5') + ' max-w-[85%] px-4 py-3 text-sm whitespace-pre-line';
                bub.textContent = text;
                wrap.appendChild(bub);
                box.appendChild(wrap);
                scroll();
            }

            function addProducts(products) {
                if (!products || products.length === 0) {
                    const wrap = document.createElement('div');
                    wrap.className = 'flex justify-start';
                    wrap.innerHTML = '<div class="bg-amber-50 ring-1 ring-amber-200 rounded-2xl rounded-bl-sm px-4 py-3 text-sm text-amber-800">Aucun produit correspondant n\'est disponible en ce moment (rupture de stock ou aucun producteur). Revenez plus tard ou précisez d\'autres ingrédients.</div>';
                    box.appendChild(wrap);
                    scroll();
                    return;
                }

                const tpl = document.getElementById('tpl-products').content.cloneNode(true);
                const list = tpl.querySelector('.tpl-list');
                products.forEach(p => {
                    const a = document.createElement('a');
                    a.href = '/produit/' + (p.slug || '');
                    a.className = 'block rounded-lg bg-white px-3 py-2 ring-1 ring-black/5 hover:ring-emerald-300';
                    a.textContent = p.name + ' — ' + p.price + ' F/' + p.unit + ' · ' + p.producer + ' · fiabilité ' + p.score + '/100';
                    list.appendChild(a);
                });
                box.appendChild(tpl);
                scroll();
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const message = input.value.trim();
                if (!message) return;

                input.value = '';
                addBubble('client', message);
                typing.classList.remove('hidden');

                try {
                    const resp = await fetch('{{ route("assistant-repas.send") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ message })
                    });
                    const data = await resp.json();
                    typing.classList.add('hidden');
                    addBubble('assistant', data.reply || 'Réponse vide.');
                    addProducts(data.products);
                } catch (err) {
                    typing.classList.add('hidden');
                    addBubble('assistant', "L'assistant est temporairement indisponible. Veuillez réessayer dans quelques instants.");
                }
            });

            document.getElementById('chat-reset').addEventListener('click', async () => {
                await fetch('{{ route("assistant-repas.reset") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                location.reload();
            });
        })();
    </script>
</body>
</html>
