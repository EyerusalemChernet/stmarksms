<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMasterSubjectsTable extends Migration
{
    public function up()
    {
        // 1. Create master_subjects catalog table
        Schema::create('master_subjects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Add master_subject_id to existing subjects table
        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('master_subject_id')->nullable()->after('id');
            $table->foreign('master_subject_id')
                  ->references('id')->on('master_subjects')
                  ->onDelete('set null');
        });

        // 3. Migrate existing data: group subjects by name → create one master per unique name
        $uniqueNames = DB::table('subjects')
            ->select('name', 'slug')
            ->groupBy('name', 'slug')
            ->orderBy('name')
            ->get();

        $usedCodes = [];
        foreach ($uniqueNames as $row) {
            // Make code unique — if slug is already taken, append a counter
            $code = $row->slug ?: null;
            if ($code) {
                $base = $code;
                $i = 2;
                while (in_array(strtolower($code), $usedCodes)) {
                    $code = $base . $i++;
                }
                $usedCodes[] = strtolower($code);
            }

            $masterId = DB::table('master_subjects')->insertGetId([
                'name'       => $row->name,
                'code'       => $code,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Link all class-subjects with this name to the master
            DB::table('subjects')
                ->where('name', $row->name)
                ->update(['master_subject_id' => $masterId]);
        }
    }

    public function down()
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['master_subject_id']);
            $table->dropColumn('master_subject_id');
        });

        Schema::dropIfExists('master_subjects');
    }
}
