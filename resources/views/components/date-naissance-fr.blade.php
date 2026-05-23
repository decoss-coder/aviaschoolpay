@php
    $name = $name ?? 'date_naissance';
    $required = $required ?? false;
    $value = old('date_naissance', $value ?? '');
    $parts = ['jour' => '', 'mois' => '', 'annee' => ''];
    if ($value && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
        $parts = ['annee' => $m[1], 'mois' => (int) $m[2], 'jour' => (int) $m[3]];
    }
    $moisLabels = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];
    $anneeMin = (int) date('Y') - 25;
    $anneeMax = (int) date('Y') - 5;
@endphp

<div class="date-naissance-fr" data-date-field="{{ $name }}">
    <input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ $value }}">

    <div class="grid grid-cols-3 gap-2">
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Jour</label>
            <select name="_dn_jour" data-dn-part="jour"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-pink-200 focus:border-pink-400 outline-none"
                    {{ $required ? 'data-required=1' : '' }}>
                <option value="">—</option>
                @for($d = 1; $d <= 31; $d++)
                    <option value="{{ $d }}" @selected((int)($parts['jour'] ?? 0) === $d)>{{ $d }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Mois</label>
            <select name="_dn_mois" data-dn-part="mois"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-pink-200 focus:border-pink-400 outline-none"
                    {{ $required ? 'data-required=1' : '' }}>
                <option value="">—</option>
                @foreach($moisLabels as $num => $libelle)
                    <option value="{{ $num }}" @selected((int)($parts['mois'] ?? 0) === $num)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Année</label>
            <select name="_dn_annee" data-dn-part="annee"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-pink-200 focus:border-pink-400 outline-none"
                    {{ $required ? 'data-required=1' : '' }}>
                <option value="">—</option>
                @for($y = $anneeMax; $y >= $anneeMin; $y--)
                    <option value="{{ $y }}" @selected((int)($parts['annee'] ?? 0) === $y)>{{ $y }}</option>
                @endfor
            </select>
        </div>
    </div>

    <div class="mt-2">
        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Ou saisie directe</label>
        <input type="text" data-dn-text inputmode="numeric" placeholder="JJ/MM/AAAA (ex. 15/03/2010)"
               value="{{ $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '' }}"
               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-200 focus:border-pink-400 outline-none">
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('.date-naissance-fr').forEach(function (root) {
        const hidden = root.querySelector('input[type="hidden"]');
        const jour = root.querySelector('[data-dn-part="jour"]');
        const mois = root.querySelector('[data-dn-part="mois"]');
        const annee = root.querySelector('[data-dn-part="annee"]');
        const text = root.querySelector('[data-dn-text]');

        function pad(n) { return String(n).padStart(2, '0'); }

        function syncFromSelects() {
            if (!jour.value || !mois.value || !annee.value) {
                if (!text.value.trim()) hidden.value = '';
                return;
            }
            const iso = annee.value + '-' + pad(mois.value) + '-' + pad(jour.value);
            hidden.value = iso;
            text.value = pad(jour.value) + '/' + pad(mois.value) + '/' + annee.value;
        }

        function syncFromText() {
            const m = text.value.trim().match(/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/);
            if (!m) {
                if (!text.value.trim()) hidden.value = '';
                return;
            }
            const d = parseInt(m[1], 10), mo = parseInt(m[2], 10), y = parseInt(m[3], 10);
            if (d < 1 || d > 31 || mo < 1 || mo > 12) return;
            hidden.value = y + '-' + pad(mo) + '-' + pad(d);
            jour.value = String(d);
            mois.value = String(mo);
            annee.value = String(y);
        }

        jour.addEventListener('change', syncFromSelects);
        mois.addEventListener('change', syncFromSelects);
        annee.addEventListener('change', syncFromSelects);
        text.addEventListener('blur', syncFromText);
        text.addEventListener('change', syncFromText);

        root.closest('form')?.addEventListener('submit', function () {
            syncFromText();
            syncFromSelects();
        });
    });
})();
</script>
