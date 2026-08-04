<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppInterface extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_es',
        'name_en',
        'path',
        'path_es',
        'path_en',
        'description',
        'allowed_roles',
        'min_level',
    ];

    protected $casts = [
        'allowed_roles' => 'array',
    ];

    public function texts()
    {
        return $this->hasMany(InterfaceText::class, 'interface_id');
    }
}
