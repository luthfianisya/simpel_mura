<?php

use App\Models\PegawaiMitra;
use App\Models\PerjalananDinas;
use App\Models\User;

function perjalananDinasUntukWord(string $jenis = 'biasa'): PerjalananDinas
{
    $pelaksana = PegawaiMitra::create(['nama' => 'Pelaksana Uji', 'nip' => '19900101 201001 2 001', 'status_kepegawaian' => 'pegawai']);
    $pd = PerjalananDinas::create([
        'id_pemohon' => $pelaksana->id_pegawai_mitra,
        'id_pegawai_mitra_pelaksana' => $pelaksana->id_pegawai_mitra,
        'jenis_perjadin' => $jenis,
        'status_draft' => 'selesai',
        'perihal' => 'Pendataan _Survei Pertanian_ 2026',
        'tanggal_mulai' => '2026-11-11',
        'tanggal_selesai' => '2026-11-13',
        'no_surat_tugas' => 'B-1/62130/KU.340/2026',
    ]);
    $pd->pengeluaranRiil()->create(['uraian' => 'Transport lokal', 'jenis' => 'transportasi', 'nominal' => 80000]);
    $pd->suratPernyataan()->create(['jenis_kondisi' => 'keterlambatan', 'keterangan' => 'kendala jaringan']);

    return $pd;
}

test('menu dev hanya bisa dibuka administrator', function () {
    $this->actingAs(User::factory()->create(['role' => 'administrator']))
        ->get(route('dev.perjalanan-dinas.index'))
        ->assertOk()
        ->assertSee('(dev) Semua Perjalanan Dinas');

    $this->actingAs(User::factory()->create(['role' => 'pegawai']))
        ->get(route('dev.perjalanan-dinas.index'))
        ->assertForbidden();
});

test('menu dev tidak tampil di sidebar pengguna biasa', function () {
    $this->actingAs(User::factory()->create(['role' => 'pegawai']))
        ->get(route('dashboard'))
        ->assertDontSee('(dev) Semua Perjalanan Dinas');
});

test('administrator bisa mengunduh dokumen word per jenis dan semua sekaligus', function () {
    $admin = User::factory()->create(['role' => 'administrator']);
    $pd = perjalananDinasUntukWord();

    foreach (['semua', 'surat-tugas', 'spd', 'rincian', 'pengeluaran', 'kuitansi', 'pernyataan', 'laporan'] as $jenis) {
        $res = $this->actingAs($admin)->get(route('dev.perjalanan-dinas.word', [$pd, $jenis]));
        $res->assertOk();
        expect($res->headers->get('content-type'))->toContain('wordprocessingml')
            ->and($res->headers->get('content-disposition'))->toContain('.docx');
        // File .docx = arsip ZIP (diawali "PK").
        expect(substr(file_get_contents($res->getFile()->getPathname()), 0, 2))->toBe('PK');
    }
});

test('spd tidak tersedia untuk dalam kota kurang dari 8 jam dan pengguna biasa tidak bisa unduh', function () {
    $pd = perjalananDinasUntukWord('dalam_kota_kurang_8_jam');

    $this->actingAs(User::factory()->create(['role' => 'administrator']))
        ->get(route('dev.perjalanan-dinas.word', [$pd, 'spd']))
        ->assertNotFound();

    $this->actingAs(User::factory()->create(['role' => 'pegawai']))
        ->get(route('dev.perjalanan-dinas.word', [$pd, 'kuitansi']))
        ->assertForbidden();
});

test('pengguna yang login bisa unduh word dari jendela export, satu dokumen atau digabung', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    $pd = perjalananDinasUntukWord();

    foreach (['kuitansi', 'kuitansi,rincian,pengeluaran,pernyataan,laporan'] as $dokumen) {
        $res = $this->actingAs($user)->get(route('perjalanan-dinas.word', $pd) . '?dokumen=' . $dokumen);
        $res->assertOk();
        expect($res->headers->get('content-type'))->toContain('wordprocessingml')
            ->and(substr(file_get_contents($res->getFile()->getPathname()), 0, 2))->toBe('PK');
    }

    // Dokumen tidak dikenal / kosong ditolak; tamu diarahkan ke login.
    $this->actingAs($user)->get(route('perjalanan-dinas.word', $pd) . '?dokumen=kuitansi,ngawur')->assertNotFound();
    $this->actingAs($user)->get(route('perjalanan-dinas.word', $pd))->assertStatus(422);
    auth()->logout();
    $this->get(route('perjalanan-dinas.word', $pd) . '?dokumen=kuitansi')->assertRedirect(route('login'));
});

test('word gabungan tidak memuat surat tugas dan spd', function () {
    $pd = perjalananDinasUntukWord();
    $isi = function (string $path): string {
        $zip = new ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return $xml;
    };
    $layanan = new App\Services\DokumenWord();

    foreach (['semua', ['surat-tugas', 'kuitansi', 'laporan', 'spd']] as $jenis) {
        $xml = $isi($layanan->buat($pd->fresh(), $jenis));
        expect($xml)->toContain('Kuitansi/Bukti Pembayaran')
            ->and($xml)->toContain('LAPORAN HASIL PERJALANAN DINAS')
            ->and($xml)->not->toContain('Memberi Perintah')            // Surat Tugas
            ->and($xml)->not->toContain('SURAT PERJALANAN DINAS');     // SPD
    }

    // Satu dokumen saja tetap bisa diunduh, termasuk Surat Tugas / SPD.
    expect($isi($layanan->buat($pd->fresh(), 'surat-tugas')))->toContain('Memberi Perintah');
});
