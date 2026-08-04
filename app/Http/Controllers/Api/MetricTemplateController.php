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
                'imagen' => $template->imagen,
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

        $template = MetricTemplate::create([
            'nombre' => $tplTrans['es'],
            'nombre_en' => $tplTrans['en'],
            'imagen' => $request->imagen
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

        return response()->json($template->load('subvariables'), 201);
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
        $template->update([
            'nombre' => $tplTrans['es'],
            'nombre_en' => $tplTrans['en'],
            'imagen' => $request->imagen
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

        return response()->json($template->load('subvariables'));
    }

    public function destroy($id)
    {
        $template = MetricTemplate::findOrFail($id);
        $template->delete();

        return response()->json(['message' => 'Template de paquete de sensores eliminado correctamente.']);
    }
}
