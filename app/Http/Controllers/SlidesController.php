<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpPresentation\IOFactory as PptParser;

class SlidesController extends Controller
{
    public function uploadSlide(Request $request)
{
    $request->validate([
        'slide' => 'required|file|mimes:ppt,pptx|max:10000', // Validate file type and size
    ]);

    // Store the file in the slides directory
    $filePath = $request->file('slide')->store('slides');

    return response()->json(['message' => 'Slide uploaded successfully', 'path' => $filePath]);
}

    public function querySlide(Request $request)
    {
        // Validate query and file type
        $request->validate([
            'query' => 'required|string',
            'file_type' => 'required|string' // Either 'pdf', 'ppt', or 'txt'
        ]);

        $query = $request->input('query');
        $fileType = $request->input('file_type');

        // Path to slide files
        $slidePath = storage_path('app/slides');

        // Search slides based on file type and query
        if ($fileType === 'pdf') {
            $result = $this->searchPdfSlides($slidePath, $query);
        // } elseif ($fileType === 'ppt') {
        //     $result = $this->searchPptSlides($slidePath, $query);
        } else {
            $result = $this->searchTxtSlides($slidePath, $query);
        }

        // Return the most relevant answer or responses
        if (!empty($result)) {
            return response()->json([
                'query' => $query,
                'response' => $this->generateAnswer($result, $query)
            ]);
        } else {
            return response()->json([
                'query' => $query,
                'response' => 'No relevant information found for the given query.'
            ]);
        }
    }

    private function searchPdfSlides($path, $query)
    {
        $parser = new PdfParser();
        $files = glob($path . '/*.pdf');
        $matches = [];

        foreach ($files as $file) {
            $pdf = $parser->parseFile($file);
            $text = $pdf->getText();

            // Search for the query in the PDF text
            if (stripos($text, $query) !== false) {
                $matches[] = [
                    'file' => basename($file),
                    'content' => $text,
                    'excerpt' => $this->getExcerpt($text, $query)
                ];
            }
        }

        return $matches;
    }

    // private function searchPptSlides($path, $query)
    // {
    //     $files = glob($path . '/*.pptx');
    //     $matches = [];

    //     foreach ($files as $file) {
    //         $reader = PptParser::createReader('PowerPoint2007');
    //         $presentation = $reader->load($file);
    //         $text = '';

    //         // Extract text from all slides
    //         foreach ($presentation->getAllSlides() as $slide) {
    //             foreach ($slide->getShapeCollection() as $shape) {
    //                 if (method_exists($shape, 'getText')) {
    //                     $text .= $shape->getText() . ' ';
    //                 }
    //             }
    //         }

    //         // Search for the query in the slide text
    //         if (stripos($text, $query) !== false) {
    //             $matches[] = [
    //                 'file' => basename($file),
    //                 'content' => $text,
    //                 'excerpt' => $this->getExcerpt($text, $query)
    //             ];
    //         }
    //     }

    //     return $matches;
    // }

    private function searchTxtSlides($path, $query)
    {
        $files = glob($path . '/*.txt');
        $matches = [];

        foreach ($files as $file) {
            $text = file_get_contents($file);

            // Search for the query in the text file
            if (stripos($text, $query) !== false) {
                $matches[] = [
                    'file' => basename($file),
                    'content' => $text,
                    'excerpt' => $this->getExcerpt($text, $query)
                ];
            }
        }

        return $matches;
    }

    private function getExcerpt($text, $query)
    {
        // Find the position of the query in the text
        $position = stripos($text, $query);

        // Get a portion of text around the query (200 characters around the query)
        $start = max(0, $position - 100);
        $length = min(strlen($text) - $start, 200);

        return substr($text, $start, $length);
    }

    // Generate an answer based on the slides that matched the query
    private function generateAnswer($matches, $query)
    {
        // We can refine this logic to give a more human-like response
        $response = "Based on the available slides, here's what we found related to '$query':\n\n";

        foreach ($matches as $match) {
            $response .= "In the file: " . $match['file'] . "\n";
            $response .= "Excerpt: " . $match['excerpt'] . "\n\n";
        }

        return $response;
    }
}
