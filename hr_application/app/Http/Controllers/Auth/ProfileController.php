<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.profile', ['user' => $request->user()]);
    }

    /**
     * Perbarui profil sendiri. Peran tidak dapat diubah di sini — hanya admin
     * yang boleh mengubah peran lewat menu Pengguna.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'string'],
            'password' => ['nullable', 'string', PasswordRule::min(8), 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak cocok.']);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Profil berhasil diperbarui.');
    }
}
