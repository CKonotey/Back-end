<?php

namespace App\Http\Controllers;

use App\Models\Slide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class SlideController extends Controller
{
    public function uploadSlide(Request $request)
    {
        try {
            // Validate the file input
            $request->validate([
                'slide' => 'required|file|mimes:ppt,pptx,pdf|max:20480', // 20 MB max size
            ]);

            $userId = $request->user()->id; // Assuming the user is authenticated
            $userFolder = "slides/user_{$userId}";

            // Create a folder for the user if it doesn't exist in the slides directory
            if (!Storage::disk('local')->exists($userFolder)) {
                Storage::disk('local')->makeDirectory($userFolder);
            }

            // Store the slide file in the user's folder within the slides directory in storage/app/slides
            $filePath = $request->file('slide')->storeAs($userFolder, $request->file('slide')->getClientOriginalName(), 'local');

            // Save the slide information to the database
            Slide::create([
                'user_id' => $userId,
                'file_path' => $filePath,
            ]);

            return response()->json([
                'message' => 'Slide uploaded successfully.',
                'file_path' => $filePath,
            ], 201);

        } catch (Exception $e) {
            // Log the error to the Laravel log file
            Log::error('Slide upload error: ' . $e->getMessage());

            // Return an error response to the client
            return response()->json([
                'message' => 'An error occurred while uploading the slide.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
