<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PegawaiMitraController;
use App\Http\Controllers\PerjalananDinasController;
use App\Http\Controllers\RincianBiayaController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\BantuanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\DokumenExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/pegawai', [ProfileController::class, 'updatePegawai'])->name('profile.pegawai.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/perjalanan-dinas', [PerjalananDinasController::class, 'index'])->name('perjalanan-dinas.index');
    Route::get('/perjalanan-dinas/saya', [PerjalananDinasController::class, 'mine'])->name('perjalanan-dinas.saya');
    Route::get('/perjalanan-dinas/create', [PerjalananDinasController::class, 'create'])->name('perjalanan-dinas.create');
    Route::get('/perjalanan-dinas/nomor-spd-berikutnya', [PerjalananDinasController::class, 'nomorSpdBerikutnya'])->name('perjalanan-dinas.nomor-spd-berikutnya');
    Route::post('/perjalanan-dinas/jenis-kegiatan', [PerjalananDinasController::class, 'tambahJenisKegiatan'])->name('perjalanan-dinas.jenis-kegiatan.store');
    Route::get('/perjalanan-dinas/import/template', [PerjalananDinasController::class, 'importTemplate'])->name('perjalanan-dinas.import-template');
    Route::post('/perjalanan-dinas/import', [PerjalananDinasController::class, 'import'])->name('perjalanan-dinas.import');
    Route::post('/perjalanan-dinas', [PerjalananDinasController::class, 'store'])->name('perjalanan-dinas.store');
    Route::get('/perjalanan-dinas/{perjalananDinas}/edit', [PerjalananDinasController::class, 'edit'])->name('perjalanan-dinas.edit');
    Route::put('/perjalanan-dinas/{perjalananDinas}', [PerjalananDinasController::class, 'update'])->name('perjalanan-dinas.update');
    Route::patch('/perjalanan-dinas/{perjalananDinas}/buka-kembali', [PerjalananDinasController::class, 'bukaKembali'])->name('perjalanan-dinas.buka-kembali');
    Route::delete('/perjalanan-dinas/{perjalananDinas}', [PerjalananDinasController::class, 'destroy'])->name('perjalanan-dinas.destroy');
    Route::delete('/perjalanan-dinas/{perjalananDinas}/dokumentasi/{dokumentasi}', [PerjalananDinasController::class, 'hapusDokumentasi'])->name('perjalanan-dinas.dokumentasi.destroy');
    Route::patch('/perjalanan-dinas/{perjalananDinas}/dokumentasi/{dokumentasi}/caption', [PerjalananDinasController::class, 'updateCaptionDokumentasi'])->name('perjalanan-dinas.dokumentasi.caption');
    Route::get('/perjalanan-dinas/{perjalananDinas}', [PerjalananDinasController::class, 'show'])->name('perjalanan-dinas.show');
    Route::get('/rincian-biaya', [RincianBiayaController::class, 'index'])->name('rincian-biaya.index');
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/bantuan', [BantuanController::class, 'index'])->name('bantuan.index');
    Route::get('/rate', [RateController::class, 'index'])->name('rate.index');
    Route::get('/dokumen-pendukung/{key}/download', [RateController::class, 'downloadDokumenPendukung'])->name('dokumen-pendukung.download');
    Route::post('/dokumen/export-pdf', [DokumenExportController::class, 'pdf'])->name('dokumen.export-pdf');
});

Route::middleware(['auth', 'role:administrator'])->group(function () {
    Route::get('/kelola-pegawai', [PegawaiMitraController::class, 'pegawai'])->name('pegawai-mitra.pegawai');
    Route::get('/kelola-mitra', [PegawaiMitraController::class, 'mitra'])->name('pegawai-mitra.mitra');
    Route::post('/pegawai-mitra', [PegawaiMitraController::class, 'store'])->name('pegawai-mitra.store');
    Route::put('/pegawai-mitra/{pegawaiMitra}', [PegawaiMitraController::class, 'update'])->name('pegawai-mitra.update');
    Route::delete('/pegawai-mitra/{pegawaiMitra}', [PegawaiMitraController::class, 'destroy'])->name('pegawai-mitra.destroy');
    Route::get('/pegawai-mitra/template/{status}', [PegawaiMitraController::class, 'template'])
        ->whereIn('status', ['pegawai', 'mitra'])
        ->name('pegawai-mitra.template');
    Route::post('/pegawai-mitra/import/{status}', [PegawaiMitraController::class, 'import'])
        ->whereIn('status', ['pegawai', 'mitra'])
        ->name('pegawai-mitra.import');

    Route::get('/kelola-akun', [UserController::class, 'index'])->name('user.index');
    Route::post('/kelola-akun', [UserController::class, 'store'])->name('user.store');
    Route::put('/kelola-akun/{user}', [UserController::class, 'update'])->name('user.update');
    Route::patch('/kelola-akun/{user}/reset-password', [UserController::class, 'resetPassword'])->name('user.reset-password');
    Route::patch('/kelola-akun/{user}/toggle-aktif', [UserController::class, 'toggleAktif'])->name('user.toggle-aktif');
    Route::delete('/kelola-akun/{user}', [UserController::class, 'destroy'])->name('user.destroy');

    Route::get('/kelola-wilayah', [WilayahController::class, 'index'])->name('wilayah.index');
    Route::post('/wilayah/kabupaten-kota', [WilayahController::class, 'storeKabupatenKota'])->name('wilayah.kabupaten-kota.store');
    Route::put('/wilayah/kabupaten-kota/{kabupatenKota}', [WilayahController::class, 'updateKabupatenKota'])->name('wilayah.kabupaten-kota.update');
    Route::delete('/wilayah/kabupaten-kota/{kabupatenKota}', [WilayahController::class, 'destroyKabupatenKota'])->name('wilayah.kabupaten-kota.destroy');
    Route::post('/wilayah/desa', [WilayahController::class, 'storeDesa'])->name('wilayah.desa.store');
    Route::put('/wilayah/desa/{desa}', [WilayahController::class, 'updateDesa'])->name('wilayah.desa.update');
    Route::delete('/wilayah/desa/{desa}', [WilayahController::class, 'destroyDesa'])->name('wilayah.desa.destroy');

    Route::post('/rate/akomodasi', [RateController::class, 'storeAkomodasi'])->name('rate.akomodasi.store');
    Route::put('/rate/akomodasi/{akomodasi}', [RateController::class, 'updateAkomodasi'])->name('rate.akomodasi.update');
    Route::delete('/rate/akomodasi/{akomodasi}', [RateController::class, 'destroyAkomodasi'])->name('rate.akomodasi.destroy');
    Route::post('/rate/transport', [RateController::class, 'storeTransport'])->name('rate.transport.store');
    Route::put('/rate/transport/{transport}', [RateController::class, 'updateTransport'])->name('rate.transport.update');
    Route::delete('/rate/transport/{transport}', [RateController::class, 'destroyTransport'])->name('rate.transport.destroy');
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
