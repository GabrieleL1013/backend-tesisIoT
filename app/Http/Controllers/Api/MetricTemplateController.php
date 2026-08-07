<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MetricTemplate;
use App\Services\TranslationService;
use Illuminate\Http\Request;

class MetricTemplateController extends Controller
{
    private function translateString(string $text, string $lang): array
    {
        $isEn = str_starts_with(strtolower($lang), 'en');
        if ($isEn) {
            $trans = TranslationService::translate($text, 'en', 'es');
            return [
                'es' => !empty($trans) ? $trans : $text,
                'en' => $text
            ];
        } else {
            $trans = TranslationService::translate($text, 'es', 'en');
            return [
                'es' => $text,
                'en' => !empty($trans) ? $trans : $text
            ];
        }
    }

    private function handleImageUpload(?string $base64Image): ?string
    {
        if (!$base64Image || !preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            return $base64Image;
        }

        $data = substr($base64Image, strpos($base64Image, ',') + 1);
        $data = base64_decode($data);

        if ($data === false) {
            return null;
        }

        $extension = 'webp';

        $folder = public_path('uploads/metricas');
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        $filename = 'sensor_' . time() . '_' . uniqid() . '.' . $extension;
        $filePath = $folder . '/' . $filename;
        file_put_contents($filePath, $data);

        return '/uploads/metricas/' . $filename;
    }

    private function formatImageUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }
        return url($path);
    }

    public function index(Request $request)
    {
        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');

        $templates = MetricTemplate::with('subvariables')->get();

        $mapped = $templates->map(function ($template) use ($isEn) {
            return [
                'id' => $template->id,
                'nombre' => $isEn ? ($template->nombre_en ?? $template->nombre) : $template->nombre,
                'nombre_es' => $template->nombre,
                'nombre_en' => $template->nombre_en ?? $template->nombre,
                'imagen' => $this->formatImageUrl($template->imagen),
                'subvariables' => $template->subvariables->map(function ($sub) use ($isEn) {
                    return [
                        'id' => $sub->id,
                        'nombre' => $isEn ? ($sub->nombre_en ?? $sub->nombre) : $sub->nombre,
                        'nombre_es' => $sub->nombre,
                        'nombre_en' => $sub->nombre_en ?? $sub->nombre,
                        'unidad' => $sub->unidad,
                        'claveMqtt' => $sub->clave_mqtt,
                        'minExpected' => $sub->min_expected,
                        'maxExpected' => $sub->max_expected,
                        'icono' => $sub->icono
                    ];
                })
            ];
        });

        return response()->json($mapped);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'imagen' => 'nullable|string',
            'subvariables' => 'required|array|min:1',
            'subvariables.*.nombre' => 'required|string',
            'subvariables.*.unidad' => 'required|string',
            'subvariables.*.claveMqtt' => 'required|string',
            'subvariables.*.minExpected' => 'nullable|numeric',
            'subvariables.*.maxExpected' => 'nullable|numeric',
            'subvariables.*.icono' => 'nullable|string'
        ]);

        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $tplTrans = $this->translateString($request->nombre, $lang);

        $imagePath = $this->handleImageUpload($request->imagen);

        $template = MetricTemplate::create([
            'nombre' => $tplTrans['es'],
            'nombre_en' => $tplTrans['en'],
            'imagen' => $imagePath
        ]);

        foreach ($request->subvariables as $sub) {
            $subTrans = $this->translateString($sub['nombre'], $lang);
            $template->subvariables()->create([
                'nombre' => $subTrans['es'],
                'nombre_en' => $subTrans['en'],
                'unidad' => $sub['unidad'],
                'clave_mqtt' => $sub['claveMqtt'],
                'min_expected' => $sub['minExpected'] ?? null,
                'max_expected' => $sub['maxExpected'] ?? null,
                'icono' => $sub['icono'] ?? null
            ]);
        }

        $res = $template->load('subvariables')->toArray();
        $res['imagen'] = $this->formatImageUrl($template->imagen);

        return response()->json($res, 201);
    }

    private function deletePhysicalImage(?string $path): void
    {
        if (!$path) return;

        $parsedPath = parse_url($path, PHP_URL_PATH);
        if ($parsedPath && str_starts_with($parsedPath, '/uploads/metricas/')) {
            $fullPath = public_path(ltrim($parsedPath, '/'));
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string',
            'imagen' => 'nullable|string',
            'subvariables' => 'required|array|min:1',
            'subvariables.*.nombre' => 'required|string',
            'subvariables.*.unidad' => 'required|string',
            'subvariables.*.claveMqtt' => 'required|string',
            'subvariables.*.minExpected' => 'nullable|numeric',
            'subvariables.*.maxExpected' => 'nullable|numeric',
            'subvariables.*.icono' => 'nullable|string'
        ]);

        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $tplTrans = $this->translateString($request->nombre, $lang);

        $template = MetricTemplate::findOrFail($id);
        $oldImage = $template->imagen;
        $imagePath = $this->handleImageUpload($request->imagen);

        if ($oldImage && $oldImage !== $imagePath) {
            $this->deletePhysicalImage($oldImage);
        }

        $template->update([
            'nombre' => $tplTrans['es'],
            'nombre_en' => $tplTrans['en'],
            'imagen' => $imagePath
        ]);

        $incomingIds = [];
        foreach ($request->subvariables as $sub) {
            if (isset($sub['id']) && $sub['id']) {
                $incomingIds[] = $sub['id'];
            }
        }

        $template->subvariables()->whereNotIn('id', $incomingIds)->delete();

        foreach ($request->subvariables as $sub) {
            $subTrans = $this->translateString($sub['nombre'], $lang);
            if (isset($sub['id']) && $sub['id']) {
                $template->subvariables()->where('id', $sub['id'])->update([
                    'nombre' => $subTrans['es'],
                    'nombre_en' => $subTrans['en'],
                    'unidad' => $sub['unidad'],
                    'clave_mqtt' => $sub['claveMqtt'],
                    'min_expected' => $sub['minExpected'] ?? null,
                    'max_expected' => $sub['maxExpected'] ?? null,
                    'icono' => $sub['icono'] ?? null
                ]);
            } else {
                $template->subvariables()->create([
                    'nombre' => $subTrans['es'],
                    'nombre_en' => $subTrans['en'],
                    'unidad' => $sub['unidad'],
                    'clave_mqtt' => $sub['claveMqtt'],
                    'min_expected' => $sub['minExpected'] ?? null,
                    'max_expected' => $sub['maxExpected'] ?? null,
                    'icono' => $sub['icono'] ?? null
                ]);
            }
        }

        $res = $template->load('subvariables')->toArray();
        $res['imagen'] = $this->formatImageUrl($template->imagen);

        return response()->json($res);
    }

    public function destroy($id)
    {
        $template = MetricTemplate::findOrFail($id);
        if ($template->imagen) {
            $this->deletePhysicalImage($template->imagen);
        }
        $template->delete();

        return response()->json(['message' => 'Template de paquete de sensores eliminado correctamente.']);
    }
}
