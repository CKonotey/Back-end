<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Slide;
use App\Models\UserQuery;

class SlideController extends Controller
{

    public function show($slide_id)
    {
        // Find the slide by ID
        $slide = Slide::find($slide_id);

        // If slide is not found, return a 404 response
        if (!$slide) {
            return response()->json(['message' => 'Slide not found'], 404);
        }

        // Return the slide data as JSON
        return response()->json($slide);
    }
    public function store(Request $request){
        $fields = $request->validate([
            'slide_id' => 'required',
            'content' => 'required',
            'metadata' => 'required'
        ]);
        // $slide = Slide::create($fields);
        return [ 'slides' => $fields ];
    }

    public function index()
    {
        // 'user_id' => auth()->id(),
        // Retrieve all slides and return as JSON
        $slides = Slide::all();

        return response()->json($slides);
    }
    public function getSlideData($slide_id)
    {
        // Fetch slide by ID
        $slide = Slide::find($slide_id);

        // Return slide content
        return response()->json($slide);
    }

    public function searchSlideContent(Request $request)
    {
        $query = $request->input('query');

        // Implement search logic (can use simple matching or NLP tools)
        $result = Slide::where('content', 'LIKE', "%$query%")->first();

        // Log the query and result
        UserQuery::create([
            // 'user_id' => auth()->id(),
            'query' => $query,
            'response' => $result ? $result->content : 'No matching content found',
        ]);

        return response()->json(['response' => $result ? $result->content : 'No matching content found']);
    }
}
