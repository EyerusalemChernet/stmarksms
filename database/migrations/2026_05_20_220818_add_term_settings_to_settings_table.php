<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTermSettingsToSettingsTable extends Migration
{
    public function up()
    {
        // Insert default term/semester and promotion settings if they don't exist
        $defaults = [
            ['type' => 'terms_per_semester',    'description' => '2'],   // 2 terms per semester
            ['type' => 'semesters_per_year',     'description' => '2'],   // 2 semesters per year
            ['type' => 'promotion_min_average',  'description' => '50'],  // minimum average to pass
            ['type' => 'promotion_mode',         'description' => 'auto'], // auto | manual
        ];

        foreach ($defaults as $row) {
            if (!DB::table('settings')->where('type', $row['type'])->exists()) {
                DB::table('settings')->insert(array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down()
    {
        DB::table('settings')->whereIn('type', [
            'terms_per_semester',
            'semesters_per_year',
            'promotion_min_average',
            'promotion_mode',
        ])->delete();
    }
}
