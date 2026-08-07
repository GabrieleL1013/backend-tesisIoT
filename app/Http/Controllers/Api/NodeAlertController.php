<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NodeAlert;
use Illuminate\Http\Request;

class NodeAlertController extends Controller
{
    /**
     * Listar alertas con filtros opcionales.
     */
    public function index(Request $request)
    {
        $query = NodeAlert::with('node:id,nombre,serial_number,categoria')
            ->orderBy('created_at', 'desc');

        // Filtro por tipo
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filtro por estado de lectura
        if ($request->has('status')) {
            if ($request->status === 'unread') {
                $query->where('is_read', false);
            } elseif ($request->status === 'read') {
                $query->where('is_read', true);
            }
        }

        // Filtro por nodo
        if ($request->has('node_id') && $request->node_id) {
            $query->where('node_id', $request->node_id);
        }

        // Búsqueda por texto (Case insensitive)
        if ($request->has('search') && $request->search) {
            $searchLower = strtolower($request->search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(message) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereHas('node', function ($nq) use ($searchLower) {
                      $nq->whereRaw('LOWER(serial_number) LIKE ?', ["%{$searchLower}%"])
                         ->orWhereRaw('LOWER(nombre) LIKE ?', ["%{$searchLower}%"]);
                  });
            });
        }

        $alerts = $query->paginate($request->get('per_page', 20));

        return response()->json($alerts);
    }

    /**
     * Contador de alertas no leídas.
     */
    public function unreadCount()
    {
        $count = NodeAlert::where('is_read', false)->count();
        return response()->json(['count' => $count]);
    }

    /**
     * Últimas alertas (para el dropdown de la campanita).
     */
    public function latest()
    {
        $alerts = NodeAlert::with('node:id,nombre,serial_number,categoria')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($alerts);
    }

    /**
     * Marcar una alerta como leída.
     */
    public function markRead($id)
    {
        $alert = NodeAlert::findOrFail($id);
        $alert->update(['is_read' => true]);

        return response()->json(['message' => 'Alerta marcada como leída.']);
    }

    /**
     * Marcar todas las alertas como leídas.
     */
    public function markAllRead()
    {
        NodeAlert::where('is_read', false)->update(['is_read' => true]);

        return response()->json(['message' => 'Todas las alertas marcadas como leídas.']);
    }

    /**
     * Eliminar múltiples alertas.
     */
    public function destroyBatch(Request $request)
    {
        $ids = $request->input('ids', []);
        if (is_array($ids) && count($ids) > 0) {
            NodeAlert::whereIn('id', $ids)->delete();
        }
        return response()->json(['message' => 'Alertas eliminadas con éxito.']);
    }

    /**
     * Eliminar una alerta.
     */
    public function destroy($id)
    {
        $alert = NodeAlert::findOrFail($id);
        $alert->delete();

        return response()->json(['message' => 'Alerta eliminada.']);
    }
}
