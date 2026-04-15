<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RenameAccountantToHrManager extends Migration
{
    public function up()
    {
        // Rename in user_types table
        DB::table('user_types')->where('title', 'accountant')->update([
            'title' => 'hr_manager',
            'name'  => 'HR Manager',
            'level' => 3,
        ]);

        // Rename existing accountant users
        DB::table('users')->where('user_type', 'accountant')->update([
            'user_type' => 'hr_manager',
        ]);

        // Remove librarian from user_types if it exists
        DB::table('user_types')->where('title', 'librarian')->delete();

        // Insert hr_manager if it doesn't exist yet (fresh installs)
        $exists = DB::table('user_types')->where('title', 'hr_manager')->exists();
        if (!$exists) {
            DB::table('user_types')->insert([
                'title'      => 'hr_manager',
                'name'       => 'HR Manager',
                'level'      => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        DB::table('user_types')->where('title', 'hr_manager')->update([
            'title' => 'accountant',
            'name'  => 'Accountant',
            'level' => 5,
        ]);
        DB::table('users')->where('user_type', 'hr_manager')->update([
            'user_type' => 'accountant',
        ]);
    }
}
