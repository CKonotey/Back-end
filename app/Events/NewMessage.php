<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;

class NewMessage implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $conversation;

    public function __construct($conversation)
    {
        $this->conversation = $conversation;

        // Log the event trigger
        Log::info('NewMessage event triggered', [
            'group_id' => $conversation['group_id'] ?? null,
            'message' => $conversation['message'] ?? null,
        ]);
    }

    public function broadcastOn()
    {
        return new Channel('group-chat-' . $this->conversation['group_id']);
    }
}
