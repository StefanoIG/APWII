<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Questions;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ChatBotController extends Controller
{
    /**
     * Normalizar texto.
     *
     * @param string $text
     * @return string
     */
    private function normalizeText($text)
    {
        // Convertir a minúsculas
        $text = strtolower($text);

        // Eliminar signos de puntuación y espacios extras
        $text = preg_replace('/[^\w\s]/u', '', $text);

        // Eliminar tildes o acentos
        $text = Str::ascii($text);

        return trim($text);
    }

    /**
     * Almacenar nuevas preguntas y respuestas.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!$this->verificarPermiso('Puede crear preguntas chatbot')) {
            return response()->json(['error' => 'No tienes permiso para crear preguntas chatbot'], 403);
        }

        $request->validate([
            'question' => 'required|string|max:255',
            'response' => 'required|string|max:1000'
        ]);

        // Normalizar la pregunta antes de almacenarla
        $normalizedQuestion = $this->normalizeText($request->input('question'));

        // Crear una nueva entrada en la base de datos
        Questions::create([
            'question' => $normalizedQuestion,
            'response' => $request->input('response')
        ]);

        return response()->json(['message' => 'Pregunta y respuesta guardadas con éxito']);
    }

    /**
     * Chat - Obtener respuesta para una pregunta.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chat(Request $request)
    {
        
        $request->validate([
            'question' => 'required|string|max:255'
        ]);

        // Normalizar la pregunta del usuario
        $normalizedQuestion = $this->normalizeText($request->input('question'));

        // Buscar la pregunta en la base de datos
        $question = Questions::where('question', $normalizedQuestion)->first();

        // Devolver la respuesta o un mensaje por defecto
        $response = $question->response ?? 'Lo siento, no entiendo la pregunta.';

        return response()->json(['response' => $response]);
    }

    /**
     * Obtener todas las preguntas y respuestas con paginación.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllQuestions(Request $request)
    {

        // Obtener todas las preguntas y respuestas con paginación (10 por página)
        $questions = Questions::paginate(10);

        // Retornar la respuesta en formato JSON
        return response()->json($questions);
    }

    /**
     * Verifica si el usuario autenticado tiene un permiso específico.
     *
     * @param string $permisoNombre
     * @return bool
     */
    private function verificarPermiso($permisoNombre)
    {
        $user = Auth::user();
        $roles = $user->roles;

        foreach ($roles as $rol) {
            if ($rol->permisos()->where('nombre', $permisoNombre)->exists()) {
                return true;
            }
        }

        return false;
    }
}
