<?php

namespace App\Http\Controllers;

use App\Models\PegawaiMitra;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PegawaiMitraController extends Controller
{
    private const PERAN_PEJABAT = ['kepala_satker', 'ppk', 'bendahara'];
    private const KOLOM_IMPORT = ['nip', 'nama', 'jabatan', 'pangkat', 'golongan', 'peran_pejabat'];

    public function pegawai()
    {
        return view('pegawai-mitra.index', [
            'daftar' => PegawaiMitra::where('status_kepegawaian', 'pegawai')->orderBy('nama')->get(),
            'statusKepegawaian' => 'pegawai',
            'judul' => 'Kelola Pegawai',
        ]);
    }

    public function mitra()
    {
        return view('pegawai-mitra.index', [
            'daftar' => PegawaiMitra::where('status_kepegawaian', 'mitra')->orderBy('nama')->get(),
            'statusKepegawaian' => 'mitra',
            'judul' => 'Kelola Mitra',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        PegawaiMitra::create($validated);

        return back()->with('success', 'Data berhasil ditambahkan.');
    }

    public function update(Request $request, PegawaiMitra $pegawaiMitra)
    {
        $validated = $this->validated($request);

        $pegawaiMitra->update($validated);

        return back()->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy(PegawaiMitra $pegawaiMitra)
    {
        try {
            $pegawaiMitra->delete();
        } catch (QueryException) {
            return back()->with('error', 'Data tidak bisa dihapus karena masih dipakai di data perjalanan dinas.');
        }

        return back()->with('success', 'Data berhasil dihapus.');
    }

    /**
     * Unduh template Excel kosong (dengan contoh baris & dropdown Peran Pejabat)
     * untuk diisi admin sebelum diimpor.
     */
    public function template(string $status)
    {
        $label = $status === 'pegawai' ? 'Pegawai' : 'Mitra';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template ' . $label);

        $headers = ['NIP', 'Nama', 'Jabatan', 'Pangkat', 'Golongan', 'Peran Pejabat'];
        foreach ($headers as $i => $header) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setWidth(24);
        }
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E7E7E9');

        // Baris contoh, dihapus atau ditimpa oleh admin sebelum import.
        $contoh = $status === 'pegawai'
            ? ['19990307 202104 1 001', 'Contoh Nama Pegawai', 'Pelaksana', 'Penata Muda', 'III/a', '']
            : ['', 'Contoh Nama Mitra', 'Mitra Statistik', '', '', ''];
        $sheet->fromArray($contoh, null, 'A2');
        $sheet->getStyle('A2:F2')->getFont()->setItalic(true)->getColor()->setRGB('999999');

        // Dropdown validasi kolom Peran Pejabat, hanya relevan untuk pegawai (kepala satker/PPK/bendahara).
        for ($row = 2; $row <= 200; $row++) {
            $validation = $sheet->getCell('F' . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setPromptTitle('Peran Pejabat');
            $validation->setPrompt('Kosongkan jika tidak relevan.');
            $validation->setFormula1('"' . implode(',', self::PERAN_PEJABAT) . '"');
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'template_' . $status . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import data dari file Excel yang sudah diisi berdasarkan template.
     * Baris dengan NIP yang sudah ada (untuk status yang sama) akan diperbarui,
     * sisanya jadi data baru. Baris tanpa nama dilewati.
     */
    public function import(Request $request, string $status)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $imported = 0;
        $dilewati = 0;

        DB::transaction(function () use ($rows, $status, &$imported, &$dilewati) {
            foreach ($rows as $i => $row) {
                if ($i === 0) {
                    continue; // baris header
                }

                $data = array_combine(self::KOLOM_IMPORT, array_pad(array_slice($row, 0, 6), 6, null));
                $data = array_map(fn ($v) => $v !== null ? trim((string) $v) : null, $data);

                if (($data['nama'] ?? '') === '') {
                    $dilewati++;
                    continue;
                }

                if (!in_array($data['peran_pejabat'], self::PERAN_PEJABAT, true)) {
                    $data['peran_pejabat'] = null;
                }
                foreach (['nip', 'jabatan', 'pangkat', 'golongan'] as $field) {
                    if ($data[$field] === '') {
                        $data[$field] = null;
                    }
                }
                $data['status_kepegawaian'] = $status;

                if (!empty($data['nip'])) {
                    PegawaiMitra::updateOrCreate(
                        ['nip' => $data['nip'], 'status_kepegawaian' => $status],
                        $data
                    );
                } else {
                    PegawaiMitra::create($data);
                }

                $imported++;
            }
        });

        $pesan = "Import selesai: {$imported} data berhasil diproses.";
        if ($dilewati > 0) {
            $pesan .= " {$dilewati} baris dilewati karena kolom Nama kosong.";
        }

        return back()->with('success', $pesan);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nip' => 'nullable|string|max:255',
            'nama' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'pangkat' => 'nullable|string|max:255',
            'golongan' => 'nullable|string|max:255',
            'status_kepegawaian' => ['required', Rule::in(['pegawai', 'mitra'])],
            'peran_pejabat' => ['nullable', Rule::in(self::PERAN_PEJABAT)],
        ]);
    }
}
