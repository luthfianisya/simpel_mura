<?php

use Illuminate\Support\Facades\Route;

/*
 * Portal aplikasi internal BPS Kabupaten Murung Raya di root domain ("/").
 * SIMPEL sendiri ada di bawah awalan "/simpel" (lihat bootstrap/app.php).
 */
Route::view('/', 'portal')->name('portal');
