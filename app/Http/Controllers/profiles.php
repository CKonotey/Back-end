<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class profiles extends Controller
{
    /**
     * Add or create a profile for the authenticated user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone' => 'nullable|string|max:15',
            'bio' => 'nullable|string|max:500',
            'social_links' => 'nullable|array',
        ]);

        $user = Auth::user();

        // Save the profile picture if uploaded
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $path = $file->store('profile_pictures', 'public');
        }

        $user->profile()->updateOrCreate([], [
            'profile_picture' => $path ?? $user->profile->profile_picture ?? null,
            'phone' => $request->phone ?? $user->profile->phone ?? null,
            'bio' => $request->bio ?? $user->profile->bio ?? null,
            'social_links' => $request->social_links ?? $user->profile->social_links ?? null,
        ]);

        return response()->json([
            'message' => 'Profile created or updated successfully.',
            'profile' => $user->profile,
        ], 200);
    }

    /**
     * Update specific fields in the user's profile.
     */
    // public function updateField(Request $request)
    // {
    //     $request->validate([
    //         'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'phone' => 'nullable|string|max:15',
    //         'bio' => 'nullable|string|max:500',
    //         'social_links' => 'nullable|array',
    //     ]);

    //     $user = Auth::user();

    //     // Update the profile picture if provided
    //     if ($request->hasFile('profile_picture')) {
    //         $file = $request->file('profile_picture');
    //         $path = $file->store('profile_pictures', 'public');
    //         $user->profile->update(['profile_picture' => $path]);
    //     }

    //     // Update other fields if provided
    //     $fields = ['phone', 'bio', 'social_links'];
    //     foreach ($fields as $field) {
    //         if ($request->has($field)) {
    //             $user->profile->update([$field => $request->$field]);
    //         }
    //     }

    //     // Reload the updated profile
    //     $user->profile->refresh();

    //     return response()->json([
    //         'message' => 'Profile field(s) updated successfully.',
    //         'profile' => $user->profile,  // Return the updated profile
    //     ], 200);
    // }

    public function updateField(Request $request)
{
    $request->validate([
        'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'phone' => 'nullable|string|max:15',
        'bio' => 'nullable|string|max:500',
        'social_links' => 'nullable|array',
    ]);

    $user = Auth::user();

    // Handle profile picture update
    if ($request->hasFile('profile_picture') && $request->file('profile_picture')->isValid()) {
        $file = $request->file('profile_picture');
        $path = $file->store('profile_pictures', 'public');
        $user->profile->update(['profile_picture' => $path]);
    }

    // Update other fields
    $fields = ['phone', 'bio', 'social_links'];
    foreach ($fields as $field) {
        if ($request->has($field)) {
            $user->profile->update([$field => $request->$field]);
        }
    }

    // Reload updated profile
    $user->profile->refresh();

    return response()->json([
        'message' => 'Profile field(s) updated successfully.',
        'profile' => $user->profile,  // Return the updated profile data
    ], 200);
}


    /**
     * Delete specific fields in the user's profile.
     */
    public function deleteField(Request $request)
    {
        $request->validate([
            'fields' => 'required|array',
            'fields.*' => 'string|in:profile_picture,phone,bio,social_links',
        ]);

        $user = Auth::user();
        $fieldsToDelete = $request->fields;

        foreach ($fieldsToDelete as $field) {
            $user->profile->update([$field => null]);
        }

        return response()->json([
            'message' => 'Profile field(s) deleted successfully.',
            'profile' => $user->profile,
        ], 200);
    }

}
