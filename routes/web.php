<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PegawaiMitraController;
use App\Http\Controllers\PerjalananDinasController;
use App\Http\Controllers\RincianBiayaController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\BantuanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/pegawai', [ProfileController::class, 'updatePegawai'])->name('profile.pegawai.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/pegawai-mitra', [PegawaiMitraController::class, 'index'])->name('pegawai-mitra.index');
    Route::get('/perjalanan-dinas', [PerjalananDinasController::class, 'index'])->name('perjalanan-dinas.index');
    Route::get('/perjalanan-dinas/saya', [PerjalananDinasController::class, 'mine'])->name('perjalanan-dinas.saya');
    Route::get('/perjalanan-dinas/create', [PerjalananDinasController::class, 'create'])->name('perjalanan-dinas.create');
    Route::get('/perjalanan-dinas/nomor-spd-berikutnya', [PerjalananDinasController::class, 'nomorSpdBerikutnya'])->name('perjalanan-dinas.nomor-spd-berikutnya');
    Route::post('/perjalanan-dinas', [PerjalananDinasController::class, 'store'])->name('perjalanan-dinas.store');
    Route::get('/rincian-biaya', [RincianBiayaController::class, 'index'])->name('rincian-biaya.index');
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/bantuan', [BantuanController::class, 'index'])->name('bantuan.index');
});

Route::get('/dashboard', function () {
    $daftarPerjalananDinas = \App\Models\PerjalananDinas::with([
        'pemohon', 'pelaksana', 'penandatanganSt', 'ppk', 'bendahara', 'jenisKegiatan',
        'rincianBiaya', 'pengeluaranRiil', 'kuitansi', 'suratPernyataan', 'laporan', 'dokumentasi',
    ])
        ->latest()
        ->get();

    $statistikPerjadin = [
        'total' => $daftarPerjalananDinas->count(),
        'biasa' => $daftarPerjalananDinas->where('jenis_perjadin', 'biasa')->count(),
        'dalam_kota_kurang_8_jam' => $daftarPerjalananDinas->where('jenis_perjadin', 'dalam_kota_kurang_8_jam')->count(),
        'dalam_kota_lebih_8_jam' => $daftarPerjalananDinas->where('jenis_perjadin', 'dalam_kota_lebih_8_jam')->count(),
    ];

    $dokumenDataById = $daftarPerjalananDinas->mapWithKeys(
        fn ($pd) => [$pd->id_perjalanan_dinas => $pd->toPreviewData()]
    );

    return view('dashboard', compact('daftarPerjalananDinas', 'statistikPerjadin', 'dokumenDataById'));
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/auth.php';
