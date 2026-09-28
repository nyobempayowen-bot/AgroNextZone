@extends('admin.layout')

@section('title', 'Évaluations')

@section('content')
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900">Évaluations</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-1">Modération des évaluations laissées par les Clients. Masquer une évaluation recalcule le score de fiabilité du Producteur.</p>
</div>

<div class="mb-4 flex flex-wrap items-center gap-2">
    @foreach (['all' => 'Toutes', 'published' => 'Visibles', 'hidden' => 'Masquées'] as $key => $label)
        <a href="{{ route('admin.reviews', ['filtre' => $key]) }}"
           class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $selectedFilter === $key ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="space-y-3">
    @forelse ($reviews as $review)
        <div class="rounded-2xl border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-amber-500">
                            @for ($i = 0; $i < 5; $i++)
                                {{ $i < $review->rating ? '★' : '☆' }}
                            @endfor
                        </span>
                        <span class="text-xs font-bold text-slate-400">{{ $review->rating }}/5</span>
                        @if ($review->status === 'hidden')
                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">Masquée</span>
                        @endif
                    </div>
                    @if (trim((string) $review->comment) !== '')
                        <p class="mt-1 text-sm text-slate-700">{{ $review->comment }}</p>
                    @endif
                    <p class="mt-1 text-xs text-slate-500">
                        Par {{ $review->client->name ?? '—' }}
                        @if ($review->product?->producer)
                            · Produit « {{ $review->product->name }} » · Producteur {{ $review->product->producer->name }}
                        @endif
                        · {{ $review->created_at?->format('d/m/Y H:i') }}
                    </p>
                </div>
                <form method="POST" action="{{ $review->status === 'hidden' ? route('admin.reviews.restore', $review) : route('admin.reviews.hide', $review) }}">
                    @csrf
                    <button type="submit" class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $review->status === 'hidden'
                        ? 'bg-white border border-emerald-300 text-emerald-700 hover:bg-emerald-50'
                        : 'bg-white border border-rose-300 text-rose-700 hover:bg-rose-50' }}">
                        {{ $review->status === 'hidden' ? 'Restaurer' : 'Masquer' }}
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border-slate-200 bg-white p-8 text-center text-slate-500 shadow-xs">Aucune évaluation.</div>
    @endforelse
</div>

<div class="mt-4">{{ $reviews->links() }}</div>
@endsection
