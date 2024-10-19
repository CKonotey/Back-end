<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Conversation;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpPresentation\IOFactory as PptParser;

class aiController extends Controller
{
    // Cohere API URL
    protected $cohereApiUrl = 'https://api.cohere.ai/v1/generate';

    // Cohere API token
    protected $cohereApiKey;

    public function __construct()
    {
        $this->cohereApiKey = env('COHERE_API_KEY');
    }

    /**
     * Create a new chat
     */
    public function createChat(Request $request)
{
    // Validate the input query
    $request->validate([
        'query' => 'required|string',
    ]);

    $userId = auth()->id();
    $query = $request->input('query');

    // Generate the chat title based on the query
    $chatTitle = $this->generateChatTitle($query);

    // Create a new chat
    $chat = Chat::create([
        'user_id' => $userId,
        'chat_title' => $chatTitle,
        'query' => $query,
        'response' => '',
    ]);

    return response()->json([
        'chat_id' => $chat->id,
        'chat_title'=>$chat->chat_title,
        'message' => 'New chat created successfully.',
    ]);
}
    public function getOrCreateChat(Request $request)
    {
        $userId = auth()->id();

        // Retrieve the latest chat or create a new one if none exists or a new one is requested
        $chat = Chat::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$chat || $request->input('new_chat')) {
            return $this->createChat($request);
        }

        // // If continuing the current chat, append the conversation
        // return $this->updateChat($request, $chat->id);
    }

    /**
     * Get all chats of the logged-in user
     */
    public function getUserChats()
    {
        $userId = auth()->id();
        $chats = Chat::where('user_id', $userId)
            ->with('conversations')
            ->get();

        return response()->json($chats);
    }

    /**
     * Get a specific chat by ID with conversations
     */
    public function showChat($id)
    {
        $userId = auth()->id();
        $chat = Chat::where('id', $id)
            ->where('user_id', $userId)
            ->with('conversations')
            ->firstOrFail();

        return response()->json($chat);
    }

    /**
     * Append a query and response to an existing chat or create a new chat
     */
    public function updateChat(Request $request)
{
    // Validate the input
$request->validate([
    'chat_id' => 'required|integer|exists:chats,id', // Validate chat_id is required, an integer, and exists in the chats table
    'query' => 'required|string',
]);

// Retrieve the chat ID from the request
$chatId = $request->input('chat_id');
$chat = Chat::findOrFail($chatId);

// Ensure the authenticated user owns the chat
if ($chat->user_id != auth()->id()) {
    return response()->json(['error' => 'Unauthorized'], 403);
}

// Generate and save the chat title if it's currently null
if (empty($chat->chat_title)) {
    $chat->chat_title = $this->generateChatTitle($request->input('query'));
    $chat->save(); // Save the updated chat with the new title
}

// Get the user's ID to locate their specific folder
$userId = auth()->id();
$slidePath = storage_path("app/private/slides/user_{$userId}");

// Ensure the directory exists before attempting to extract text
if (!file_exists($slidePath)) {
    return response()->json(['error' => 'No slides found for the user', 'slide_path' => $slidePath ], 404);
}

// Extract text from the user's slides
$allText = $this->extractSlidesText($slidePath);

// Get the AI-generated response
$query = $request->input('query');
$response = $this->getNlpResponse($query, $allText);

// Create a new conversation in the chat
$conversation = Conversation::create([
    'chat_id' => $chat->id,
    'query' => $query,
    'response' => $response,
]);

return response()->json([
    'chat_id' => $chat->id,
    'conversation' => $conversation,
]);

}

/**
 * Generate a more descriptive chat title based on the content of the query.
 *
 * @param string $query
 * @return string
 */
private function generateChatTitle($query)
{
    // Handle simple greeting queries
    $greetings = ['hello', 'hi', 'hey'];
    $lowercaseQuery = strtolower($query);

    if (in_array($lowercaseQuery, $greetings)) {
        return 'Greeting Conversation';
    }

    // List of words to ignore in the chat title
    $ignoreWords = ['a', 'an', 'the', 'and', 'or', 'in', 'on', 'at', 'with', 'to', 'for', 'of', 'make'];

    // Split the query into words and filter out the ignored words
    $words = explode(' ', $lowercaseQuery);
    $filteredWords = array_filter($words, function ($word) use ($ignoreWords) {
        return !in_array($word, $ignoreWords);
    });

    // Capitalize the remaining words to create the title
    $titleWords = array_map('ucfirst', $filteredWords);

    // Join the words to form the chat title
    $chatTitle = implode(' ', $titleWords);

    // Fallback to a default title if the resulting title is too short or empty
    return $chatTitle ?: 'General Conversation';
}

//     public function updateChat(Request $request)
// {
//     // Validate the input
//     $request->validate([
//         'chat_id' => 'required|integer|exists:chats,id', // Validate chat_id is required, an integer, and exists in the chats table
//         'query' => 'required|string',
//     ]);

//     // Retrieve the chat ID from the request
//     $chatId = $request->input('chat_id');
//     $chat = Chat::findOrFail($chatId);

//     // Ensure the authenticated user owns the chat
//     if ($chat->user_id != auth()->id()) {
//         return response()->json(['error' => 'Unauthorized'], 403);
//     }

//     // Extract text from slides
//     $slidePath = storage_path('app/slides');
//     $allText = $this->extractSlidesText($slidePath);

//     // Get the AI-generated response
//     $query = $request->input('query');
//     $response = $this->getNlpResponse($query, $allText);

//     // Create a new conversation in the chat
//     $conversation = Conversation::create([
//         'chat_id' => $chat->id,
//         'query' => $query,
//         'response' => $response,
//     ]);

//     return response()->json([
//         'chat_id' => $chat->id,
//         'conversation' => $conversation
//     ]);
// }


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
            $response = $client->post($this->cohereApiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->cohereApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'command-xlarge-nightly',
                    'prompt' => "Context: $context\n\nQuestion: $query\n\nAnswer:",
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
