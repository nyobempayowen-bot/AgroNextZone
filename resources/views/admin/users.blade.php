@extends("admin.layout")

@section("title", "Utilisateurs")

@section("content")
<div class="mb-6 flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900">Gestion des utilisateurs</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">Clients et producteurs inscrits sur la plateforme.</p>
    </div>
    <form method="GET" action="{{ route("admin.users") }}" class="flex gap-2">
        <input type="hidden" name="role" value="{{ $selectedRole }}">
        <input type="text" name="recherche" value="{{ $search }}" placeholder="Nom, email ou téléphone..."
               class="rounded-xl border-slate-300 text-sm w-52 sm:w-64">
        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Recher</button>
    </form>
</div>

<div class="mb-4 flex-wrap gap-2">
    @foreach (["all" => "Tous", "client" => "Clients", "producer" => "Producteurs", "admin" => "Admins"] as $key => $label)
        <a href="{{ route("admin.users", ["role" => $key, "recherche" => $search]) }}"
           class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $selectedRole === $key ? "bg-slate-900 text-white" : "bg-white border-slate-200 text-slate-600 hover:bg-slate-50" }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="rounded-2xl border-slate-200 bg-white shadow-xs overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Utilisateur</th>
                <th class="px-4 py-3">Rôle</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3">Commandes</th>
                <th class="px-4 py-3">Inscription</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($users as $u)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <span class="font-semibold text-slate-900 block">{{ $u->name }}</span>
                        <span class="text-xs text-slate-500">{{ $u->email }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $u->role === "producer" ? "bg-amber-50 text-amber-700" : ($u->role === "admin" ? "bg-slate-900 text-white" : "bg-emerald-50 text-emerald-700") }}">
                            {{ $u->role }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ ($u->status ?? "active") === "suspended" ? "bg-rose-50 text-rose-700" : "bg-emerald-50 text-emerald-700" }}">
                            {{ $u->status ?? "active" }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $u->orders_count }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $u->created_at->format("d/m/Y") }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route("admin.users.show", ["user" => $u->id]) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">Détail</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucun utilisateur trouvé.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection
