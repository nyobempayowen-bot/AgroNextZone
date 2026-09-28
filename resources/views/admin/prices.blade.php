@extends('admin.layout')

@section('title', 'Gestion des prix')

@section('content')
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900">Gestion des prix</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-1">Marché des prix en lecture seule : moyenne, minimum et maximum calculés à partir des offres réelles en base.</p>
</div>

<div class="rounded-2xl border-slate-200 bg-white shadow-xs overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Produit</th>
                <th class="px-4 py-3">Unité</th>
                <th class="px-4 py-3">Prix moyen</th>
                <th class="px-4 py-3">Prix min</th>
                <th class="px-4 py-3">Prix max</th>
                <th class="px-4 py-3">Offres</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($prices as $price)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $price->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $price->unit }}</td>
                    <td class="px-4 py-3 font-semibold text-emerald-700">{{ number_format((float) $price->avg_price, 0, ',', ' ') }} FCFA</td>
                    <td class="px-4 py-3 text-slate-600">{{ number_format((float) $price->min_price, 0, ',', ' ') }} FCFA</td>
                    <td class="px-4 py-3 text-slate-600">{{ number_format((float) $price->max_price, 0, ',', ' ') }} FCFA</td>
                    <td class="px-4 py-3 text-slate-500">{{ $price->offers_count }} offre(s)</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucune donnée de prix.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
