<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddMarkWeightRules extends Migration
{
    public function up()
    {
        $rules = [
            [
                'name'        => 'Assessment Max',
                'type'        => 'mark_weight',
                'condition'   => 'eq',
                'value'       => 30,
                'action'      => 'assessment_max',
                'active'      => 1,
                'description' => 'Maximum marks for Assessment component (St. Mark\'s format)',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Mid Exam Max',
                'type'        => 'mark_weight',
                'condition'   => 'eq',
                'value'       => 20,
                'action'      => 'mid_exam_max',
                'active'      => 1,
                'description' => 'Maximum marks for Mid Exam component (St. Mark\'s format)',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Final Exam Max',
                'type'        => 'mark_weight',
                'condition'   => 'eq',
                'value'       => 50,
                'action'      => 'final_exam_max',
                'active'      => 1,
                'description' => 'Maximum marks for Final Exam component (St. Mark\'s format)',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        // Only insert if not already present
        foreach ($rules as $rule) {
            $exists = DB::table('rules')->where('action', $rule['action'])->exists();
            if (!$exists) {
                DB::table('rules')->insert($rule);
            }
        }
    }

    public function down()
    {
        DB::table('rules')->whereIn('action', ['assessment_max', 'mid_exam_max', 'final_exam_max'])->delete();
    }
}
