@extends('layouts.app-2')

@section('page-title', 'Periksa Sinkronisasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-501">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy m-0">
                <a href="{{ route('data-master.index', ['tab' => 'sinkronisasi']) }}">← Kembali ke Sinkronisasi</a>
                <h1 class="mb-1 mt-2">Periksa data sinkronisasi</h1>
                <p class="mb-0">{{ $issue->summary }}</p>
            </div>
        </div>
        @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
        <section class="sibk-panel"><div class="sibk-panel__body p-4">
            @include('pages.data-master._sync-issue-detail')
        </div></section>
    </div>
@endsection
