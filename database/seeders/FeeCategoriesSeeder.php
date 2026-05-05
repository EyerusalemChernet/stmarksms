<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeeCategory;

class FeeCategoriesSeeder extends Seeder
{
    public function run()
    {
        FeeCategory::truncate();
        $categories = [
            ['name' => 'Tuition',      'code' => 'TUI',  'description' => 'Main academic tuition fee'],
            ['name' => 'Registration', 'code' => 'REG',  'description' => 'Annual registration fee'],
            ['name' => 'Transport',    'code' => 'TRN',  'description' => 'School bus / transport fee'],
            ['name' => 'Activity',     'code' => 'ACT',  'description' => 'Sports and extracurricular activities'],
            ['name' => 'Other',        'code' => 'OTH',  'description' => 'Miscellaneous school fees'],
        ];
        foreach ($categories as $cat) {
            FeeCategory::create($cat);
        }
    }
}
