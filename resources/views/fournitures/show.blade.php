@extends('layouts.app')
@section('title', 'Fournitures — ' . $liste->classe?->nom)
@section('page-title', $liste->titre)
@section('page-subtitle', $liste->classe?->nom . ' · ' . $liste->items->count() . ' fourniture(s)')

@section('content')
<div class="space-y-6">
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif

    <div class="flex items-center justify-between">
        <a href="{{ route('fournitures.index') }}" class="text-sm font-semibold text-gray-500 hover:text-teal-600">← Toutes les listes</a>
        <div class="flex gap-2">
            <a href="{{ route('fournitures.pdf', $liste->id) }}" target="_blank" class="px-4 py-2 bg-blue-600 text-white text-sm font-bold rounded-xl">📄 Télécharger PDF</a>
            <form method="POST" action="{{ route('fournitures.publier', $liste->id) }}" class="inline">@csrf
                <button class="px-4 py-2 bg-{{ $liste->publie ? 'gray' : 'emerald' }}-600 text-white text-sm font-bold rounded-xl">
                    {{ $liste->publie ? 'Masquer' : '✓ Publier' }}
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-6">
        <h3 class="font-extrabold text-gray-900 mb-3">+ Ajouter une fourniture</h3>
        <form method="POST" action="{{ route('fournitures.items.store', $liste->id) }}" class="grid grid-cols-1 lg:grid-cols-12 gap-2 items-end">
            @csrf
            <div class="lg:col-span-4">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Libellé *</label>
                <input name="libelle" required maxlength="200" placeholder="Ex: Cahier 100 pages grand format" class="w-full rounded-xl border-gray-200 text-sm" />
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Catégorie</label>
                <input name="categorie" list="cats" placeholder="Cahiers, Stylos..." class="w-full rounded-xl border-gray-200 text-sm" />
                <datalist id="cats">
                    <option>Cahiers</option><option>Stylos</option><option>Livres</option>
                    <option>Trousse</option><option>Géométrie</option><option>Vêtements/Sport</option>
                </datalist>
            </div>
            <div class="lg:col-span-1">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Quantité *</label>
                <input type="number" name="quantite" min="1" max="1000" value="1" required class="w-full rounded-xl border-gray-200 text-sm" />
            </div>
            <div class="lg:col-span-1">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Unité</label>
                <input name="unite" placeholder="pièce" class="w-full rounded-xl border-gray-200 text-sm" />
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Marque suggérée</label>
                <input name="marque_suggeree" maxlength="100" class="w-full rounded-xl border-gray-200 text-sm" />
            </div>
            <div class="lg:col-span-1 flex items-center pb-2">
                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="obligatoire" value="1" checked class="rounded" /> Oblig.</label>
            </div>
            <div class="lg:col-span-1">
                <button type="submit" class="w-full px-3 py-2 bg-teal-600 text-white text-sm font-bold rounded-xl">+ Ajouter</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">Fournitures de la liste</h3>
            <span class="text-xs font-semibold text-gray-500">{{ $liste->items->count() }} item(s)</span>
        </div>
        @if($liste->items->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-gray-500">Aucune fourniture pour le moment.</div>
        @else
        @php $parCat = $liste->items->groupBy(fn($i) => $i->categorie ?: 'Autres'); @endphp
        @foreach($parCat as $cat => $items)
            <div class="px-5 py-2 bg-gray-50 text-xs font-bold uppercase text-gray-600 border-b border-gray-100">{{ $cat }} ({{ $items->count() }})</div>
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    @foreach($items as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="font-semibold text-gray-900">{{ $item->libelle }}</div>
                                @if($item->marque_suggeree)<div class="text-xs text-gray-500">Marque suggérée : {{ $item->marque_suggeree }}</div>@endif
                                @if($item->observations)<div class="text-xs text-gray-500 italic">{{ $item->observations }}</div>@endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="font-bold text-teal-700">{{ $item->quantite }}</span>
                                <span class="text-xs text-gray-500">{{ $item->unite ?: 'pièce(s)' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($item->obligatoire)
                                    <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-700">Obligatoire</span>
                                @else
                                    <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-600">Facultatif</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('fournitures.items.destroy', [$liste->id, $item->id]) }}" class="inline" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                                    <button class="text-xs text-red-600 font-bold">🗑</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
        @endif
    </div>
</div>
@endsection
