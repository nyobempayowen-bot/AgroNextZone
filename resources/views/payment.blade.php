<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paiement — AgroNextZone</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">
@section('content')
<main class="max-w-2xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-emerald-950 mb-2">Paiement de la commande {{ $order->reference }}</h1>

    <div class="rounded-2xl border-slate-200 bg-white p-6 shadow-sm mb-6">
        <p class="text-slate-600">Montant à régler :</p>
        <p class="text-3xl font-extrabold text-emerald-800">{{ number_format((float) $order->total, 0, ',', ' ') }} FCFA</p>
        <p class="text-sm text-slate-500 mt-2">Méthode : {{ $order->payment_method }}</p>
        @if ($payment)
            <p class="text-sm text-slate-500">Référence paiement : <span class="font-mono">{{ $payment->provider_reference }}</span></p>
            <p class="text-sm mt-1">
                Statut :
                <span class="font-semibold {{ $payment->status === 'paid' ? 'text-emerald-700' : ($payment->status === 'pending' ? 'text-amber-600' : 'text-red-600') }}">
                    {{ match($payment->status) { 'paid' => 'Réussi', 'pending' => 'En attente', 'failed' => 'Échoué', 'cancelled' => 'Annulé', default => $payment->status } }}
                </span>
            </p>
        @endif
        @if ($simulation ?? false)
            <p class="mt-3 text-xs text-slate-500 italic">Mode simulation : aucun argent réel n'est transféré, aucun fournisseur de paiement externe n'est appelé.</p>
        @endif
    </div>

    @if ($payment && $payment->status === 'pending')
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('payment.decide', ['reference' => $order->reference, 'outcome' => 'successful']) }}"
               class="px-5 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700">Simuler un paiement réussi</a>
            <a href="{{ route('payment.decide', ['reference' => $order->reference, 'outcome' => 'failed']) }}"
               class="px-5 py-3 rounded-xl bg-red-100 text-red-700 font-semibold hover:bg-red-200">Simuler un échec</a>
            <a href="{{ route('payment.decide', ['reference' => $order->reference, 'outcome' => 'cancelled']) }}"
               class="px-5 py-3 rounded-xl bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200">Annuler</a>
        </div>
    @else
        <a href="{{ route('client.dashboard', ['tab' => 'orders']) }}" class="text-emerald-700 font-semibold underline">Retour à mes commandes</a>
    @endif
</main>
</body>
</html>
