<?php

namespace App\Events;

use App\Models\Node;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LecturaRecibida implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $node;
    public $data;

    /**
     * Create a new event instance.
     */
    public function __construct(Node $node, array $data)
    {
        $this->node = $node;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('telemetry.' . $this->node->serial_number),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'LecturaRecibida';
    }
}
