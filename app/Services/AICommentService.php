<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class AICommentService
{
    protected Client $http;
    protected string $model;
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('OLLAMA_URL', 'http://127.0.0.1:11434'), '/');
        $this->model   = env('OLLAMA_MODEL', 'tinyllama');
        $this->http    = new Client(['timeout' => 30]);
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Generate a personalised, evidence-based report card comment.
     *
     * @param float|null $previousTotal  Previous term total (0-100) for trend detection
     */
    public function generateComment(
        string $studentName,
        string $subject,
        float  $assessment,
        float  $midExam,
        float  $finalExam,
        ?float $attendance    = null,
        ?float $previousTotal = null
    ): string {
        $total   = $assessment + $midExam + $finalExam;
        $level   = $this->performanceLevel($total);
        $prompt  = $this->buildCommentPrompt(
            $studentName, $subject,
            $assessment, $midExam, $finalExam,
            $total, $level, $attendance, $previousTotal
        );

        try {
            $response = $this->http->post("{$this->baseUrl}/api/generate", [
                'json' => [
                    'model'   => $this->model,
                    'prompt'  => $prompt,
                    'stream'  => false,
                    'options' => ['temperature' => 0.7, 'num_predict' => 150],
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $text = trim($body['response'] ?? '');

            return $text ?: $this->fallbackComment($studentName, $subject, $level);
        } catch (RequestException $e) {
            Log::error('Ollama comment generation failed: ' . $e->getMessage());
            return $this->fallbackComment($studentName, $subject, $level);
        }
    }

    /**
     * Summarise a parent message into 1-2 sentences.
     */
    public function summarizeMessage(string $message): string
    {
        $prompt = "Summarize the following parent message in 1-2 short sentences. Be concise and professional:\n\n\"{$message}\"\n\nSummary:";

        try {
            $response = $this->http->post("{$this->baseUrl}/api/generate", [
                'json' => [
                    'model'   => $this->model,
                    'prompt'  => $prompt,
                    'stream'  => false,
                    'options' => ['temperature' => 0.3, 'num_predict' => 80],
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            return trim($body['response'] ?? substr($message, 0, 100) . '...');
        } catch (RequestException $e) {
            return substr($message, 0, 100) . '...';
        }
    }

    /**
     * Return the detected pattern key for a given score set.
     * Used by the front-end tooltip to explain why a comment was generated.
     */
    public function getPatternLabel(float $assessment, float $midExam, float $finalExam, ?float $previousTotal = null): string
    {
        $labels = [
            'strong_coursework_weak_exam'  => 'Good coursework, struggles under exam conditions',
            'strong_exam_weak_coursework'  => 'Strong exam performance, inconsistent daily work',
            'significant_struggle'         => 'Needs foundational support across all components',
            'excellence'                   => 'Mastery — extension and enrichment recommended',
            'significant_drop'             => 'Performance declined vs. previous term',
            'significant_improvement'      => 'Remarkable improvement vs. previous term',
            'consistent'                   => 'Consistent, steady progress',
        ];

        $pattern = $this->detectPattern($assessment, $midExam, $finalExam, $previousTotal);
        return $labels[$pattern] ?? $labels['consistent'];
    }

    // ── Pattern detection ────────────────────────────────────────────────────

    /**
     * Detect the dominant learning pattern from score distribution.
     */
    private function detectPattern(
        float  $assessment,
        float  $midExam,
        float  $finalExam,
        ?float $previousTotal = null
    ): string {
        $total          = $assessment + $midExam + $finalExam;
        $courseworkRatio = ($assessment + $midExam) / 50; // coursework out of 50
        $examRatio       = $finalExam / 50;               // final exam out of 50

        // Strong coursework, weak exam
        if ($courseworkRatio >= 0.7 && $examRatio < 0.5) {
            return 'strong_coursework_weak_exam';
        }

        // Strong exam, weak coursework
        if ($examRatio >= 0.7 && $courseworkRatio < 0.5) {
            return 'strong_exam_weak_coursework';
        }

        // Struggling across the board
        if ($total < 45) {
            return 'significant_struggle';
        }

        // Excellence
        if ($total >= 85) {
            return 'excellence';
        }

        // Significant drop from previous term
        if ($previousTotal !== null && ($previousTotal - $total) > 15) {
            return 'significant_drop';
        }

        // Significant improvement from previous term
        if ($previousTotal !== null && ($total - $previousTotal) > 15) {
            return 'significant_improvement';
        }

        return 'consistent';
    }

    /**
     * Return evidence-based guidance for a detected pattern.
     */
    private function getPatternGuidance(string $pattern): array
    {
        $guidance = [
            'strong_coursework_weak_exam' => [
                'focus'    => 'exam technique and timed practice',
                'evidence' => 'Scores indicate good understanding of material but difficulty demonstrating it under exam conditions.',
            ],
            'strong_exam_weak_coursework' => [
                'focus'    => 'consistent completion of daily assignments and classwork',
                'evidence' => 'Strong exam performance shows capability, but coursework scores suggest inconsistent effort on regular tasks.',
            ],
            'significant_struggle' => [
                'focus'    => 'foundational concepts and additional support',
                'evidence' => 'Current scores indicate the student would benefit from targeted intervention.',
            ],
            'excellence' => [
                'focus'    => 'extension and enrichment activities',
                'evidence' => 'Student has demonstrated mastery of the material.',
            ],
            'significant_drop' => [
                'focus'    => 'identifying barriers and providing support',
                'evidence' => 'Performance has declined compared to the previous term. A supportive conversation is recommended.',
            ],
            'significant_improvement' => [
                'focus'    => 'recognising and sustaining this growth',
                'evidence' => 'Student has shown remarkable improvement. This effort should be celebrated.',
            ],
            'consistent' => [
                'focus'    => 'maintaining steady progress',
                'evidence' => 'Student is performing at a consistent level.',
            ],
        ];

        return $guidance[$pattern] ?? $guidance['consistent'];
    }

    // ── Prompt building ──────────────────────────────────────────────────────

    private function buildCommentPrompt(
        string  $name,
        string  $subject,
        float   $assessment,
        float   $midExam,
        float   $finalExam,
        float   $total,
        string  $level,
        ?float  $attendance    = null,
        ?float  $previousTotal = null
    ): string {
        $pattern  = $this->detectPattern($assessment, $midExam, $finalExam, $previousTotal);
        $guidance = $this->getPatternGuidance($pattern);

        $prompt  = "You are a professional teacher writing an evidence-based report card comment.\n\n";
        $prompt .= "STUDENT DATA:\n";
        $prompt .= "- Name: {$name}\n";
        $prompt .= "- Subject: {$subject}\n";
        $prompt .= "- Assessment (max 30): {$assessment}\n";
        $prompt .= "- Mid Exam (max 20): {$midExam}\n";
        $prompt .= "- Final Exam (max 50): {$finalExam}\n";
        $prompt .= "- Total: {$total}/100\n";
        $prompt .= "- Performance Level: {$level}\n";

        if ($previousTotal !== null) {
            $prompt .= "- Previous Term Total: {$previousTotal}/100\n";
        }
        if ($attendance !== null) {
            $prompt .= "- Attendance: {$attendance}%\n";
        }

        $prompt .= "\nEVIDENCE-BASED OBSERVATION:\n";
        $prompt .= "\"{$guidance['evidence']}\"\n";
        $prompt .= "\nRECOMMENDED FOCUS AREA:\n";
        $prompt .= "\"{$guidance['focus']}\"\n";

        $prompt .= "\nWrite a 2-3 sentence professional comment that:\n";
        $prompt .= "1. Acknowledges the student's performance based on the data\n";
        $prompt .= "2. References the evidence-based observation naturally\n";
        $prompt .= "3. Suggests the recommended focus area in an encouraging way\n";
        $prompt .= "4. Uses the student's name\n";
        $prompt .= "5. Is warm and professional — no placeholders\n\n";
        $prompt .= "Teacher comment:";

        return $prompt;
    }

    // ── Fallback ─────────────────────────────────────────────────────────────

    private function performanceLevel(float $total): string
    {
        if ($total >= 80) return 'excellent';
        if ($total >= 65) return 'good';
        if ($total >= 50) return 'satisfactory';
        return 'needs improvement';
    }

    private function fallbackComment(string $name, string $subject, string $level): string
    {
        return match ($level) {
            'excellent'    => "{$name} has demonstrated outstanding performance in {$subject} this term. Keep up the excellent work!",
            'good'         => "{$name} has shown good understanding in {$subject}. Continued effort will yield even better results.",
            'satisfactory' => "{$name} has made satisfactory progress in {$subject}. Regular practice will help improve further.",
            default        => "{$name} needs additional support in {$subject}. Extra practice and attention in this area is recommended.",
        };
    }
}
