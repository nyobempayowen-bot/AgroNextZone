@extends('admin.layout')

@section('title', 'ParamÃ¨tres')

@section('content')
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900">ParamÃ¨tres</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-1">Gestion des catÃ©gories de produits et informations gÃ©nÃ©rales de la plateforme.</p>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border-slate-200 bg-white p-5 shadow-xs">
        <h2 class="text-base font-bold text-slate-900">CatÃ©gories de produits</h2>

        <form method="POST" action="{{ route('admin.settings.categories.store') }}" class="mt-4 flex flex-wrap gap-2">
            @csrf
            <input type="text" name="nom" placeholder="Nom de la catÃ©gorie"
                   class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" required>
            <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition">Ajouter</button>
        </form>

        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($categories as $category)
                <li class="flex items-center justify-between py-2.5">
                    <div>
                        <span class="text-sm font-semibold text-slate-800">{{ $category->nom }}</span>
                        <span class="ml-2 text-xs text-slate-400">{{ $category->products()->count() }} offre(s)</span>
                    </div>
                    @if ($category->products()->count() === 0)
                        <form method="POST" action="{{ route('admin.settings.categories.delete', $category) }}"
                              onsubmit="return confirm('Supprimer cette catÃ©gorie ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-xl bg-white border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 transition">Supprimer</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="py-4 text-center text-sm text-slate-500">Aucune catÃ©gorie.</li>
            @endforelse
        </ul>
    </div>

    <div class="rounded-2xl border-slate-200 bg-white p-5 shadow-xs">
        <h2 class="text-base font-bold text-slate-900">Informations de la plateforme</h2>
        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Nom</dt><dd class="font-semibold text-slate-800">AgroNextZone</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Devise</dt><dd class="font-semibold text-slate-800">FCFA (XOF)</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Paiements</dt><dd class="font-semibold text-slate-800">Paiement simulÃ© (aucun PSP externe)</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">CatÃ©gories</dt><dd class="font-semibold text-slate-800">{{ $categories->count() }}</dd></div>
        </dl>
    </div>
</div>
@endsection
