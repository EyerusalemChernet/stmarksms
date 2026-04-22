<?php

namespace App\Http\Controllers;

use App\Services\AICommentService;
use Illuminate\Http\Request;

class AICommentController extends Controller
{
    protected AICommentService $ai;

    public function __construct(AICommentService $ai)
    {
        $this->middleware('auth');
        $this->ai = $ai;
    }

    public function generate(Request $request)
    {
        $request->validate([
            'student_name' => 'required|string|max:150',
            'subject'      => 'required|string|max:100',
            'assessment'   => 'required|numeric|min:0|max:30',
            'mid_exam'     => 'required|numeric|min:0|max:20',
            'final_exam'   => 'required|numeric|min:0|max:50',
            'attendance'   => 'nullable|numeric|min:0|max:100',
        ]);

        $comment = $this->ai->generateComment(
            $request->student_name,
            $request->subject,
            (float) $request->assessment,
            (float) $request->mid_exam,
            (float) $request->final_exam,
            $request->attendance ? (float) $request->attendance : null
        );

        return response()->json(['comment' => $comment]);
    }

    public function summarize(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:50',
        ]);

        $summary = $this->ai->summarizeMessage($request->message);

        return response()->json(['summary' => $summary]);
    }
}
