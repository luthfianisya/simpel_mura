<?php

use App\Models\User;

test('beberapa dokumen digabung jadi satu pdf dengan ukuran kertas masing-masing', function () {
    $html = fn (string $ukuran, string $isi) => '<!DOCTYPE html><html><head><style>@page{size:' . $ukuran . ';margin:0;}</style></head><body><p>' . $isi . '</p></body></html>';

    $respon = $this->actingAs(User::factory()->create())->postJson(route('dokumen.export-pdf-gabung'), [
        'dokumen' => [$html('215mm 330mm', 'SPD'), $html('210mm 297mm', 'Laporan')],
        'filename' => 'Dokumen Lengkap',
    ]);

    $respon->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $pdf = $respon->getContent();
    expect(substr($pdf, 0, 4))->toBe('%PDF');
    expect(preg_match_all('/\/Type\s*\/Page[^s]/', $pdf))->toBe(2);
});
