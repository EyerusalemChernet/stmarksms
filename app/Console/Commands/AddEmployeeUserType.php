<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddEmployeeUserType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:employee-user-type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Employee user type to the database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Check if employee user type already exists
        $exists = DB::table('user_types')->where('title', 'employee')->exists();
        
        if ($exists) {
            $this->info('Employee user type already exists!');
            return 0;
        }

        // Insert employee user type
        DB::table('user_types')->insert([
            'title' => 'employee',
            'name' => 'Employee',
            'level' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info('Employee user type added successfully!');
        
        // Show all user types
        $this->info('Current user types:');
        $types = DB::table('user_types')->get();
        foreach ($types as $type) {
            $this->line("  - {$type->name} (title: {$type->title}, level: {$type->level})");
        }

        return 0;
    }
}
