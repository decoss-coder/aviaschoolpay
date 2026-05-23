@extends('layouts.app')
@section('title', 'Accès suspendu')
@section('page-title', 'Accès suspendu')
@section('page-subtitle', 'Contactez Avia Technologie')

@section('content')
<div class="max-w-lg mx-auto mt-12">
    <div class="bg-white rounded-2xl border shadow-card p-8 text-center">
        <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h1 class="font-display text-xl font-extrabold text-gray-900">Accès à votre établissement suspendu</h1>
        <p class="text-sm text-gray-600 mt-3 leading-relaxed">
            L'administration Avia Technologie a temporairement bloqué l'accès à votre école sur AviaSchoolPay.
            Veuillez contacter votre direction ou le support Avia pour plus d'informations.
        </p>
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 text-white font-bold text-sm">Se déconnecter</button>
        </form>
    </div>
</div>
@endsection
