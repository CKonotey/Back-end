<?php

namespace App\Helpers;

use GuzzleHttp\Client;

class GeminiHelper
{
    /**
     * Get the AI response from the Gemini API
     *
     * @param string $query
     * @return string
     */
    public static function getNlpResponse($query)
    {
        $client = new Client();

        try {
            $geminiApiKey = config('services.gemini.api_key');  // Retrieve the API key
            $geminiApiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent';

            // Prepare the request payload
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $query],
                        ],
                    ],
                ],
            ];

            // Send the request to the Gemini API
            $response = $client->post($geminiApiUrl . '?key=' . $geminiApiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($payload),
            ]);

            // Parse the response body
            $body = json_decode($response->getBody(), true);

            // Extract the generated content
            return $body['contents'][0]['parts'][0]['text'] ?? 'No response received from the API.';
        } catch (\Exception $e) {
            return 'Error fetching response: ' . $e->getMessage();
        }
    }
}
