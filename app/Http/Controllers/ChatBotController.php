<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Questions;
use Illuminate\Support\Str;

class ChatBotController extends Controller
{
    // Método para normalizar texto
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

    // Método para almacenar nuevas preguntas y respuestas
    public function store(Request $request)
    {
        // Normalizar la pregunta antes de almacenarla
        $normalizedQuestion = $this->normalizeText($request->input('question'));

        // Crear una nueva entrada en la base de datos
        Questions::create([
            'question' => $normalizedQuestion,
            'response' => $request->input('response')
        ]);

        return response()->json(['message' => 'Pregunta y respuesta guardadas con éxito']);
    }

    // Método para el chat
    public function chat(Request $request)
    {
        // Normalizar la pregunta del usuario
        $normalizedQuestion = $this->normalizeText($request->input('question'));

        // Buscar la pregunta en la base de datos
        $question = Questions::where('question', $normalizedQuestion)->first();

        // Devolver la respuesta o un mensaje por defecto
        $response = $question->response ?? 'Lo siento, no entiendo la pregunta.';

        return response()->json(['response' => $response]);
    }

    // Método para obtener todas las preguntas y respuestas con paginación
    public function getAllQuestions(Request $request)
    {
        // Obtener todas las preguntas y respuestas con paginación (10 por página)
        $questions = Questions::paginate(10);

        // Retornar la respuesta en formato JSON
        return response()->json($questions);
    }
}
