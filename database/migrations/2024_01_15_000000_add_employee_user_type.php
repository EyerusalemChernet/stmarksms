<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddEmployeeUserType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if employee user type already exists
        $exists = DB::table('user_types')->where('title', 'employee')->exists();
        
        if (!$exists) {
            DB::table('user_types')->insert([
                'title' => 'employee',
                'name' => 'Employee',
                'level' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('user_types')->where('title', 'employee')->delete();
    }
}
