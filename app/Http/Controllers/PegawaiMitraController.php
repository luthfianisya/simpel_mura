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
    private const PERAN_PEJABAT = ['kepala_satker', 'ppk', 'ppspm', 'bendahara'];

    // Kolom template/import Excel per jenis data: [kolom database => judul kolom Excel].
    private const KOLOM_IMPORT = [
        'pegawai' => [
            'nip' => 'NIP',
            'nama' => 'Nama',
            'jabatan' => 'Jabatan',
            'pangkat' => 'Pangkat',
            'golongan' => 'Golongan',
            'peran_pejabat' => 'Peran Pejabat',
        ],
        'mitra' => [
            'sobat_id' => 'Sobat ID',
            'nama' => 'Nama',
            'alamat' => 'Alamat',
            'jenis_mitra' => 'Sebagai',
        ],
    ];

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
        $validated = $this->validated($request, $pegawaiMitra);

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
     * Ubah banyak pegawai/mitra sekaligus dengan nilai yang sama. Field kosong = tidak
     * diubah. Pegawai: jabatan, pangkat, golongan, peran pejabat ("-" = kosongkan).
     * Mitra: peran mitra (Sebagai) dan alamat.
     */
    public function bulkUpdate(Request $request)
    {
        $status = $request->input('status_kepegawaian');
        $aturan = [
            'status_kepegawaian' => ['required', Rule::in(['pegawai', 'mitra'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:pegawai_mitra,id_pegawai_mitra'],
        ];
        $aturan += $status === 'mitra'
            ? [
                'jenis_mitra' => ['nullable', Rule::in(array_keys(PegawaiMitra::JENIS_MITRA))],
                'alamat' => 'nullable|string|max:1000',
            ]
            : [
                'jabatan' => 'nullable|string|max:255',
                'pangkat' => 'nullable|string|max:255',
                'golongan' => 'nullable|string|max:255',
                'peran_pejabat' => ['nullable', Rule::in([...self::PERAN_PEJABAT, '-'])],
            ];
        $validated = $request->validate($aturan, ['ids.required' => 'Pilih minimal satu data terlebih dahulu.']);

        $perubahan = collect($validated)->except(['ids', 'status_kepegawaian'])->filter(fn ($v) => $v !== null && $v !== '')->all();
        if (empty($perubahan)) {
            return back()->with('error', 'Belum ada field yang diisi untuk diubah.');
        }
        if (($perubahan['peran_pejabat'] ?? null) === '-') {
            $perubahan['peran_pejabat'] = null;
        }
        if (isset($perubahan['jenis_mitra'])) {
            $perubahan['jabatan'] = PegawaiMitra::JENIS_MITRA[$perubahan['jenis_mitra']];
        }

        $diubah = PegawaiMitra::whereIn('id_pegawai_mitra', $validated['ids'])
            ->where('status_kepegawaian', $status)
            ->update($perubahan);

        return back()->with('success', "{$diubah} data berhasil diperbarui.");
    }

    /**
     * Hapus banyak pegawai/mitra sekaligus. Yang masih dipakai di perjalanan dinas tidak
     * bisa dihapus dan dilaporkan namanya, sisanya tetap terhapus.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'status_kepegawaian' => ['required', Rule::in(['pegawai', 'mitra'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:pegawai_mitra,id_pegawai_mitra'],
        ], ['ids.required' => 'Pilih minimal satu data terlebih dahulu.']);

        $dihapus = 0;
        $gagal = [];
        $daftar = PegawaiMitra::whereIn('id_pegawai_mitra', $validated['ids'])
            ->where('status_kepegawaian', $validated['status_kepegawaian'])
            ->get();
        foreach ($daftar as $pegawaiMitra) {
            try {
                $pegawaiMitra->delete();
                $dihapus++;
            } catch (QueryException) {
                $gagal[] = $pegawaiMitra->nama;
            }
        }

        $redirect = back()->with('success', "{$dihapus} data berhasil dihapus.");
        if ($gagal) {
            $redirect->with('error', count($gagal) . ' data tidak bisa dihapus karena masih dipakai di perjalanan dinas: ' . implode(', ', $gagal) . '.');
        }

        return $redirect;
    }

    /**
     * Unduh template Excel kosong (dengan contoh baris & dropdown) untuk diisi admin
     * sebelum diimpor. Kolomnya beda untuk pegawai dan mitra, lihat KOLOM_IMPORT.
     */
    public function template(string $status)
    {
        $label = $status === 'pegawai' ? 'Pegawai' : 'Mitra';
        $kolom = self::KOLOM_IMPORT[$status];
        $kolomTerakhir = chr(64 + count($kolom));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template ' . $label);

        foreach (array_values($kolom) as $i => $header) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setWidth($status === 'mitra' && $header === 'Alamat' ? 40 : 24);
        }
        $sheet->getStyle("A1:{$kolomTerakhir}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$kolomTerakhir}1")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E7E7E9');

        // Baris contoh, dihapus atau ditimpa oleh admin sebelum import.
        $contoh = $status === 'pegawai'
            ? ['19990307 202104 1 001', 'Contoh Nama Pegawai', 'Pelaksana', 'Penata Muda', 'III/a', '']
            : ['621300000001', 'Contoh Nama Mitra', 'Jl. Contoh No. 1, Puruk Cahu', 'Mitra Pendataan'];
        $sheet->fromArray($contoh, null, 'A2');
        $sheet->getStyle("A2:{$kolomTerakhir}2")->getFont()->setItalic(true)->getColor()->setRGB('999999');
        // Sobat ID/NIP disimpan sebagai teks supaya angka panjangnya tidak jadi notasi ilmiah.
        $sheet->getStyle('A2:A200')->getNumberFormat()->setFormatCode('@');

        // Dropdown: Peran Pejabat (kolom F) untuk pegawai, Sebagai (kolom D) untuk mitra.
        [$kolomDropdown, $judulDropdown, $pilihan, $bolehKosong] = $status === 'pegawai'
            ? ['F', 'Peran Pejabat', self::PERAN_PEJABAT, true]
            : ['D', 'Sebagai', array_values(PegawaiMitra::JENIS_MITRA), false];
        for ($row = 2; $row <= 200; $row++) {
            $validation = $sheet->getCell($kolomDropdown . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank($bolehKosong);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setPromptTitle($judulDropdown);
            $validation->setPrompt($bolehKosong ? 'Kosongkan jika tidak relevan.' : 'Pilih salah satu.');
            $validation->setFormula1('"' . implode(',', $pilihan) . '"');
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
     * Baris dengan NIP (pegawai) / Sobat ID (mitra) yang sudah ada akan diperbarui,
     * sisanya jadi data baru. Baris tanpa nama dilewati.
     */
    public function import(Request $request, string $status)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $kolom = array_keys(self::KOLOM_IMPORT[$status]);
        $kunci = $status === 'pegawai' ? 'nip' : 'sobat_id';
        $imported = 0;
        $dilewati = 0;

        DB::transaction(function () use ($rows, $status, $kolom, $kunci, &$imported, &$dilewati) {
            foreach ($rows as $i => $row) {
                if ($i === 0) {
                    continue; // baris header
                }

                $data = array_combine($kolom, array_pad(array_slice($row, 0, count($kolom)), count($kolom), null));
                $data = array_map(fn ($v) => $v !== null && trim((string) $v) !== '' ? trim((string) $v) : null, $data);

                if (($data['nama'] ?? null) === null) {
                    $dilewati++;
                    continue;
                }

                if ($status === 'pegawai') {
                    if (!in_array($data['peran_pejabat'], self::PERAN_PEJABAT, true)) {
                        $data['peran_pejabat'] = null;
                    }
                } else {
                    // Kolom "Sebagai" boleh diisi label ("Mitra Pendataan") maupun kodenya ("pendataan").
                    $data['jenis_mitra'] = array_search($data['jenis_mitra'], PegawaiMitra::JENIS_MITRA, true)
                        ?: (array_key_exists((string) $data['jenis_mitra'], PegawaiMitra::JENIS_MITRA) ? $data['jenis_mitra'] : null);
                    $data['jabatan'] = PegawaiMitra::JENIS_MITRA[$data['jenis_mitra']] ?? null;
                }
                $data['status_kepegawaian'] = $status;

                if (!empty($data[$kunci])) {
                    PegawaiMitra::updateOrCreate(
                        [$kunci => $data[$kunci], 'status_kepegawaian' => $status],
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

    private function validated(Request $request, ?PegawaiMitra $pegawaiMitra = null): array
    {
        $request->validate([
            'status_kepegawaian' => ['required', Rule::in(['pegawai', 'mitra'])],
        ]);

        if ($request->input('status_kepegawaian') === 'mitra') {
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'sobat_id' => [
                    'required', 'string', 'max:255',
                    Rule::unique('pegawai_mitra', 'sobat_id')->ignore($pegawaiMitra?->id_pegawai_mitra, 'id_pegawai_mitra'),
                ],
                'alamat' => 'required|string|max:1000',
                'jenis_mitra' => ['required', Rule::in(array_keys(PegawaiMitra::JENIS_MITRA))],
                'status_kepegawaian' => 'required',
            ], [
                'sobat_id.unique' => 'Sobat ID ini sudah terdaftar untuk mitra lain.',
                'jenis_mitra.required' => 'Pilih mitra sebagai apa.',
            ]);

            // Mitra tidak punya NIP/pangkat/golongan/peran pejabat; jabatannya mengikuti
            // peran mitra supaya tetap tampil di dokumen perjalanan dinas.
            return array_merge($validated, [
                'jabatan' => PegawaiMitra::JENIS_MITRA[$validated['jenis_mitra']],
                'nip' => null,
                'pangkat' => null,
                'golongan' => null,
                'peran_pejabat' => null,
            ]);
        }

        return $request->validate([
            'nip' => 'nullable|string|max:255',
            'nama' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'pangkat' => 'nullable|string|max:255',
            'golongan' => 'nullable|string|max:255',
            'status_kepegawaian' => 'required',
            'peran_pejabat' => ['nullable', Rule::in(self::PERAN_PEJABAT)],
        ]);
    }
}
