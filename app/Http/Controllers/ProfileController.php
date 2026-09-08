<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;



class ProfileController extends Controller
{
    /**
     * Show My Account page
     */
    public function index()
    {
        return view('account.index');
    }

    /**
     * Update user profile
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * 🔄 Update the user's profile information
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // ✅ Validation
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:10240'], // ✅ Ajouté 'gif'
        ]);

        // ✅ Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($user->profile_photo_path) {
                Storage::delete('public/' . $user->profile_photo_path);
            }

            // Store new photo
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $validated['profile_photo_path'] = $path;
        }

        // ✅ If email changed → unverify it (for email verification flow)
        if ($request->email !== $user->email) {
            $validated['email_verified_at'] = null;
        }

        // ✅ Update user
        $user->update($validated);

        // ✅ Redirect with success message
        return back()->with('status', 'profile-updated');
    }

    /**
     * 🗑️ Delete the user's profile photo
     */
    public function deletePhoto(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->profile_photo_path) {
            // Delete photo from storage
            Storage::delete('public/' . $user->profile_photo_path);
            
            // Update user record
            $user->update(['profile_photo_path' => null]);
        }

        return back()->with('status', 'profile-updated');
    }

    /**
     * 🚪 Logout the user
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * 🗑️ Delete the user's account (optional - for GDPR compliance)
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Delete profile photo if exists
        if ($user->profile_photo_path) {
            Storage::delete('public/' . $user->profile_photo_path);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'account-deleted');
    }

    public function updatePassword(Request $request): RedirectResponse
{
    $request->validate([
        'current_password' => ['required', 'current_password'],
        'password' => ['required', 'confirmed', 'min:8'],
    ]);

    $request->user()->update([
        'password' => Hash::make($request->password),
    ]);

    return back()->with('status', 'password-updated');
}
}
