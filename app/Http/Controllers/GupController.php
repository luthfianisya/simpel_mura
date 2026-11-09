<?php

namespace App\Http\Controllers;

use App\Models\Gup;
use App\Models\MonitoringSpj;
use App\Models\PerjalananDinas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pengelompokan SPJ ke dalam GUP dari halaman Monitoring SPJ (hanya PPK/PPSPM/Bendahara).
 * SPJ yang masuk GUP otomatis berjenis pembayaran "GUP" dan Tanggal GUP-nya mengikuti GUP.
 * GUP yang tidak lagi berisi SPJ dihapus otomatis.
 */
class GupController extends Controller
{
    /** Buat GUP baru berisi SPJ terpilih. */
    public function store(Request $request)
    {
        $this->cekAkses($request);
        $validated = $request->validate($this->aturanIds() + [
            'tanggal_gup' => ['nullable', 'date'],
        ], $this->pesan());

        $gup = DB::transaction(function () use ($request, $validated) {
            $gup = Gup::create([
                'tanggal_gup' => $validated['tanggal_gup'] ?? null,
                'dibuat_oleh' => $request->user()->id,
            ]);
            $this->masukkan($validated, $gup, $request->user()->id);

            return $gup;
        });

        return $this->kembali($this->jumlah($validated) . ' SPJ berhasil dikelompokkan ke ' . $this->label($gup) . '.');
    }

    /** Masukkan SPJ terpilih ke GUP yang sudah ada (SPJ dari GUP lain ikut dipindahkan). */
    public function tambah(Request $request, Gup $gup)
    {
        $this->cekAkses($request);
        $validated = $request->validate($this->aturanIds(), $this->pesan());

        DB::transaction(fn () => $this->masukkan($validated, $gup, $request->user()->id));

        return $this->kembali($this->jumlah($validated) . ' SPJ berhasil dimasukkan ke ' . $this->label($gup) . '.');
    }

    /** Keluarkan SPJ terpilih dari GUP-nya (jenis pembayaran tetap GUP, tanggal GUP dikosongkan). */
    public function keluarkan(Request $request)
    {
        $this->cekAkses($request);
        $validated = $request->validate($this->aturanIds(), $this->pesan());

        DB::transaction(function () use ($request, $validated) {
            $this->barisTerpilih($validated)->update([
                'id_gup' => null,
                'tanggal_gup' => null,
                'diubah_oleh' => $request->user()->id,
            ]);
            $this->hapusGupKosong();
        });

        return $this->kembali($this->jumlah($validated) . ' SPJ berhasil dikeluarkan dari GUP.');
    }

    /** Ubah tanggal GUP; Tanggal GUP semua SPJ di dalamnya ikut berubah. */
    public function update(Request $request, Gup $gup)
    {
        $this->cekAkses($request);
        $validated = $request->validate(['tanggal_gup' => ['nullable', 'date']]);

        DB::transaction(function () use ($gup, $validated) {
            $gup->update(['tanggal_gup' => $validated['tanggal_gup'] ?? null]);
            $gup->monitoringSpj()->update(['tanggal_gup' => $gup->tanggal_gup]);
        });

        return $this->kembali('Tanggal ' . $this->label($gup) . ' berhasil disimpan.');
    }

    /** Hapus GUP; SPJ di dalamnya kembali ke daftar "Belum masuk GUP". */
    public function destroy(Request $request, Gup $gup)
    {
        $this->cekAkses($request);
        $label = $this->label($gup);

        DB::transaction(function () use ($gup) {
            $gup->monitoringSpj()->update(['id_gup' => null, 'tanggal_gup' => null]);
            $gup->delete();
        });

        return $this->kembali($label . ' berhasil dihapus. SPJ di dalamnya kembali ke daftar Belum Masuk GUP.');
    }

    private function masukkan(array $validated, Gup $gup, int $idUser): void
    {
        $isian = [
            'id_gup' => $gup->id_gup,
            'jenis_pembayaran' => 'GUP',
            'tanggal_gup' => $gup->tanggal_gup,
            'diubah_oleh' => $idUser,
        ];
        // SPJ perjalanan dinas yang belum punya baris monitoring dibuatkan sekalian.
        foreach (PerjalananDinas::whereIn('id_perjalanan_dinas', $validated['ids'] ?? [])->get() as $pd) {
            $pd->monitoringSpj()->updateOrCreate([], $isian);
        }
        MonitoringSpj::rincianNonPerjadin()->whereIn('id_monitoring_spj', $validated['rincian_ids'] ?? [])->update($isian);
        $this->hapusGupKosong();
    }

    /** Baris monitoring_spj terpilih: milik perjalanan dinas (ids) + Rincian Non-Perjadin (rincian_ids). */
    private function barisTerpilih(array $validated)
    {
        return MonitoringSpj::where(fn ($q) => $q
            ->whereIn('id_perjalanan_dinas', $validated['ids'] ?? [])
            ->orWhere(fn ($q) => $q->whereNull('id_perjalanan_dinas')->whereIn('id_monitoring_spj', $validated['rincian_ids'] ?? [])));
    }

    private function jumlah(array $validated): int
    {
        return count($validated['ids'] ?? []) + count($validated['rincian_ids'] ?? []);
    }

    private function hapusGupKosong(): void
    {
        Gup::doesntHave('monitoringSpj')->delete();
    }

    private function label(Gup $gup): string
    {
        return optional(Gup::denganNomor()->firstWhere('id_gup', $gup->id_gup))->label ?? 'GUP';
    }

    private function aturanIds(): array
    {
        return MonitoringSpjController::aturanBaris();
    }

    private function pesan(): array
    {
        return MonitoringSpjController::pesanBaris();
    }

    private function cekAkses(Request $request): void
    {
        abort_unless($request->user()->bisaIsiMonitoringSpj(), 403, 'Pengelompokan GUP hanya bisa dilakukan oleh PPK / PPSPM / Bendahara.');
    }

    private function kembali(string $pesan)
    {
        return redirect()->route('monitoring-spj.index')->with('success', $pesan);
    }
}
