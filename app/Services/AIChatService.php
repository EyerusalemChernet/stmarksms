<?php

namespace App\Services;

use App\Helpers\Qs;
use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Models\MyClass;
use App\Models\StudentRecord;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIChatService
{
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('OLLAMA_URL', 'http://127.0.0.1:11434'), '/');
        $this->model   = env('OLLAMA_CHAT_MODEL', env('OLLAMA_MODEL', 'llama3'));
    }

    // ── Main entry point ──────────────────────────────────────────────────────

    /**
     * Send a chat message and return the AI response.
     * Uses /api/generate (compatible with all Ollama models including tinyllama).
     */
    public function chat(array $history, string $message): string
    {
        $user    = Auth::user();
        $context = $this->buildContext($user, $message);
        $prompt  = $this->buildPrompt($user, $context, $history, $message);

        try {
            $response = Http::timeout(12)->post("{$this->baseUrl}/api/generate", [
                'model'   => $this->model,
                'prompt'  => $prompt,
                'stream'  => false,
                'options' => [
                    'temperature' => 0.5,
                    'num_predict' => 250,
                ],
            ]);

            if ($response->successful()) {
                $text = trim($response->json('response') ?? '');
                return $text ?: $this->fallback($message);
            }

            Log::warning('AI Chat: Ollama returned ' . $response->status() . ' — ' . $response->body());
            return $this->fallback($message);

        } catch (\Throwable $e) {
            Log::error('AI Chat error: ' . $e->getMessage());
            return $this->fallback($message);
        }
    }

    // ── Context builder — pulls live DB data relevant to the question ─────────

    protected function buildContext(User $user, string $message): array
    {
        $ctx  = [];
        $msg  = strtolower($message);
        $year = Qs::getCurrentSession();

        // Always include basic school info
        $ctx['school_name']    = config('app.name', 'St. Mark School');
        $ctx['current_session'] = $year;
        $ctx['today']          = Carbon::now()->format('l, d M Y');

        // Role-specific context
        if (Qs::userIsTeamSA()) {
            $ctx['total_students'] = User::where('user_type', 'student')->count();
            $ctx['total_teachers'] = User::where('user_type', 'teacher')->count();
            $ctx['total_classes']  = MyClass::count();
        }

        // Student/class questions
        if (str_contains($msg, 'student') || str_contains($msg, 'class') || str_contains($msg, 'grade')) {
            $ctx['classes'] = MyClass::orderBy('name')
                ->withCount(['section'])
                ->get()
                ->map(fn($c) => ['name' => $c->name, 'sections' => $c->section_count])
                ->toArray();
        }

        // Calendar / event questions
        if (str_contains($msg, 'event') || str_contains($msg, 'calendar') || str_contains($msg, 'holiday')
            || str_contains($msg, 'exam') || str_contains($msg, 'schedule') || str_contains($msg, 'when')) {
            $upcoming = CalendarEvent::where('start_date', '>=', now()->toDateString())
                ->where('start_date', '<=', now()->addDays(60)->toDateString())
                ->orderBy('start_date')
                ->take(10)
                ->get(['title', 'start_date', 'type'])
                ->map(fn($e) => [
                    'title' => $e->title,
                    'date'  => $e->start_date->format('d M Y'),
                    'type'  => $e->type,
                ])
                ->toArray();
            $ctx['upcoming_events'] = $upcoming;
        }

        // Academic year questions
        if (str_contains($msg, 'academic year') || str_contains($msg, 'semester') || str_contains($msg, 'term')) {
            $current = AcademicYear::where('is_current', true)->first();
            if ($current) {
                $ctx['academic_year'] = [
                    'name'       => $current->name,
                    'eth_name'   => $current->eth_name,
                    'start_date' => $current->start_date->format('d M Y'),
                    'end_date'   => $current->end_date->format('d M Y'),
                    'status'     => $current->status,
                ];
            }
        }

        // Parent-specific: their children's info
        if (Qs::userIsParent()) {
            $children = StudentRecord::where('my_parent_id', $user->id)
                ->with(['user', 'my_class'])
                ->get()
                ->map(fn($sr) => [
                    'name'  => $sr->user->name ?? 'N/A',
                    'class' => $sr->my_class->name ?? 'N/A',
                    'adm_no'=> $sr->adm_no,
                ])
                ->toArray();
            $ctx['my_children'] = $children;
        }

        // Teacher-specific: their subjects
        if (Qs::userIsTeacher()) {
            $subjects = \App\Models\Subject::where('teacher_id', $user->id)
                ->with('my_class')
                ->get()
                ->map(fn($s) => ['subject' => $s->name, 'class' => $s->my_class->name ?? 'N/A'])
                ->toArray();
            $ctx['my_subjects'] = $subjects;
        }

        return $ctx;
    }

    // ── Prompt builder (for /api/generate — works with all models) ───────────

    protected function buildPrompt(User $user, array $ctx, array $history, string $message): string
    {
        $role    = ucwords(str_replace('_', ' ', $user->user_type));
        $ctxJson = json_encode($ctx, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt  = "You are the St. Mark School AI Assistant — helpful, friendly, and professional.\n\n";
        $prompt .= "CURRENT USER: {$user->name} ({$role})\n\n";
        $prompt .= "LIVE SCHOOL DATA:\n{$ctxJson}\n\n";
        $prompt .= "RULES:\n";
        $prompt .= "- Answer questions about the school using the data above.\n";
        $prompt .= "- Be concise (2-4 sentences). Use the user's name occasionally.\n";
        $prompt .= "- If data is not available, say so honestly.\n";
        $prompt .= "- Never make up student names, grades, or data not in the context.\n\n";

        // Include last 6 turns of history for context
        $recent = array_slice($history, -6);
        if (!empty($recent)) {
            $prompt .= "CONVERSATION HISTORY:\n";
            foreach ($recent as $turn) {
                $label   = $turn['role'] === 'user' ? $user->name : 'Assistant';
                $prompt .= "{$label}: {$turn['content']}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "{$user->name}: {$message}\nAssistant:";
        return $prompt;
    }

    // ── Fallback when Ollama is unavailable ───────────────────────────────────

    protected function fallback(string $message): string
    {
        $msg = strtolower($message);

        if (str_contains($msg, 'hello') || str_contains($msg, 'hi')) {
            return "Hello! I'm the St. Mark School Assistant. I'm having trouble connecting to my AI engine right now, but I'm here to help. Please try again in a moment.";
        }

        return "I'm sorry, I'm having trouble connecting to my AI engine right now. Please make sure Ollama is running (`ollama serve`) and try again. You can also check the system settings.";
    }
}
