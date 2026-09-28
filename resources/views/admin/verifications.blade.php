@extends('admin.layout')

@section('title', 'Producteurs à vérifier')

@section('content')
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900">Producteurs à vérifier</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-1">Comptes producteurs en attente de validation (CNI / infos professionnelles).</p>
</div>

<div class="rounded-2xl border-slate-200 bg-white shadow-xs overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Producteur</th>
                <th class="px-4 py-3">Contact</th>
                <th class="px-4 py-3">Dossier</th>
                <th class="px-4 py-3">Soumis le</th>
                <th class="px-4 py-3 text-right">Décision</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($pending as $v)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <span class="font-semibold text-slate-900 block">{{ $v->producer->name ?? '—' }}</span>
                        <span class="text-xs text-slate-500">{{ $v->producer->email ?? '' }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $v->producer->phone ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">
                        @if (!empty($v->document_path))
                            <a href="{{ asset('storage/' . $v->document_path) }}" target="_blank" class="text-emerald-700 font-semibold underline">Consulter le document</a>
                        @else
                            <span class="text-slate-400">Aucun document</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ optional($v->submitted_at)->format('d/m/Y H:i') ?? $v->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.verifications.decide', ['verification' => $v->id]) }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            @csrf
                            <input type="text" name="motif" placeholder="Motif (rejet)" class="rounded-xl border-slate-300 text-xs w-full sm:w-40">
                            <button type="submit" name="decision" value="approve" class="rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Valider</button>
                            <button type="submit" name="decision" value="reject" class="rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">Rejeter</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Aucun dossier en attente.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
