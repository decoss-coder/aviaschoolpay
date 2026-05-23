@if(($lectureSeule ?? false) && ($anneeLectureSeule ?? null))
<div class="bg-gradient-to-r {{ ($anneeLectureSeule->estArchiveConsultation() ?? false) ? 'from-violet-600 via-indigo-600 to-blue-600' : 'from-amber-500 via-orange-500 to-amber-600' }} text-white relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute -top-4 left-1/4 w-32 h-32 bg-white rounded-full blur-3xl"></div>
        <div class="absolute -bottom-4 right-1/4 w-40 h-40 bg-white rounded-full blur-3xl"></div>
    </div>
    <div class="relative px-4 lg:px-6 py-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
        <div class="flex items-center gap-3">
            <span class="text-2xl">{{ ($anneeLectureSeule->estArchiveConsultation() ?? false) ? '📂' : '🔒' }}</span>
            <div>
                <p class="font-extrabold text-sm uppercase tracking-wide flex items-center gap-2">
                    {{ ($anneeLectureSeule->estArchiveConsultation() ?? false) ? 'Consultation archive restaurée' : 'Mode LECTURE SEULE' }}
                    <span class="px-2 py-0.5 bg-white/20 backdrop-blur rounded-full text-[10px] font-extrabold">
                        {{ $anneeLectureSeule->libelle }}
                    </span>
                </p>
                <p class="text-xs text-white/90 mt-0.5">
                    @if($anneeLectureSeule->estArchiveConsultation() ?? false)
                        Toutes les données de cette année sont visibles. <b>Aucun ajout, modification ni suppression</b> n'est autorisé.
                        Créez ou activez une <b>nouvelle année</b> pour la saisie courante.
                    @else
                        Cette année est <b>{{ $anneeLectureSeule->archivee ? 'archivée' : 'clôturée' }}</b> — aucune modification possible.
                    @endif
                </p>
            </div>
        </div>
        @auth
            @if(in_array(auth()->user()->role, ['super_admin', 'directeur', 'directeur_adjoint', 'gestionnaire'], true))
                <a href="{{ route('admin.annees.index') }}"
                   class="px-4 py-2 bg-white text-gray-800 rounded-xl text-xs font-bold hover:bg-gray-50 transition flex items-center gap-1.5 shadow-md flex-shrink-0">
                    📅 Gérer les années
                </a>
            @endif
        @endauth
    </div>
</div>
@endif
