@extends('layouts.app-2')

@section('page-title', 'Detail Konsultasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        @if(session('success'))
            <x-notification-toast>{{ session('success') }}</x-notification-toast>
        @endif

        @include('pages.consultations._detail-modal')
    </div>
@endsection
