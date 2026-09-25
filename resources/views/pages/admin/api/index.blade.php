@extends('layouts.app-2')

@section('page-title', 'Kelola API - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="ADMIN-API">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Kelola API</h1>
                <p>Atur koneksi Dapodik dan e-Tatib dari satu tempat.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        @include('pages.data-master._integration-setting')
    </div>
@endsection
