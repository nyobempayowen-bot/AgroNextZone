@extends('admin.layout')
@section('title', 'Signalements')
@section('content')
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900">Offres signalées</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-1">Signalements en attente. Approuver suspend l'offre ; rejeter clôt le signalement.</p>
</div>
<div class="rounded-2xl border-slate-200 bg-white shadow-xs overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Offre</th>
                <th class="px-4 py-3">Producteur</th>
                <th class="px-4 py-3">Signalé par</th>
                <th class="px-4 py-3">Motif</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($reports as $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $r->product?->name ?? 'Offre supprimée' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $r->product?->producer?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $r->reporter?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600 max-w-xs">{{ $r->reason ?? 'Non précisé' }}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <form method="POST" action="{{ route('admin.reports.decide', ['report' => $r->id]) }}">
                                @csrf
                                <input type="hidden" name="decision" value="approve">
                                <button type="submit" class="rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">Suspendre l'offre</button>
                            </form>
                            <form method="POST" action="{{ route('admin.reports.decide', ['report' => $r->id]) }}">
                                @csrf
                                <input type="hidden" name="decision" value="dismiss">
                                <button type="submit" class="rounded-xl bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-300">Rejeter</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucun signalement en attente.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
