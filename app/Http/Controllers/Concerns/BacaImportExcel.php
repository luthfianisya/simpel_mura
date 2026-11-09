<?php

namespace App\Http\Controllers\Concerns;

use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Pembacaan nilai sel untuk import Excel (perjalanan dinas & rincian non-perjadin), supaya
 * aturan tanggal, angka, dan baris contoh sama di semua template.
 */
trait BacaImportExcel
{
    // Awalan teks penanda baris contoh di template import — baris bertanda ini tidak ikut
    // diimport, jadi boleh dibiarkan di file.
    protected const PENANDA_CONTOH = '[CONTOH]';

    protected function diawaliPenandaContoh(string $nilai): bool
    {
        return stripos(trim($nilai), self::PENANDA_CONTOH) === 0;
    }

    /**
     * Tanggal dari sel import → "YYYY-MM-DD". Menerima teks YYYY-MM-DD (format template),
     * tanggal Excel (angka serial, kalau sel terlanjur diformat tanggal), serta DD/MM/YYYY
     * atau DD-MM-YYYY yang lazim diketik. Selain itu dikembalikan apa adanya supaya
     * validasi yang melaporkan barisnya.
     */
    protected function normalisasiTanggalImport(string $nilai): string
    {
        if ($nilai === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return $nilai;
        }
        if (is_numeric($nilai) && (float) $nilai > 20000 && (float) $nilai < 80000) {
            return Date::excelToDateTimeObject((float) $nilai)->format('Y-m-d');
        }
        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $nilai, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return $nilai;
    }

    /**
     * Angka dari sel import. Menerima 500000, "500.000", "Rp 500.000", "500.000,50".
     * Kosong → null. Teks yang bukan angka dikembalikan apa adanya supaya validasi
     * numeric/integer melaporkannya (bukan diam-diam jadi 0).
     */
    protected function angkaImport(string $nilai, bool $bulat = false): int|float|string|null
    {
        $bersih = preg_replace('/^rp\.?\s*/i', '', str_replace(' ', '', $nilai));
        if ($bersih === '') {
            return null;
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $bersih)) {
            $bersih = str_replace(['.', ','], ['', '.'], $bersih);
        }
        if (!is_numeric($bersih)) {
            return $nilai;
        }

        return $bulat && floor((float) $bersih) == (float) $bersih ? (int) $bersih : (float) $bersih;
    }
}
