@extends('layouts.app-2')
@section('page-title', 'Halaman Dashboard')

@section('extra-css')
    @include('pages.dashboard.css')
@endsection

@section('body')
    @include('pages.dashboard.html')
@endsection

@section('extra-javascript')
    @include('pages.dashboard.javascript')
@endsection
