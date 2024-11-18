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
use App\Helpers\CohereHelper;
use App\Helpers\BroadcastHelper;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

class GroupController extends Controller
{

    protected $cohereApiUrl = 'https://api.cohere.ai/v1/generate';

    // Cohere API token
    protected $cohereApiKey;

    public function __construct()
    {
        $this->cohereApiKey = env('COHERE_API_KEY');
    }

     /**
     * Get conversations for a specific group.
     *
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupConversations($groupId)
    {
        $group = Group::findOrFail($groupId); // Ensure the group exists

        $conversations = Conversation::where('group_id', $groupId)
            ->with('chat') // Eager load the related chat if needed
            ->get();

        return response()->json($conversations);
    }
    // getting all groups
    public function getAllGroups()
    {
        $groups = Group::all(); // Retrieve all groups from the database

        return response()->json($groups);
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

         // Use the CohereHelper to get the NLP response
         $response = CohereHelper::getNlpResponse($query, $allText);

         $conversation = Conversation::create([
            'group_id' => $groupId,
            'query' => $query,
            'response' => $response,
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);
         // Broadcast the new conversation message to the group channel

         $broadcaster = new BroadcastHelper();
         $broadcaster->sendMessageToGroupChat($groupId, $conversation->toArray());

    // Dispatch the event
        // event(new NewMessage($conversation));
        return response()->json(['conversation' => $conversation]);
    }

    // Save non-AI message to group chat
    $conversation = $group->conversations()->create([
        'user_id' => $user->id,
        'user_name' => $user->name,
        'query' => $query,
    ]);

    $broadcaster = new BroadcastHelper();
    $broadcaster->sendMessageToGroupChat($groupId, $conversation->toArray());

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
}
