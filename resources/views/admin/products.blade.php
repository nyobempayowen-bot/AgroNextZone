@extends('admin.layout')

@section('title', 'Offres / Produits')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900">Offres / Produits</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">Catalogue complet. Les offres suspendues n'apparaissent plus sur la Marketplace.</p>
    </div>
    <form method="GET" action="{{ route('admin.products') }}" class="flex items-center gap-2">
        <input type="text" name="recherche" value="{{ $search }}" placeholder="Rechercher une offre…" class="rounded-xl border-slate-300 text-sm w-full sm:w-56">
        <button type="submit" class="rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Rechercher</button>
    </form>
</div>

<div class="rounded-2xl border-slate-200 bg-white shadow-xs overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Offre</th>
                <th class="px-4 py-3">Producteur</th>
                <th class="px-4 py-3">Prix</th>
                <th class="px-4 py-3">Stock</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($products as $p)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <span class="font-semibold text-slate-900 block">{{ $p->name }}</span>
                        <span class="text-xs text-slate-500">{{ $p->category->name ?? 'Sans catégorie' }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $p->producer->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ number_format((float) $p->price, 0, ',', ' ') }} FCFA / {{ $p->unit }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $p->stock_quantity }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                            {{ $p->status === 'published' ? 'bg-emerald-100 text-emerald-700' : ($p->status === 'suspended' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ $p->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @if ($p->status === 'published')
                                <form method="POST" action="{{ route('admin.products.status', ['product' => $p->id]) }}">
                                    @csrf
                                    <input type="hidden" name="statut" value="suspended">
                                    <button type="submit" class="rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">Suspendre</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.products.status', ['product' => $p->id]) }}">
                                    @csrf
                                    <input type="hidden" name="statut" value="published">
                                    <button type="submit" class="rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Approuver</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucune offre.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $products->links() }}</div>
@endsection
