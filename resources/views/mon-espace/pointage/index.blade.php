@extends('layouts.app')
@section('title', 'Mon pointage')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 space-y-5" x-data="scanner()">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.dashboard') }}" class="hover:text-brand-600 font-medium">Mon espace</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Pointage</span>
    </div>

    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Pointage par QR Code</h1>
            <p class="text-sm text-gray-500 mt-1">Scannez le QR code de la salle pour enregistrer votre arrivée ou votre départ.</p>
        </div>
        <div class="text-right">
            <p class="text-xs font-bold text-gray-400 uppercase">{{ now()->locale('fr')->isoFormat('dddd D MMM YYYY') }}</p>
            <p class="font-mono text-lg font-extrabold text-brand-700" x-text="currentTime"></p>
        </div>
    </div>

    {{-- Prochaine séance EDT --}}
    @if($prochaineSeance)
    <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 flex items-center gap-3 text-sm">
        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-blue-900">Prochaine séance</p>
            <p class="text-xs text-blue-700">
                {{ $prochaineSeance->creneau?->heure_debut }} – {{ $prochaineSeance->creneau?->heure_fin }}
                · {{ $prochaineSeance->classe?->nom }} · {{ $prochaineSeance->matiere?->nom }}
                @if($prochaineSeance->salle)· <b>Salle {{ $prochaineSeance->salle->nom }}</b>@endif
            </p>
        </div>
    </div>
    @endif

    {{-- Toast résultat --}}
    <template x-if="result">
        <div :class="result.success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
             class="border rounded-xl px-4 py-3 flex items-start gap-3">
            <svg x-show="result.success" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <svg x-show="!result.success" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-1">
                <p class="font-bold" x-text="result.message"></p>
                <p class="text-xs mt-0.5" x-show="result.salle">Salle : <b x-text="result.salle"></b> · Distance école : <b x-text="result.distance + 'm'"></b></p>
                <p class="text-xs mt-0.5" x-show="result.conforme === false" class="text-amber-700">⚠ Cette salle ne correspond pas à votre EDT du jour.</p>
            </div>
            <button @click="result = null" class="text-xs font-bold opacity-60 hover:opacity-100">✕</button>
        </div>
    </template>

    {{-- État du système (caméra + GPS) --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-white rounded-xl border p-3 flex items-center gap-3"
             :class="cameraReady ? 'border-green-200' : 'border-gray-200'">
            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                 :class="cameraReady ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3" stroke-width="2"/></svg>
            </div>
            <div class="text-xs">
                <p class="font-bold" :class="cameraReady ? 'text-green-700' : 'text-gray-500'">Caméra</p>
                <p class="text-gray-500" x-text="cameraReady ? 'Prête' : 'Inactive'"></p>
            </div>
        </div>
        <div class="bg-white rounded-xl border p-3 flex items-center gap-3"
             :class="gps.lat ? 'border-green-200' : 'border-gray-200'">
            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                 :class="gps.lat ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3" stroke-width="2"/></svg>
            </div>
            <div class="text-xs">
                <p class="font-bold" :class="gps.lat ? 'text-green-700' : 'text-gray-500'">GPS</p>
                <p class="text-gray-500" x-text="gps.lat ? 'Précision ' + Math.round(gps.acc) + 'm' : 'En attente...'"></p>
            </div>
        </div>
    </div>

    {{-- Mode arrivée / départ --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-4">
        <p class="text-xs font-bold text-gray-500 uppercase mb-2">Type de pointage</p>
        <div class="grid grid-cols-2 gap-2">
            <button @click="typeScan = 'arrivee'" :disabled="{{ $aDejaArrivee ? 'true' : 'false' }}"
                    :class="typeScan === 'arrivee' ? 'bg-green-600 text-white border-green-600' : 'bg-gray-50 text-gray-700 border-gray-200'"
                    class="px-4 py-3 rounded-xl border-2 font-bold text-sm transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14"/></svg>
                <span>Arrivée @if($aDejaArrivee) ✓ @endif</span>
            </button>
            <button @click="typeScan = 'depart'" :disabled="{{ $aDejaDepart ? 'true' : 'false' }}"
                    :class="typeScan === 'depart' ? 'bg-orange-600 text-white border-orange-600' : 'bg-gray-50 text-gray-700 border-gray-200'"
                    class="px-4 py-3 rounded-xl border-2 font-bold text-sm transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 8l4 4m0 0l-4 4m4-4H3"/></svg>
                <span>Départ @if($aDejaDepart) ✓ @endif</span>
            </button>
        </div>
    </div>

    {{-- Scanner zone --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Scanner le QR</h2>
            <span x-show="scanning" class="text-xs font-bold text-green-700 animate-pulse flex items-center gap-1">
                <span class="w-2 h-2 bg-green-500 rounded-full"></span> Scan en cours
            </span>
        </div>

        <div id="qr-reader" class="w-full aspect-square max-h-[400px] bg-gray-50"></div>

        <div class="px-5 py-3 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100">
            <p class="text-xs text-gray-500 flex-1" x-show="!scanning">Cliquez sur "Démarrer" pour activer la caméra.</p>
            <p class="text-xs text-gray-500 flex-1" x-show="scanning">Pointez votre caméra vers le QR de la salle.</p>
            <div class="flex gap-2">
                <button @click="startScanner()" x-show="!scanning"
                        :disabled="!typeScan || sending"
                        class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-4 py-2 rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed">
                    📷 Démarrer le scanner
                </button>
                <button @click="stopScanner()" x-show="scanning"
                        class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-4 py-2 rounded-lg transition">
                    ✕ Arrêter
                </button>
            </div>
        </div>
    </div>

    {{-- Historique aujourd'hui --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Aujourd'hui ({{ $pointagesAujourdHui->count() }})</h2>
        </div>
        @if($pointagesAujourdHui->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-gray-400">Aucun pointage aujourd'hui.</div>
        @else
            <div class="divide-y divide-gray-50">
                @foreach($pointagesAujourdHui as $p)
                @php
                    $statutColors = [
                        'present' => 'bg-green-100 text-green-700',
                        'retard'  => 'bg-amber-100 text-amber-700',
                        'absent'  => 'bg-gray-100 text-gray-500',
                        'hors_zone' => 'bg-red-100 text-red-700',
                        'fraude_detectee' => 'bg-red-100 text-red-700',
                    ];
                @endphp
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center
                        {{ $p->type_scan === 'arrivee' ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-orange-600' }}">
                        @if($p->type_scan === 'arrivee')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm text-gray-800">
                            {{ ucfirst($p->type_scan) }} · {{ \Carbon\Carbon::parse($p->heure_scan)->format('H:i') }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Salle {{ $p->salle?->nom ?? '—' }}
                            @if($p->distance_ecole_metres !== null)· {{ round($p->distance_ecole_metres) }}m de l'école @endif
                        </p>
                    </div>
                    <span class="text-xs font-bold px-2 py-1 rounded-full {{ $statutColors[$p->statut] ?? 'bg-gray-100 text-gray-500' }}">
                        {{ ucfirst(str_replace('_', ' ', $p->statut)) }}
                    </span>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- 7 derniers jours --}}
    @if($derniersJours->isNotEmpty())
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">7 derniers jours</h2>
        </div>
        <div class="divide-y divide-gray-50 text-sm">
            @foreach($derniersJours as $dateKey => $jourPointages)
            @if($dateKey !== now()->toDateString())
            <div class="px-5 py-3">
                <p class="text-xs font-bold text-gray-500 uppercase mb-2">{{ \Carbon\Carbon::parse($dateKey)->locale('fr')->isoFormat('ddd D MMM') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($jourPointages as $p)
                    <span class="text-xs font-semibold px-2 py-1 rounded-lg
                        {{ $p->statut === 'present' ? 'bg-green-50 text-green-700' : ($p->statut === 'retard' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">
                        {{ ucfirst($p->type_scan) }} {{ \Carbon\Carbon::parse($p->heure_scan)->format('H:i') }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
function scanner() {
    return {
        currentTime: '',
        cameraReady: false,
        gps: { lat: null, lng: null, acc: null },
        typeScan: {{ $aDejaArrivee ? "'depart'" : "'arrivee'" }},
        scanning: false,
        sending: false,
        result: null,
        html5QrCode: null,
        timer: null,

        init() {
            this.updateClock();
            this.timer = setInterval(() => this.updateClock(), 1000);
            this.requestGps();
        },

        updateClock() {
            const d = new Date();
            this.currentTime = String(d.getHours()).padStart(2,'0') + ':' +
                              String(d.getMinutes()).padStart(2,'0') + ':' +
                              String(d.getSeconds()).padStart(2,'0');
        },

        requestGps() {
            if (!navigator.geolocation) {
                this.result = { success: false, message: 'GPS non supporté par ce navigateur.' };
                return;
            }
            navigator.geolocation.watchPosition(
                pos => {
                    this.gps = {
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        acc: pos.coords.accuracy,
                    };
                },
                err => {
                    this.result = { success: false, message: 'GPS refusé : ' + err.message + ' (le pointage est obligatoirement géolocalisé)' };
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 }
            );
        },

        async startScanner() {
            if (!this.gps.lat) {
                this.result = { success: false, message: 'Position GPS introuvable. Activez la géolocalisation.' };
                return;
            }
            this.result = null;
            this.scanning = true;
            this.cameraReady = false;

            this.html5QrCode = new Html5Qrcode('qr-reader');
            try {
                await this.html5QrCode.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 260, height: 260 } },
                    (decodedText) => this.onScanSuccess(decodedText),
                    () => {}
                );
                this.cameraReady = true;
            } catch (e) {
                this.scanning = false;
                this.result = { success: false, message: 'Caméra inaccessible : ' + e };
            }
        },

        async stopScanner() {
            if (this.html5QrCode && this.scanning) {
                try { await this.html5QrCode.stop(); } catch (e) {}
                try { await this.html5QrCode.clear(); } catch (e) {}
            }
            this.scanning = false;
            this.cameraReady = false;
        },

        async onScanSuccess(decodedText) {
            if (this.sending) return;
            this.sending = true;
            await this.stopScanner();

            try {
                const res = await fetch('{{ route('mon-espace.pointage.scan') }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        code_qr: decodedText,
                        gps_latitude:  this.gps.lat,
                        gps_longitude: this.gps.lng,
                        gps_precision: this.gps.acc,
                        type_scan: this.typeScan,
                    }),
                });
                const data = await res.json();
                this.result = {
                    success: data.success === true,
                    message: data.message || data.error || 'Réponse inattendue',
                    salle:    data.salle,
                    distance: data.distance,
                    conforme: data.conforme,
                };

                if (data.success) {
                    setTimeout(() => window.location.reload(), 2500);
                }
            } catch (e) {
                this.result = { success: false, message: 'Erreur réseau : ' + e };
            } finally {
                this.sending = false;
            }
        },
    };
}
</script>
@endpush
@endsection
