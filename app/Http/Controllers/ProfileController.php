<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */

    public function test()
    {
        $filePaths = [
            "Saved Pictures/Captu.PNG",
            "Saved Pictures/Capture.PNG",
            "Saved Pictures/Capture1.PNG",
            "Saved Pictures/Login.PNG",
            "Saved Pictures/tranglayteamplateweb.PNG",
            "Saved Pictures/Logo/logo.PNG",
            "Saved Pictures/khung/1-01.png",
            "Saved Pictures/khung/2-01.png",
            "Saved Pictures/khung/3-01.png",
            "Saved Pictures/khung/4-01.png",
        ];

        $files = [
            "Captu.PNG",
            "Capture.PNG",
            "Capture1.PNG",
            "Login.PNG",
            "tranglayteamplateweb.PNG",
            "logo.PNG",
            "1-01.png",
            "2-01.png",
            "3-01.png",
            "4-01.png",
        ];
        $tree = [];

        foreach ($filePaths as $key => $value) {
            $parts = explode('/', $value);
            $currentNode = &$tree;
            foreach ($parts as $i => $part) {
                if(!isset($currentNode[$part])){
                    $currentNode[$part] = [];
                }

                if($i === count($parts) - 1){
                    $currentNode[$part] = $files[$key];
                }else{
                    $currentNode = &$currentNode[$part];
                }
            }
        }
        dump($tree);
    }

    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
