<!-- AI Recommendation Bubble + Panel -->
<div id="ai-bubble-root" class="fixed bottom-6 right-6 z-50">
    <button id="ai-bubble-btn" aria-expanded="false" aria-controls="ai-bubble-panel" class="flex items-center gap-2 p-3 rounded-full bg-emerald-600 text-white shadow-lg hover:scale-105 transition-transform">
        <span class="h-8 w-8 flex items-center justify-center rounded-full bg-white/10">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 9h8m-7 4h.01M15 13h.01M7 19h10a3 3 0 003-3V8a3 3 0 00-3-3h-1.5L14 3h-4L8.5 5H7a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        </span>
        <span class="hidden sm:inline font-bold">AgroBot</span>
    </button>

    <div id="ai-bubble-panel" class="hidden mt-3 w-[360px] max-w-[95vw] rounded-2xl bg-white shadow-xl ring-1 ring-black/5 overflow-hidden">
        <div class="p-3 border-b flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="h-8 w-8 flex items-center justify-center rounded-full bg-emerald-600 text-white">AI</div>
                <div>
                    <div class="text-sm font-bold text-slate-900">AgroBot</div>
                    <div class="text-xs text-slate-500">Assistant intelligent</div>
                </div>
            </div>
            <button id="ai-bubble-close" class="text-slate-500 hover:text-slate-800" aria-label="Fermer">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <div class="flex border-b text-sm">
            <button id="tab-reco" type="button" class="flex-1 py-2 text-center font-semibold text-emerald-700 border-b-2 border-emerald-600" aria-selected="true" role="tab">Recommandations</button>
            <button id="tab-chat" type="button" class="flex-1 py-2 text-center font-semibold text-slate-500 border-b-2 border-transparent hover:text-slate-700" aria-selected="false" role="tab">Assistant repas</button>
        </div>

        <!-- Onglet 1 : Recommandations -->
        <div id="pane-reco" class="p-3">
            <div id="ai-bubble-loading" class="flex items-center justify-center py-6">
                <svg class="animate-spin h-6 w-6 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            </div>

            <div id="ai-bubble-list" class="space-y-3 hidden">
                <!-- Recommendations will be inserted here -->
            </div>

            <div id="ai-bubble-empty" class="text-center text-sm text-slate-500 hidden py-6">Aucune recommandation disponible pour le moment.</div>
        </div>

        <!-- Onglet 2 : Chat assistant repas -->
        <div id="pane-chat" class="hidden flex flex-col" style="height: 420px;">
            <div class="flex items-center justify-between px-3 pt-2">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Conversation</span>
                <button id="chat-reset" type="button" class="text-[11px] font-semibold text-slate-400 hover:text-rose-600">Effacer</button>
            </div>
            <div id="chat-messages" class="flex-1 overflow-y-auto p-3 space-y-2 text-sm" style="min-height: 280px;">
                <div class="text-center text-xs text-slate-400 py-4">Dites ce que vous voulez cuisiner (ex. "Je veux préparer du Ndolé").</div>
            </div>
            <form id="chat-form" class="p-2 border-t flex items-center gap-2">
                <input id="chat-input" type="text" autocomplete="off" placeholder="Ex. : qu'est-ce qu'il faut pour du Koki ?"
                       class="flex-1 text-sm border rounded-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500" />
                <button type="submit" class="p-2 rounded-full bg-emerald-600 text-white hover:bg-emerald-700" aria-label="Envoyer">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 0l-6 6m6-6l6 6"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    (function(){
        const btn = document.getElementById('ai-bubble-btn');
        const panel = document.getElementById('ai-bubble-panel');
        const close = document.getElementById('ai-bubble-close');
        const loading = document.getElementById('ai-bubble-loading');
        const list = document.getElementById('ai-bubble-list');
        const empty = document.getElementById('ai-bubble-empty');

        function openPanel(){
            panel.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
            // show loading
            loading.classList.remove('hidden');
            list.classList.add('hidden');
            empty.classList.add('hidden');

            // Bloc C (2/2): chargement asynchrone depuis l'endpoint backend.
            // La clé API (OpenRouter) ne quitte jamais le serveur : elle vit
            // uniquement dans .env et l'appel HTTP est fait par Laravel.
            fetch('/recommandations-ia', { headers: { 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)))
                .then(data => {
                    loading.classList.add('hidden');
                    const recos = (data && data.recommendations) || [];
                    if (recos.length === 0) {
                        empty.classList.remove('hidden');
                        return;
                    }
                    list.innerHTML = '';
                    recos.forEach(p => {
                        const item = document.createElement('a');
                        item.href = '/produit/' + (p.slug || '');
                        item.className = 'flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50';
                        const wrap = document.createElement('div');
                        const name = document.createElement('div');
                        name.className = 'text-sm font-semibold text-slate-900';
                        name.textContent = p.name || '';
                        const meta = document.createElement('div');
                        meta.className = 'text-xs text-slate-500';
                        meta.textContent = (p.region ? p.region + ' — ' : '') + (p.reason || '');
                        wrap.appendChild(name);
                        wrap.appendChild(meta);
                        item.appendChild(wrap);
                        list.appendChild(item);
                    });
                    list.classList.remove('hidden');
                })
                .catch(() => {
                    loading.classList.add('hidden');
                    empty.textContent = 'Recommandations indisponibles pour le moment.';
                    empty.classList.remove('hidden');
                });
        }

        function closePanel(){
            panel.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        }

        btn.addEventListener('click', function(e){
            e.stopPropagation();
            if (panel.classList.contains('hidden')) openPanel(); else closePanel();
        });

        close.addEventListener('click', function(e){
            e.stopPropagation();
            closePanel();
        });

        // close on outside click
        document.addEventListener('click', function(e){
            if (!panel.contains(e.target) && !btn.contains(e.target)) closePanel();
        });

        // close on escape
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape') closePanel();
        });

        /* ----- Onglets : Recommandations / Assistant repas ----- */
        const tabReco = document.getElementById('tab-reco');
        const tabChat = document.getElementById('tab-chat');
        const paneReco = document.getElementById('pane-reco');
        const paneChat = document.getElementById('pane-chat');

        function showTab(tab){
            const isReco = tab === 'reco';
            tabReco.classList.toggle('text-emerald-700', isReco);
            tabReco.classList.toggle('border-emerald-600', isReco);
            tabReco.classList.toggle('text-slate-500', !isReco);
            tabReco.classList.toggle('border-transparent', !isReco);
            tabReco.setAttribute('aria-selected', isReco);
            tabChat.classList.toggle('text-emerald-700', !isReco);
            tabChat.classList.toggle('border-emerald-600', !isReco);
            tabChat.classList.toggle('text-slate-500', isReco);
            tabChat.classList.toggle('border-transparent', isReco);
            tabChat.setAttribute('aria-selected', !isReco);
            paneReco.classList.toggle('hidden', !isReco);
            paneChat.classList.toggle('hidden', isReco);
            paneChat.classList.toggle('flex', !isReco);
            if (!isReco) { loadHistory(); document.getElementById('chat-input').focus(); }
        }

        tabReco.addEventListener('click', function(){ showTab('reco'); });
        tabChat.addEventListener('click', function(){ showTab('chat'); });

        /* ----- Chat assistant repas ----- */
        const chatMessages = document.getElementById('chat-messages');
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        let historyLoaded = false;

        function escapeHtml(s){
            const d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        function addMsg(role, text){
            const wrap = document.createElement('div');
            wrap.className = role === 'user'
                ? 'flex justify-end'
                : 'flex justify-start';
            wrap.innerHTML = '<div class="max-w-[85%] rounded-2xl px-3 py-2 whitespace-pre-line ' +
                (role === 'user'
                    ? 'bg-emerald-600 text-white rounded-br-sm'
                    : 'bg-slate-100 text-slate-800 rounded-bl-sm')
                + '">' + escapeHtml(text) + '</div>';
            chatMessages.appendChild(wrap);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            return wrap;
        }

        function addProducts(products){
            if (!products || products.length === 0) return;
            const box = document.createElement('div');
            box.className = 'space-y-1 pt-1';
            products.forEach(p => {
                const a = document.createElement('a');
                a.href = '/produit/' + (p.slug || '');
                a.className = 'block rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 hover:bg-emerald-100';
                a.innerHTML =
                    '<div class="text-sm font-semibold text-slate-900">' + escapeHtml(p.name) + '</div>'
                    + '<div class="text-xs text-slate-600">'
                    + escapeHtml((p.price != null ? p.price + ' FCFA/' + (p.unit || '') : ''))
                    + (p.producer ? ' — ' + escapeHtml(p.producer) : '')
                    + (p.score != null ? ' · fiabilité ' + p.score + '/100' : '')
                    + '</div>';
                box.appendChild(a);
            });
            chatMessages.appendChild(box);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function loadHistory(){
            if (historyLoaded) return;
            historyLoaded = true;
            fetch("{{ route('assistant-repas.history') }}", { headers: { 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)))
                .then(data => {
                    (data.history || []).forEach(t => addMsg(t.role === 'client' ? 'user' : 'bot', t.text));
                })
                .catch(() => {});
        }

        chatForm.addEventListener('submit', function(e){
            e.preventDefault();
            const msg = chatInput.value.trim();
            if (msg === '') return;
            chatInput.value = '';
            addMsg('user', msg);
            const thinking = addMsg('bot', '…');

            fetch("{{ route('assistant-repas.send') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ message: msg })
            })
                .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)))
                .then(data => {
                    thinking.remove();
                    addMsg('bot', data.reply || 'Réponse vide.');
                    if (data.products && data.products.length > 0) {
                        addProducts(data.products);
                    } else if (!data.api_error && data.known_dish) {
                        addMsg('bot', "Aucun produit disponible pour ces ingrédients sur la plateforme pour le moment.");
                    }
                })
                .catch(() => {
                    thinking.remove();
                    addMsg('bot', "L'assistant est temporairement indisponible. Veuillez réessayer.");
                });
        });

        // Effacer la conversation (réinitialise l'historique en session)
        document.getElementById('chat-reset').addEventListener('click', function(){
            fetch("{{ route('assistant-repas.reset') }}", {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            }).finally(() => {
                chatMessages.innerHTML = '';
                addMsg('bot', 'Conversation effacée. Quel plat souhaitez-vous préparer ?');
            });
        });
    })();
</script>
