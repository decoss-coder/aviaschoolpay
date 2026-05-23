<x-mail::message>
# {{ $variant === 'confirme' ? 'Paiement confirmé' : 'Paiement en attente' }}

{{ $bodyMessage }}

**Établissement :** {{ $paiement->etablissement?->nom ?? '—' }}  
**Élève :** {{ $paiement->eleve?->prenom }} {{ $paiement->eleve?->nom }}  
**Classe :** {{ $paiement->inscription?->classe?->nom ?? '—' }}  
**Montant :** {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA  
**Mode :** {{ strtoupper($paiement->mode ?? '—') }}  
**Référence :** {{ $paiement->reference }}  
@if($paiement->numero_recu)
**N° reçu :** {{ $paiement->numero_recu }}  
@endif

<x-mail::button :url="$url">
Voir le paiement
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
