@php
    $extrait = $pointage->cahierExtrait();
    $validation = $pointage->cahierValidation();
    $creneau = $extrait['creneau'] ?? [];
    $edtMatch = $validation['edt_match'] ?? null;
    $cahierRoute = $cahierRoute ?? 'pointages.cahier-texte';
    $valide = (bool) ($validation['valide'] ?? $pointage->cahier_texte_validated);
    $score = $validation['score'] ?? null;
@endphp

<section class="bg-white border border-gray-200/80 rounded-2xl shadow-sm overflow-hidden" id="cahier-texte">
    <div class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4
        {{ $valide ? 'bg-gradient-to-r from-emerald-50/80 to-white' : ($pointage->aCahierTexte() ? 'bg-gradient-to-r from-amber-50/60 to-white' : 'bg-gray-50/50') }}">
        <div class="flex items-center gap-3">
            <span class="flex items-center justify-center w-11 h-11 rounded-xl {{ $valide ? 'bg-emerald-100 text-emerald-700' : ($pointage->aCahierTexte() ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-500') }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </span>
            <div>
                <h3 class="font-display text-lg font-extrabold text-gray-900">Cahier de texte · App mobile</h3>
                <p class="text-xs text-gray-500 mt-0.5">Photo + analyse OCR + validation croisée EDT</p>
            </div>
        </div>
        @if($valide)
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-bold shadow-sm">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                Validé par IA
            </span>
        @elseif($pointage->aCahierTexte())
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500 text-white text-sm font-bold">Validation incomplète</span>
        @else
            <span class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-200 text-gray-600 text-sm font-bold">Non transmis</span>
        @endif
    </div>

    @if($pointage->aCahierTexte())
        <div class="p-6">
            @if($score !== null || $pointage->cahier_texte_confidence)
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                    @if($score !== null)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center">
                            <p class="text-[10px] font-bold uppercase text-gray-500">Score EDT</p>
                            <p class="font-display text-2xl font-extrabold text-gray-900 mt-1">{{ $score }}<span class="text-sm text-gray-400">/100</span></p>
                        </div>
                    @endif
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center">
                        <p class="text-[10px] font-bold uppercase text-gray-500">Confiance OCR</p>
                        <p class="font-display text-2xl font-extrabold text-gray-900 mt-1">{{ $pointage->cahier_texte_confidence ?? '—' }}<span class="text-sm text-gray-400">%</span></p>
                    </div>
                    @if($pointage->cahier_texte_validated_at)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center sm:col-span-2">
                            <p class="text-[10px] font-bold uppercase text-gray-500">Validé le</p>
                            <p class="text-sm font-bold text-gray-900 mt-1">{{ $pointage->cahier_texte_validated_at->format('d/m/Y à H:i') }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-3">Preuve photographique</p>
                    <a href="{{ route($cahierRoute, $pointage) }}" target="_blank" rel="noopener"
                       class="group block relative rounded-2xl overflow-hidden border border-gray-200 bg-gray-100 shadow-inner">
                        <img src="{{ route($cahierRoute, $pointage) }}" alt="Cahier de texte"
                             class="w-full max-h-[480px] object-contain group-hover:scale-[1.01] transition-transform duration-300">
                        <span class="absolute bottom-3 right-3 px-3 py-1.5 rounded-lg bg-black/60 text-white text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity">
                            Ouvrir en plein écran
                        </span>
                    </a>
                </div>

                <div class="space-y-5">
                    @if(!empty($validation['raisons']))
                        <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4">
                            <p class="text-xs font-bold uppercase text-amber-800 mb-2">Observations IA</p>
                            <ul class="space-y-1.5 text-sm text-amber-900">
                                @foreach($validation['raisons'] as $raison)
                                    <li class="flex gap-2"><span class="text-amber-500">•</span>{{ $raison }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="rounded-xl border border-brand-100 bg-brand-50/30 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-brand-700 mb-3">Extraction OCR</p>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4 py-2 border-b border-brand-100/80">
                                <dt class="text-gray-500 shrink-0">Date</dt>
                                <dd class="font-bold text-gray-900 text-right">{{ $extrait['date'] ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2 border-b border-brand-100/80">
                                <dt class="text-gray-500 shrink-0">Créneau</dt>
                                <dd class="font-bold text-gray-900 text-right">
                                    @if(!empty($creneau['heure_debut']) || !empty($creneau['heure_fin']))
                                        {{ $creneau['heure_debut'] ?? '?' }} – {{ $creneau['heure_fin'] ?? '?' }}
                                        @if(!empty($creneau['libelle']))
                                            <span class="block text-xs font-normal text-gray-500">{{ $creneau['libelle'] }}</span>
                                        @endif
                                    @else — @endif
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2 border-b border-brand-100/80">
                                <dt class="text-gray-500">Classe</dt>
                                <dd class="font-bold text-gray-900">{{ $extrait['classe'] ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2 border-b border-brand-100/80">
                                <dt class="text-gray-500">Matière</dt>
                                <dd class="font-bold text-gray-900">{{ $extrait['matiere'] ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2">
                                <dt class="text-gray-500">Signature</dt>
                                <dd class="font-bold {{ !empty($extrait['signature_presente']) ? 'text-emerald-700' : 'text-gray-400' }}">
                                    {{ !empty($extrait['signature_presente']) ? 'Présente' : 'Absente' }}
                                </dd>
                            </div>
                        </dl>
                        @if(!empty($extrait['contenu']))
                            <div class="mt-4 pt-4 border-t border-brand-100">
                                <p class="text-[10px] font-bold uppercase text-brand-600 mb-2">Contenu de la leçon</p>
                                <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $extrait['contenu'] }}</p>
                            </div>
                        @endif
                    </div>

                    @if($edtMatch)
                        <div class="rounded-xl border border-indigo-200 bg-indigo-50/50 p-5">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 mb-3">Correspondance emploi du temps</p>
                            @if(!empty($edtMatch['creneau']))
                                <p class="font-bold text-gray-900">{{ $edtMatch['creneau']['libelle'] ?? 'Créneau' }}
                                    <span class="text-indigo-600 font-semibold">({{ $edtMatch['creneau']['heure_debut'] ?? '' }} – {{ $edtMatch['creneau']['heure_fin'] ?? '' }})</span>
                                </p>
                            @endif
                            @if(!empty($edtMatch['matiere']['nom']))
                                <p class="text-sm text-gray-600 mt-2"><span class="font-semibold">Matière :</span> {{ $edtMatch['matiere']['nom'] }}</p>
                            @endif
                            @if(!empty($edtMatch['classe']['nom']))
                                <p class="text-sm text-gray-600"><span class="font-semibold">Classe :</span> {{ $edtMatch['classe']['nom'] }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="p-12 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <p class="font-bold text-gray-700">Aucun cahier de texte reçu</p>
            <p class="text-sm text-gray-500 mt-2 max-w-sm mx-auto">L'enseignant n'a pas encore envoyé la photo du cahier depuis l'application après le scan QR.</p>
        </div>
    @endif
</section>
