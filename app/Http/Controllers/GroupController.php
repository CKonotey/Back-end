<?php

namespace App\Http\Controllers;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Conversation;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpPresentation\IOFactory as PptParser;

class GroupController extends Controller
{

    protected $cohereApiUrl = 'https://api.cohere.ai/v1/generate';

    // Cohere API token
    protected $cohereApiKey;

    public function __construct()
    {
        $this->cohereApiKey = env('COHERE_API_KEY');
    }

    // creating a group
    public function createGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group = Group::create($request->only(['name', 'description']));

        return response()->json(['message' => 'Group created successfully', 'group' => $group]);
    }

    // joining a group
    public function joinGroup($groupId)
{
    $user = auth()->user();
    $group = Group::findOrFail($groupId);

    $group->users()->attach($user);

    return response()->json(['message' => 'Joined group successfully']);
}

// upload slides to group
public function uploadSlides(Request $request, $groupId)
{
    $request->validate(['slides' => 'required|file|mimes:pdf,ppt,pptx']);
    $group = Group::findOrFail($groupId);

    $filePath = $request->file('slides')->store("groups/{$groupId}");
    return response()->json(['message' => 'Slides uploaded successfully', 'path' => $filePath]);
}

// chat in group

public function groupChat(Request $request, $groupId)
{
    $request->validate(['query' => 'required|string']);

    $user = auth()->user();
    $group = Group::findOrFail($groupId);
    $query = $request->input('query');

    // Check for AI keyword
    if (str_starts_with($query, '/askai:')) {
        $query = substr($query, 7);

        // Extract text from slides
        $slidePath = storage_path("app/groups/{$groupId}");
        $allText = $this->extractSlidesText($slidePath);

        // Get AI response
        $response = $this->getNlpResponse($query, $allText);

        // Save conversation to group
        // $conversation = $group->conversations()->create([
        //     'user_id' => $user->id,
        //     'message' => $message,
        //     'response' => $response,
        // ]);

        $conversation = Conversation::create([
            'group_id' => $groupId,  // Use the group ID
            'query' => $query,
            'response' => $response,
        ]);

        return response()->json(['conversation' => $conversation]);
    }

    // Save non-AI message to group chat
    $conversation = $group->conversations()->create([
        'user_id' => $user->id,
        'query' => $query,
    ]);

    return response()->json(['conversation' => $conversation]);
}

  /**
     * Extract text from all PDF, PPT, and PPTX files in the 'slides' folder
     */
    private function extractSlidesText($folderPath)
    {
        $allText = '';

        // Get all PDF, PPT, and PPTX files from the folder
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

       /**
     * Get the AI response from Cohere API
     */
    private function getNlpResponse($query, $context)
    {
        $client = new Client();

        try {

            // Modify the prompt to instruct the model to explain bullet points
            $prompt = "Context: $context\n\nSome points are bulleted without much explanation. Expand on the following point: \"$query\" with detailed information.\n\nAnswer:";

            $response = $client->post($this->cohereApiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->cohereApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'command-xlarge-nightly',
                    'prompt' => $prompt,
                    'max_tokens' => 150,
                    'temperature' => 0.7,
                    'k' => 1,
                    'stop_sequences' => ['\n'],
                ],
            ]);

            $body = json_decode($response->getBody(), true);

            return $body['generations'][0]['text'] ?? 'No relevant information found.';
        } catch (\Exception $e) {
            return 'Error fetching response: ' . $e->getMessage();
        }
    }



}
