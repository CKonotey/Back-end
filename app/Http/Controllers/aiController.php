<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpPresentation\IOFactory as PptParser;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;

class aiController extends Controller
{
    // Cohere API URL
    protected $cohereApiUrl = 'https://api.cohere.ai/v1/generate';

    // Cohere API token (you can set it in the .env file)
    protected $cohereApiKey;

    public function __construct()
    {
        $this->cohereApiKey = env('COHERE_API_KEY');
    }

    // Method to handle querying of slides without uploading, just reading from storage
    public function querySlide(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
        ]);

        $query = $request->input('query');

        // Path to slides in the app/slides directory
        $slidePath = storage_path('app/slides');

        // Extract text from all slide files in the directory
        $allText = $this->extractSlidesText($slidePath);

        // Get the most relevant response using Cohere AI
        $response = $this->getNlpResponse($query, $allText);

        return response()->json([
            'query' => $query,
            'response' => $response
        ]);
    }

    // Method to extract text from all slide files in the app/slides folder
    private function extractSlidesText($folderPath)
    {
        $allText = '';

        // Get all PDF, PPT, PPTX files from the folder
        $pdfFiles = File::glob($folderPath . '/*.pdf');
        $pptFiles = File::glob($folderPath . '/*.ppt');
        $pptxFiles = File::glob($folderPath . '/*.pptx');

        // Extract text from PDF files
        foreach ($pdfFiles as $file) {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($file);
            $allText .= $pdf->getText() . "\n";
        }

        // Extract text from PPT and PPTX files
        foreach (array_merge($pptFiles, $pptxFiles) as $file) {
            $pptReader = PptParser::createReader('PowerPoint2007');
            $presentation = $pptReader->load($file);

            foreach ($presentation->getAllSlides() as $slide) {
                foreach ($slide->getShapeCollection() as $shape) {
                    if ($shape instanceof \PhpOffice\PhpPresentation\Shape\RichText) {
                        $allText .= $shape->getPlainText() . "\n";
                    }
                }
            }
        }

        return $allText;
    }

    // Method to get the NLP response from Cohere AI
    private function getNlpResponse($query, $context)
    {
        $client = new Client();

        try {
            $response = $client->post($this->cohereApiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->cohereApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'command-xlarge-nightly', // Or another Cohere model based on your use case
                    'prompt' => "Context: $context\n\nQuestion: $query\n\nAnswer:",
                    'max_tokens' => 150, // Limit response length
                    'temperature' => 0.7, // Control randomness
                    'k' => 1,             // Number of completions
                    'stop_sequences' => ['\n'], // Stop generating after the answer
                ],
            ]);

            $body = json_decode($response->getBody(), true);

            // Extract the answer from the response
            return $body['generations'][0]['text'] ?? 'No relevant information found in the slides.';
        } catch (\Exception $e) {
            return 'Error fetching response: ' . $e->getMessage();
        }
    }
}
