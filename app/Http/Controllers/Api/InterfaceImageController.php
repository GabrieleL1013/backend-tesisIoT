<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InterfaceImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InterfaceImageController extends Controller
{
    /**
     * Return all interface images as key => { image_data, mime_type } pairs.
     */
    public function index()
    {
        $result = Cache::remember('interface_images_cache', 3600, function () {
            $images = InterfaceImage::all(['key', 'image_data', 'mime_type']);
            $res = [];
            foreach ($images as $img) {
                $res[$img->key] = [
                    'image_data' => $img->image_data,
                    'mime_type'  => $img->mime_type,
                ];
            }
            return $res;
        });

        return response()->json($result, 200, [], JSON_FORCE_OBJECT);
    }

    /**
     * Store or update an interface image (accepts base64 data).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key'        => 'required|string|max:255',
            'image_data' => 'required|string',
            'mime_type'  => 'nullable|string|max:100',
        ]);

        $img = InterfaceImage::updateOrCreate(
            ['key' => $validated['key']],
            [
                'image_data' => $validated['image_data'],
                'mime_type'  => $validated['mime_type'] ?? 'image/jpeg',
            ]
        );

        Cache::forget('interface_images_cache');

        return response()->json([
            'key'       => $img->key,
            'mime_type' => $img->mime_type,
        ], 200);
    }

    /**
     * Delete an interface image by key.
     */
    public function destroy($key)
    {
        $img = InterfaceImage::where('key', $key)->first();
        if (!$img) {
            return response()->json(['message' => 'Imagen no encontrada'], 404);
        }
        $img->delete();

        Cache::forget('interface_images_cache');

        return response()->json(['message' => 'Imagen eliminada correctamente'], 200);
    }
}

