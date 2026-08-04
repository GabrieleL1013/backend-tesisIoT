<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NodeAlert extends Model
{
    protected $table = 'node_alerts';

    protected $fillable = [
        'node_id',
        'type',
        'title',
        'title_en',
        'message',
        'message_en',
        'severity',
        'is_read',
        'resolved_at',
        'metadata'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'metadata' => 'array',
        'resolved_at' => 'datetime'
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id');
    }
}
