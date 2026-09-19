@extends('layouts.app-2')

@section('page-title', 'Detail Konsultasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @include('pages.consultations._detail-modal')
    </div>
@endsection
