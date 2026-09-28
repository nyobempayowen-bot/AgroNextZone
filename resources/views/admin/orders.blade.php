@extends('admin.layout')

@section('title', 'Commandes')

@section('content')
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900">Commandes</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-1">Vue d'ensemble en lecture seule. L'administration supervise — la logique métier client/producteur n'est pas dupliquée.</p>
</div>

<div class="mb-4 flex flex-wrap items-center gap-2">
    <a href="{{ route('admin.orders', ['statut' => 'all']) }}"
       class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $selectedStatus === 'all' ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50' }}">
        Toutes
    </a>
    @foreach ($statuses as $status)
        <a href="{{ route('admin.orders', ['statut' => $status]) }}"
           class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $selectedStatus === $status ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50' }}">
            {{ ucfirst($status) }}
        </a>
    @endforeach
</div>

<div class="rounded-2xl border-slate-200 bg-white shadow-xs overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Commande</th>
                <th class="px-4 py-3">Client</th>
                <th class="px-4 py-3">Articles</th>
                <th class="px-4 py-3">Montant</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($orders as $order)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-semibold text-slate-900">#{{ $order->id }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $order->client->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $order->items->count() }} article(s)</td>
                    <td class="px-4 py-3 font-semibold text-slate-900">{{ number_format((float) $order->total_amount, 0, ',', ' ') }} FCFA</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                            {{ in_array($order->status, ['delivered', 'completed'], true) ? 'bg-emerald-100 text-emerald-700' : (in_array($order->status, ['cancelled', 'disputed'], true) ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucune commande.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection
