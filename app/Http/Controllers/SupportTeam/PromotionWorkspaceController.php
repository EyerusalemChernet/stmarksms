<?php

namespace App\Http\Controllers\SupportTeam;

use App\Http\Controllers\Controller;
use App\Models\PromotionDraft;
use App\Models\Section;
use App\Services\RedistributionService;
use Illuminate\Http\Request;

class PromotionWorkspaceController extends Controller
{
    public function __construct(protected RedistributionService $redistributionService)
    {
        $this->middleware('teamSA');
    }

    /** Auto-save: update a single draft's proposed section */
    public function updateDraft(Request $req, PromotionDraft $draft)
    {
        $req->validate(['proposed_section_id' => 'nullable|exists:sections,id']);

        $draft->update(['proposed_section_id' => $req->proposed_section_id]);

        return response()->json(['ok' => true, 'draft_id' => $draft->id]);
    }

    /** Toggle lock state on a draft */
    public function toggleLock(Request $req, PromotionDraft $draft)
    {
        $draft->update(['is_locked' => !$draft->is_locked]);

        return response()->json(['ok' => true, 'is_locked' => $draft->is_locked]);
    }

    /** Dynamically add a new section to the target class */
    public function addSection(Request $req)
    {
        $req->validate([
            'my_class_id'  => 'required|exists:my_classes,id',
            'section_name' => 'required|string|max:50',
            'capacity'     => 'nullable|integer|min:1|max:200',
        ]);

        $section = Section::create([
            'my_class_id' => $req->my_class_id,
            'name'        => $req->section_name,
            'capacity'    => $req->capacity,
            'active'      => 1,
        ]);

        return response()->json([
            'ok'      => true,
            'section' => [
                'id'       => $section->id,
                'name'     => $section->name,
                'capacity' => $section->capacity,
                'students' => [],
            ],
        ]);
    }

    /** Remove an empty section */
    public function removeSection(Section $section)
    {
        $hasStudents = PromotionDraft::where('proposed_section_id', $section->id)->exists();

        if ($hasStudents) {
            return response()->json([
                'ok'  => false,
                'msg' => 'Cannot remove a section that has students assigned. Reassign them first.',
            ], 422);
        }

        $section->delete();

        return response()->json(['ok' => true]);
    }
}
