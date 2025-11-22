<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('Profile/Show', [
            'user' => $request->user(),
        ]);
    }

    public function edit(Request $request)
    {
        return Inertia::render('Profile/Edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        // Handle profile update logic
        $request->user()->update($request->validated());

        return back()->with('success', 'Profile updated successfully.');
    }
}
