{{--
    Affichage d'une note par étoiles (lecture seule).
    @param float|int|null $note    note sur 5, ou null si aucun avis
    @param string $class           classes adicionais des icônes
--}}
@php
    $note = $note ?? null;
    $filled = $note !== null ? (int) round((float) $note) : 0;
    $size = $size ?? 'h-4 w-4';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 align-middle']) }} role="img"
      aria-label="{{ $note !== null ? 'Note : '.$filled.' sur 5' : 'Aucun avis pour le moment' }}">
    @for ($i = 1; $i <= 5; $i++)
        @if ($note !== null)
            <svg class="{{ $size }} {{ $i <= $filled ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 00.95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.58a1 1 0 00-.36 1.12l1.36 4.18c.3.92-.76 1.69-1.54 1.12l-3.56-2.58a1 1 0 00-1.18 0l-3.56 2.58c-.78.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 00-.36-1.12L1.74 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 00.95-.69L9.05 2.93z"/>
            </svg>
        @else
            <svg class="{{ $size }} text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.5l2.2 4.46 4.92.72c.43.06.61.59.28.9l-3.56 3.46.84 4.9c.07.43-.38.76-.77.56L11 16.06l-4.4 2.31c-.39.2-.84-.13-.77-.56l.84-4.9L3.15 9.58c-.33-.31-.15-.84.28-.9l4.92-.72 2.2-4.46c.19-.59 1.05-.59 1.24 0z"/>
            </svg>
        @endif
    @endfor
</span>
