@extends('layouts.app')

@section('title', 'Modifier un créneau')
@section('page-title', 'Modifier un créneau')
@section('page-subtitle', 'Ajustement manuel de l’emploi du temps')

@section('content')
@include('partials.rh-admin-nav')

<form method="POST" action="{{ route('emploi-du-temps.update', $emploi) }}" class="space-y-6">
    @csrf
    @method('PUT')

    @include('emploi-du-temps._form', ['emploi' => $emploi])

    <div class="flex justify-between">
        <a href="{{ route('emploi-du-temps.index') }}" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700">
            Annuler
        </a>

        <button class="px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white rounded-xl text-sm font-bold shadow-brand-glow">
            Mettre à jour
        </button>
    </div>
</form>
@endsection