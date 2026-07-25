<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $staff = $request->user()->business->users()
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return Inertia::render('Staff/Index', [
            'staff' => $staff,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_KASIR])],
        ]);

        $request->user()->business->users()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'Akun karyawan berhasil dibuat.');
    }

    public function destroy(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->business_id === $request->user()->business_id, 403);

        if ($staff->id === $request->user()->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        if ($staff->isOwner()) {
            return back()->with('error', 'Akun pemilik usaha tidak bisa dihapus.');
        }

        $staff->delete();

        return back()->with('success', 'Akun karyawan berhasil dihapus.');
    }
}
