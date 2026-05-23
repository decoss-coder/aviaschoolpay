<div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Année scolaire</label>
            <select name="annee_scolaire_id" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                <option value="">Sélectionner...</option>
                @foreach($annees as $annee)
                    <option value="{{ $annee->id }}" @selected(old('annee_scolaire_id', $emploi->annee_scolaire_id ?? null) == $annee->id)>
                        {{ $annee->libelle }}
                    </option>
                @endforeach
            </select>
            @error('annee_scolaire_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Jour</label>
            <select name="jour" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                <option value="">Sélectionner...</option>
                @foreach($jours as $jour)
                    <option value="{{ $jour }}" @selected(old('jour', $emploi->jour ?? null) === $jour)>
                        {{ ucfirst($jour) }}
                    </option>
                @endforeach
            </select>
            @error('jour') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Classe</label>
            <select name="classe_id" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                <option value="">Sélectionner...</option>
                @foreach($classes as $classe)
                    <option value="{{ $classe->id }}" @selected(old('classe_id', $emploi->classe_id ?? null) == $classe->id)>
                        {{ $classe->nom }}
                    </option>
                @endforeach
            </select>
            @error('classe_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Matière</label>
            <select name="matiere_id" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                <option value="">Sélectionner...</option>
                @foreach($matieres as $matiere)
                    <option value="{{ $matiere->id }}" @selected(old('matiere_id', $emploi->matiere_id ?? null) == $matiere->id)>
                        {{ $matiere->nom }}
                    </option>
                @endforeach
            </select>
            @error('matiere_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">
                Enseignant <span class="text-gray-400 normal-case">(optionnel)</span>
            </label>
            <select name="enseignant_id" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                <option value="">Non affecté pour l’instant</option>
                @foreach($enseignants as $enseignant)
                    <option value="{{ $enseignant->id }}"
                        @selected(old('enseignant_id', $emploi->enseignant_id ?? null) == $enseignant->id)>
                        {{ $enseignant->nom_complet }}
                    </option>
                @endforeach
            </select>
            @error('enseignant_id')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Salle</label>
            <select name="salle_id" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                <option value="">Sélectionner...</option>
                @foreach($salles as $salle)
                    <option value="{{ $salle->id }}" @selected(old('salle_id', $emploi->salle_id ?? null) == $salle->id)>
                        {{ $salle->nom }}
                    </option>
                @endforeach
            </select>
            @error('salle_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Créneau</label>
            <select name="creneau_id" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                <option value="">Sélectionner...</option>
                @foreach($creneaux as $creneau)
                    <option value="{{ $creneau->id }}" @selected(old('creneau_id', $emploi->creneau_id ?? null) == $creneau->id)>
                        {{ $creneau->libelle ?? (($creneau->heure_debut ?? '') . ' - ' . ($creneau->heure_fin ?? '')) }}
                    </option>
                @endforeach
            </select>
            @error('creneau_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Valide du</label>
            <input type="date" name="valide_du" value="{{ old('valide_du', optional($emploi->valide_du ?? null)->format('Y-m-d')) }}"
                   class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
            @error('valide_du') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Valide au</label>
            <input type="date" name="valide_au" value="{{ old('valide_au', optional($emploi->valide_au ?? null)->format('Y-m-d')) }}"
                   class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
            @error('valide_au') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-2 pt-7">
            <input type="checkbox" name="actif" value="1" @checked(old('actif', $emploi->actif ?? true))>
            <span class="text-sm text-gray-700">Actif</span>
        </div>
    </div>
</div>