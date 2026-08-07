<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
Note for Muslich:
routing sementara untuk preview front-end,
jika sudah mulai membuat back-end silahkan dihapus
*/
Route::get('/login', function () {
    return view('pages.login.index');
});
