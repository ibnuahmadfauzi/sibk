<?php

use Illuminate\Support\Facades\Route;


/*
Note for Muslich:
routing sementara untuk preview front-end,
jika sudah mulai membuat back-end silahkan dihapus
*/

Route::get('/', function () {
    return redirect('/login');
});
Route::get('/login', function () {
    return view('pages.login.index');
});
Route::get('/dashboard', function () {
    return view('pages.dashboard.index');
});
