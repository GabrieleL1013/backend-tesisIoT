<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterfaceText extends Model
{
    protected $fillable = ['interface_id', 'key', 'text', 'text_en'];

    public function interface()
    {
        return $this->belongsTo(AppInterface::class, 'interface_id');
    }
}
