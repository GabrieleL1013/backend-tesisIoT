<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppInterface extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'path',
        'description',
        'allowed_roles',
        'min_level',
    ];

    protected $casts = [
        'allowed_roles' => 'array',
    ];
}
