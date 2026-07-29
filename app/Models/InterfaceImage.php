<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterfaceImage extends Model
{
    protected $fillable = ['key', 'image_data', 'mime_type'];
}
