<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion | AgroNextZone</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-100 text-slate-800 antialiased min-h-screen flex flex-col">
    @include('components.sidebar')

    <header class="border-b border-emerald-100 bg-white/90 backdrop-blur-sm sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="p-2 rounded-lg hover:bg-slate-100 transition text-slate-700 md:hidden" aria-label="Ouvrir le menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <div class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-700 shadow-sm ring-1 ring-emerald-200 flex-shrink-0">
                            <span class="text-sm sm:text-base font-black text-white tracking-tight">AN</span>
                        </div>
                        <div class="flex flex-col leading-none">
                            <span class="text-base sm:text-lg font-black text-emerald-900">AgroNextZone</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('messagerie') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        <span aria-hidden="true">←</span><span class="hidden sm:inline">Conversations</span><span class="sm:hidden">Retour</span>
                    </a>
                    <a href="{{ route('profil', ['id' => $producerId]) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 sm:px-4 py-2 text-xs sm:text-sm font-semibold text-emerald-700 hover:bg-emerald-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="hidden sm:inline">Profil du producteur</span>
                    <span class="sm:hidden">Profil</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-4xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="rounded-2xl sm:rounded-[30px] border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col">
            <div class="flex items-center justify-between border-b border-slate-200 bg-gradient-to-r from-emerald-600 to-emerald-700 px-4 sm:px-6 py-3.5 sm:py-4 text-white">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="{{ $producteur['avatar'] }}" alt="{{ $producteur['nom'] }}" class="h-10 w-10 sm:h-12 sm:w-12 rounded-full border-2 border-white/60 object-cover flex-shrink-0" />
                    <div class="min-w-0">
                        <p class="text-[10px] sm:text-xs uppercase tracking-[0.18em] text-emerald-100">Interlocuteur</p>
                        <h1 class="text-base sm:text-xl font-bold truncate">{{ $producteur['nom'] }}</h1>
                    </div>
                </div>
                <span class="rounded-full bg-emerald-200/20 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-50 flex-shrink-0">En ligne</span>
            </div>

            {{-- Flash success --}}
            @if (session('success'))
                <div class="bg-emerald-50 border-b border-emerald-100 px-4 py-2 text-xs text-emerald-700 font-semibold">
                    ✓ {{ session('success') }}
                </div>
            @endif

            {{-- Zone messages --}}
            <div id="chat-messages" class="h-[340px] sm:h-[440px] overflow-y-auto bg-slate-50 p-4 sm:p-5 space-y-3">
                @foreach ($messages as $message)
                    <div class="flex {{ $message['sender'] === 'me' ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message['id'] }}">

                        {{-- Groupe bulle + bouton modifier (mes messages seulement) --}}
                        <div class="flex flex-col {{ $message['sender'] === 'me' ? 'items-end' : 'items-start' }} max-w-[88%] sm:max-w-[80%] group">

                            {{-- Vue normale --}}
                            <div class="message-view w-full">
                                <div class="rounded-2xl px-4 py-2.5 sm:py-3 text-xs sm:text-sm break-words
                                    {{ $message['sender'] === 'me'
                                        ? 'bg-emerald-600 text-white rounded-br-md shadow-xs'
                                        : 'bg-white text-slate-700 border border-slate-200 rounded-bl-md shadow-xs' }}">
                                    {{ $message['text'] }}
                                </div>

                                <div class="flex items-center gap-1.5 mt-0.5 {{ $message['sender'] === 'me' ? 'justify-end' : 'justify-start' }}">
                                    <span class="text-[10px] text-slate-400">{{ $message['time'] }}</span>
                                    @if ($message['is_edited'])
                                        <span class="text-[10px] text-slate-400 italic">· Modifié</span>
                                    @endif
                                    @if ($message['sender'] === 'me')
                                        {{-- Bouton Modifier discret, visible au hover --}}
                                        <button
                                            type="button"
                                            onclick="startEdit({{ $message['id'] }}, {{ json_encode($message['text']) }})"
                                            class="opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity ml-1 p-1 rounded-lg hover:bg-slate-200/60 text-slate-400 hover:text-emerald-600"
                                            title="Modifier ce message"
                                            aria-label="Modifier le message"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.172-8.172z"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Vue édition (masquée par défaut) --}}
                            @if ($message['sender'] === 'me')
                                <div class="message-edit hidden w-full mt-1" id="edit-{{ $message['id'] }}">
                                    <form
                                        method="POST"
                                        action="{{ route('message.update', ['message' => $message['id']]) }}"
                                        onsubmit="return validateEdit(this)"
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <textarea
                                            name="message"
                                            rows="2"
                                            class="w-full resize-none rounded-xl border border-emerald-300 bg-white px-3.5 py-2.5 text-xs sm:text-sm text-slate-700 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none transition"
                                            placeholder="Modifier votre message..."
                                        ></textarea>
                                        <div class="flex justify-end gap-2 mt-1.5">
                                            <button
                                                type="button"
                                                onclick="cancelEdit({{ $message['id'] }})"
                                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition"
                                            >Annuler</button>
                                            <button
                                                type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-700 transition"
                                            >Enregistrer</button>
                                        </div>
                                    </form>
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Formulaire d'envoi --}}
            <form method="POST" action="{{ route('discussion.send', ['id' => $producerId]) }}" class="border-t border-slate-200 bg-white p-3 sm:p-4">
                @csrf
                <div class="flex flex-col sm:flex-row gap-2.5 sm:gap-3">
                    <textarea name="message" rows="2" required placeholder="Écrivez votre message..." class="w-full resize-none rounded-xl sm:rounded-2xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 sm:py-3 text-xs sm:text-sm text-slate-700 placeholder:text-slate-400 focus:border-emerald-300 focus:bg-white focus:outline-none transition"></textarea>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl sm:rounded-2xl bg-emerald-600 px-5 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-white hover:bg-emerald-700 transition flex-shrink-0">
                        <span>Envoyer</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500 mt-auto">
        &copy; {{ date('Y') }} AgroNextZone — Marketplace Agricole du Cameroun.
    </footer>

    <script>
        /**
         * Passe un message en mode édition.
         * @param {number} id     - ID du message en base
         * @param {string} text   - Contenu actuel (pré-rempli dans le textarea)
         */
        function startEdit(id, text) {
            // Masque la vue normale, affiche la vue édition.
            const wrapper = document.querySelector('[data-message-id="' + id + '"]');
            if (!wrapper) return;

            const viewEl = wrapper.querySelector('.message-view');
            const editEl = document.getElementById('edit-' + id);
            if (!viewEl || !editEl) return;

            const textarea = editEl.querySelector('textarea');
            textarea.value = text;           // Pré-remplit le contenu original.
            textarea.dataset.original = text; // Sauvegarde pour annulation.

            viewEl.classList.add('hidden');
            editEl.classList.remove('hidden');
            textarea.focus();
            // Place le curseur en fin de texte.
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        }

        /**
         * Annule la modification : restaure la vue normale sans perte du contenu.
         * @param {number} id - ID du message
         */
        function cancelEdit(id) {
            const wrapper = document.querySelector('[data-message-id="' + id + '"]');
            if (!wrapper) return;

            const viewEl = wrapper.querySelector('.message-view');
            const editEl = document.getElementById('edit-' + id);
            if (!viewEl || !editEl) return;

            editEl.classList.add('hidden');
            viewEl.classList.remove('hidden');
        }

        /**
         * Validation côté client avant envoi : empêche un message vide.
         * @param {HTMLFormElement} form
         * @returns {boolean}
         */
        function validateEdit(form) {
            const textarea = form.querySelector('textarea[name="message"]');
            if (!textarea || textarea.value.trim() === '') {
                textarea.classList.add('border-rose-400');
                textarea.focus();
                return false;
            }
            return true;
        }

        // Scroll automatique vers le bas à l'ouverture de la page.
        document.addEventListener('DOMContentLoaded', function () {
            var chatBox = document.getElementById('chat-messages');
            if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
        });
    </script>
</body>
</html>
