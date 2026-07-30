<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InterfaceText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InterfaceTextController extends Controller
{
    /**
     * Display a listing of interface texts as key-value pairs.
     */
    public function index()
    {
        $texts = Cache::remember('interface_texts_cache', 3600, function () {
            return InterfaceText::pluck('text', 'key')->all();
        });

        return response()->json($texts, 200, [], JSON_FORCE_OBJECT);
    }

    /**
     * Store or update an interface text.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'text' => 'required|string',
        ]);

        $interfaceText = InterfaceText::updateOrCreate(
            ['key' => $validated['key']],
            ['text' => $validated['text']]
        );

        Cache::forget('interface_texts_cache');

        return response()->json($interfaceText, 200);
    }
}

