<?php

namespace App\Helpers;

use Pusher\Pusher;

class BroadcastHelper
{
    protected $pusher;

    public function __construct()
    {
        $this->pusher = new Pusher(
            config('services.connections.pusher.key'),
            config('services.connections.pusher.secret'),
            config('services.connections.pusher.app_id'),
            [
                'cluster' => config('services.connections.pusher.options.cluster'),
                'useTLS' => true,
            ]
        );
    }

    public function sendMessageToGroupChat($groupId, $conversation)
    {
        $channel = 'group-chat-' . $groupId;
        $event = 'new-message';

        $this->pusher->trigger($channel, $event, [
            'conversation' => $conversation,
        ]);
    }
}
