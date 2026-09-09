<?php

namespace App\Http\Controllers;

use App\Models\PegawaiMitra;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = ['administrator', 'pegawai'];
    private const DEFAULT_PASSWORD = 'password';

    public function index()
    {
        return view('user.index', [
            'daftarUser' => User::with('pegawaiMitra')->orderBy('name')->get(),
            'daftarPegawaiBelumPunyaAkun' => PegawaiMitra::doesntHave('user')->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_pegawai_mitra' => ['required', 'exists:pegawai_mitra,id_pegawai_mitra', Rule::unique('users', 'id_pegawai_mitra')],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $pegawai = PegawaiMitra::findOrFail($validated['id_pegawai_mitra']);

        User::create([
            'name' => $pegawai->nama,
            'username' => $validated['username'],
            'email' => $validated['username'] . '@simpel.local',
            'role' => $validated['role'],
            'id_pegawai_mitra' => $pegawai->id_pegawai_mitra,
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password'] ?: self::DEFAULT_PASSWORD),
        ]);

        return back()->with('success', 'Akun untuk ' . $pegawai->nama . ' berhasil dibuat. Username: ' . $validated['username'] . ', password: ' . ($validated['password'] ?: self::DEFAULT_PASSWORD));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        if ($user->id === $request->user()->id && $validated['role'] !== 'administrator') {
            return back()->with('error', 'Anda tidak bisa mencabut hak administrator dari akun Anda sendiri.');
        }

        $user->update($validated);

        return back()->with('success', 'Akun ' . $user->name . ' berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $password = $validated['password'] ?: self::DEFAULT_PASSWORD;
        $user->update(['password' => Hash::make($password)]);

        return back()->with('success', 'Password akun ' . $user->name . ' berhasil direset menjadi: ' . $password);
    }

    public function toggleAktif(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak bisa menonaktifkan akun Anda sendiri.');
        }

        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', 'Akun ' . $user->name . ' berhasil ' . ($user->is_active ? 'diaktifkan' : 'dinonaktifkan') . '.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
    }
}
