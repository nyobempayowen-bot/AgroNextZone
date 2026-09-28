@extends("admin.layout")

@section("title", "Détail utilisateur")

@section("content")
<div class="mb-6">
    <a href="{{ route("admin.users") }}" class="text-xs font-semibold text-emerald-700 hover:underline">&larr; Retour aux utilisateurs</a>
    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900 mt-2">{{ $account->name }}</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-1">{{ $account->email }} &middot; rôle : <strong>{{ $account->role }}</strong></p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
    <div class="lg:col-span-2 space-y-4">
        <div class="rounded-2xl border-slate-200 bg-white p-5 shadow-xs">
            <h2 class="font-bold text-slate-900 mb-3">Informations</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs text-slate-500">Téléphone</dt><dd class="font-semibold">{{ $account->phone ?? "—" }}</dd></div>
                <div><dt class="text-xs text-slate-500">Statut du compte</dt>
                    <dd class="font-semibold {{ ($account->status ?? "active") === "suspended" ? "text-rose-600" : "text-emerald-700" }}">{{ $account->status ?? "active" }}</dd></div>
                <div><dt class="text-xs text-slate-500">Commandes (client)</dt><dd class="font-semibold">{{ $account->orders_count }}</dd></div>
                <div><dt class="text-xs text-slate-500">Transactions (producteur)</dt><dd class="font-semibold">{{ $account->producer_transactions_count }}</dd></div>
                <div><dt class="text-xs text-slate-500">Offres publiées</dt><dd class="font-semibold">{{ $account->products_count }}</dd></div>
                <div><dt class="text-xs text-slate-500">Vérifié</dt><dd class="font-semibold">{{ $account->is_verified ? "Oui" : "Non" }}</dd></div>
            </dl>
        </div>

        @if ($account->role === "producer" && $account->producerVerification)
            <div class="rounded-2xl border-slate-200 bg-white p-5 shadow-xs">
                <h2 class="font-bold text-slate-900 mb-3">Dossier de vérification</h2>
                <p class="text-sm"><strong>Statut :</strong> {{ $account->producerVerification->status }}</p>
                @if ($account->producerVerification->rejection_reason)
                    <p class="text-sm"><strong>Motif de rejet :</strong> {{ $account->producerVerification->rejection_reason }}</p>
                @endif
            </div>
        @endif
    </div>

    <div class="space-y-4">
        <div class="rounded-2xl border-slate-200 bg-white p-5 shadow-xs">
            <h2 class="font-bold text-slate-900 mb-3">Actions</h2>
            @if (($account->status ?? "active") !== "suspended")
                @if ($account->role !== "admin")
                    <form method="POST" action="{{ route("admin.users.suspend", ["user" => $account->id]) }}">
                        @csrf
                        <label class="block text-xs text-slate-500 mb-1">Motif (optionnel)</label>
                        <textarea name="motif" rows="2" class="w-full rounded-xl border-slate-300 text-sm" maxlength="500"></textarea>
                        <button type="submit" class="mt-2 w-full rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700">Suspendre le compte</button>
                    </form>
                @else
                    <p class="text-xs text-slate-500">Un administrateur ne peut pas être suspendu.</p>
                @endif
            @else
                <form method="POST" action="{{ route("admin.users.reactivate", ["user" => $account->id]) }}">
                    @csrf
                    <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Réactiver le compte</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
