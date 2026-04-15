<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HRManagerSeeder extends Seeder
{
    public function run()
    {
        // Skip if already exists
        if (DB::table('users')->where('username', 'hr')->exists()) {
            return;
        }

        DB::table('users')->insert([
            'name'           => 'HR Manager',
            'email'          => 'hr@school.com',
            'username'       => 'hr',
            'password'       => Hash::make('hr123'),
            'user_type'      => 'hr_manager',
            'code'           => strtoupper(Str::random(10)),
            'remember_token' => Str::random(10),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }
}
