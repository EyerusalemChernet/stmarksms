<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EthiopianGradingSystem extends Migration
{
    public function up()
    {
        // ── 1. Add "Upper Primary" class type if not present ─────────────────
        $upId = DB::table('class_types')->where('code', 'UP')->value('id');
        if (!$upId) {
            $upId = DB::table('class_types')->insertGetId([
                'name' => 'Upper Primary',
                'code' => 'UP',
            ]);
        }

        // ── 2. Reclassify Classes 5–8 as Upper Primary ───────────────────────
        DB::table('my_classes')
            ->whereIn('name', ['Class 5', 'Class 6', 'Class 7', 'Class 8'])
            ->update(['class_type_id' => $upId]);

        // ── 3. Wipe existing grades and seed correct Ethiopian scales ─────────
        Schema::disableForeignKeyConstraints();
        DB::table('grades')->truncate();
        Schema::enableForeignKeyConstraints();

        $nurseryId     = DB::table('class_types')->where('code', 'N')->value('id');   // 3
        $preNurseryId  = DB::table('class_types')->where('code', 'PN')->value('id');  // 2
        $crecheId      = DB::table('class_types')->where('code', 'C')->value('id');   // 1
        $primaryId     = DB::table('class_types')->where('code', 'P')->value('id');   // 4
        // $upId already set above

        // Nursery / Pre-Nursery / Creche — descriptive only, no letter grades
        $descriptive = [
            ['name' => 'Excellent',          'mark_from' => 80, 'mark_to' => 100, 'remark' => 'Excellent'],
            ['name' => 'Good',               'mark_from' => 60, 'mark_to' => 79,  'remark' => 'Good'],
            ['name' => 'Satisfactory',       'mark_from' => 40, 'mark_to' => 59,  'remark' => 'Satisfactory'],
            ['name' => 'Needs Improvement',  'mark_from' => 0,  'mark_to' => 39,  'remark' => 'Needs Improvement'],
        ];
        foreach ([$crecheId, $preNurseryId, $nurseryId] as $typeId) {
            foreach ($descriptive as $g) {
                DB::table('grades')->insert(array_merge($g, ['class_type_id' => $typeId]));
            }
        }

        // Primary (Classes 1–4) — letter grades
        $primaryGrades = [
            ['name' => 'A+', 'mark_from' => 90, 'mark_to' => 100, 'remark' => 'Outstanding'],
            ['name' => 'A',  'mark_from' => 80, 'mark_to' => 89,  'remark' => 'Excellent'],
            ['name' => 'B',  'mark_from' => 70, 'mark_to' => 79,  'remark' => 'Very Good'],
            ['name' => 'C',  'mark_from' => 60, 'mark_to' => 69,  'remark' => 'Good'],
            ['name' => 'D',  'mark_from' => 50, 'mark_to' => 59,  'remark' => 'Pass'],
            ['name' => 'F',  'mark_from' => 0,  'mark_to' => 49,  'remark' => 'Fail'],
        ];
        foreach ($primaryGrades as $g) {
            DB::table('grades')->insert(array_merge($g, ['class_type_id' => $primaryId]));
        }

        // Upper Primary (Classes 5–8) — stricter letter grades
        $upperGrades = [
            ['name' => 'A+', 'mark_from' => 90, 'mark_to' => 100, 'remark' => 'Outstanding'],
            ['name' => 'A',  'mark_from' => 75, 'mark_to' => 89,  'remark' => 'Excellent'],
            ['name' => 'B',  'mark_from' => 60, 'mark_to' => 74,  'remark' => 'Very Good'],
            ['name' => 'C',  'mark_from' => 50, 'mark_to' => 59,  'remark' => 'Good'],
            ['name' => 'D',  'mark_from' => 40, 'mark_to' => 49,  'remark' => 'Pass'],
            ['name' => 'F',  'mark_from' => 0,  'mark_to' => 39,  'remark' => 'Fail'],
        ];
        foreach ($upperGrades as $g) {
            DB::table('grades')->insert(array_merge($g, ['class_type_id' => $upId]));
        }
    }

    public function down()
    {
        // Revert Classes 5–8 back to Primary
        $primaryId = DB::table('class_types')->where('code', 'P')->value('id');
        DB::table('my_classes')
            ->whereIn('name', ['Class 5', 'Class 6', 'Class 7', 'Class 8'])
            ->update(['class_type_id' => $primaryId]);

        DB::table('class_types')->where('code', 'UP')->delete();
    }
}
