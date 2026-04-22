<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class EthiopianizeStudentRecords extends Migration
{
    public function up()
    {
        // 1. Rename house → religion
        if (Schema::hasColumn('student_records', 'house') && !Schema::hasColumn('student_records', 'religion')) {
            Schema::table('student_records', function (Blueprint $table) {
                $table->renameColumn('house', 'religion');
            });
        }

        // 2. Replace Nigerian states with Ethiopian regions
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('lgas')->truncate();
        DB::table('states')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $regions = [
            'Addis Ababa', 'Oromia', 'Amhara', 'Tigray',
            'SNNPR (Southern Nations)', 'Somali', 'Afar',
            'Benishangul-Gumuz', 'Gambela', 'Harari',
            'Dire Dawa', 'Sidama', 'South West Ethiopia',
        ];
        foreach ($regions as $r) {
            DB::table('states')->insert(['name' => $r, 'created_at' => now(), 'updated_at' => now()]);
        }

        // 3. Replace Nigerian LGAs with Addis Ababa sub-cities (already truncated above)
        $addisId = DB::table('states')->where('name', 'Addis Ababa')->value('id');
        $subcities = [
            'Addis Ketema', 'Akaky Kaliti', 'Arada', 'Bole',
            'Gullele', 'Kirkos', 'Kolfe Keranio', 'Lideta',
            'Nifas Silk-Lafto', 'Yeka', 'Lemi Kura',
        ];
        foreach ($subcities as $sc) {
            DB::table('lgas')->insert([
                'state_id'   => $addisId,
                'name'       => $sc,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        if (Schema::hasColumn('student_records', 'religion') && !Schema::hasColumn('student_records', 'house')) {
            Schema::table('student_records', function (Blueprint $table) {
                $table->renameColumn('religion', 'house');
            });
        }
    }
}
