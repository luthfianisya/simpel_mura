<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Administrator melihat rekap feedback dari seluruh akun; akun lain hanya melihat
     * form kirim feedback.
     */
    public function index(Request $request)
    {
        $isAdmin = $request->user()->role === 'administrator';

        $daftarFeedback = $isAdmin
            ? Feedback::with(['pengirim', 'penindakLanjut'])->latest()->get()
            : collect();

        return view('feedback.index', [
            'daftarFeedback' => $daftarFeedback,
            'isAdmin' => $isAdmin,
            'ringkasan' => [
                'total' => $daftarFeedback->count(),
                'belum' => $daftarFeedback->where('selesai', false)->count(),
                'selesai' => $daftarFeedback->where('selesai', true)->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'isi' => ['required', 'string', 'max:5000'],
        ]);

        Feedback::create($validated + ['id_user' => $request->user()->id]);

        return redirect()->route('feedback.index')
            ->with('success', 'Terima kasih, feedback Anda sudah terkirim dan akan ditindaklanjuti administrator.');
    }

    /**
     * Administrator mengisi tindak lanjut/solusi dan menandai selesai atau belum.
     */
    public function tindakLanjut(Request $request, Feedback $feedback)
    {
        abort_unless($request->user()->role === 'administrator', 403);

        $validated = $request->validate([
            'tindak_lanjut' => ['nullable', 'string', 'max:5000'],
            'selesai' => ['required', 'boolean'],
        ]);

        $feedback->update([
            'tindak_lanjut' => $validated['tindak_lanjut'] ?? null,
            'selesai' => (bool) $validated['selesai'],
            'ditindaklanjuti_oleh' => $request->user()->id,
            'ditindaklanjuti_pada' => now(),
        ]);

        return redirect()->route('feedback.index')->with('success', 'Tindak lanjut feedback berhasil disimpan.');
    }
}
