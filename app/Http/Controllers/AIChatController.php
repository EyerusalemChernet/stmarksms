<?php

namespace App\Http\Controllers;

use App\Services\AIChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AIChatController extends Controller
{
    protected AIChatService $chat;

    public function __construct(AIChatService $chat)
    {
        $this->middleware('auth');
        $this->chat = $chat;
    }

    /**
     * POST /ai/chat
     * Accepts: { message: string, history: array }
     * Returns: { reply: string }
     */
    public function message(Request $req)
    {
        $req->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array|max:20',
        ]);

        $history = $req->input('history', []);
        $message = trim($req->input('message'));

        $reply = $this->chat->chat($history, $message);

        return response()->json([
            'ok'    => true,
            'reply' => $reply,
        ]);
    }

    /**
     * GET /ai/chat/status
     * Check if Ollama is reachable
     */
    public function status()
    {
        $url = rtrim(env('OLLAMA_URL', 'http://127.0.0.1:11434'), '/');
        try {
            $resp = \Illuminate\Support\Facades\Http::timeout(3)->get("{$url}/api/tags");
            $models = collect($resp->json('models', []))->pluck('name')->toArray();
            return response()->json([
                'ok'     => true,
                'models' => $models,
                'model'  => env('OLLAMA_CHAT_MODEL', env('OLLAMA_MODEL', 'llama3')),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'Ollama not reachable. Run: ollama serve']);
        }
    }
}
