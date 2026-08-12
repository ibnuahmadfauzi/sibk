@extends('layouts.app-1')
@section('page-title', 'Halaman Login')

@section('body')
    @include('pages.login.html')
@endsection

@section('extra-javascript')
    @include('pages.login.javascript')
@endsection
