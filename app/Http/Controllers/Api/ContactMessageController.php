<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    /**
     * Display a listing of the contact messages.
     * Automatically clears messages older than 3 days.
     */
    public function index()
    {
        // Limpieza de mensajes con más de 3 días de antigüedad
        ContactMessage::where('created_at', '<', now()->subDays(3))->delete();

        $messages = ContactMessage::orderBy('created_at', 'desc')->get();
        return response()->json($messages, 200);
    }

    /**
     * Store a newly created contact message in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|max:255',
            'telefono' => 'required|string|max:40',
            'mensaje' => 'required|string|min:10',
        ]);

        $message = ContactMessage::create($validated);

        return response()->json($message, 201);
    }

    /**
     * Update the specified contact message in storage.
     * Mainly used to mark as read.
     */
    public function update(Request $request, $id)
    {
        $message = ContactMessage::findOrFail($id);

        $validated = $request->validate([
            'leido' => 'required|boolean',
        ]);

        $message->update($validated);

        return response()->json($message, 200);
    }

    /**
     * Remove the specified contact message from storage.
     */
    public function destroy($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return response()->json(['message' => 'Mensaje de contacto eliminado con éxito.'], 200);
    }
}
