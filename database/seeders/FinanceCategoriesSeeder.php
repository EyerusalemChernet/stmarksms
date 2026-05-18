<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;

class FinanceCategoriesSeeder extends Seeder
{
    public function run()
    {
        $expenseCategories = [
            ['name' => 'Utilities', 'description' => 'Electricity, water, internet'],
            ['name' => 'Maintenance', 'description' => 'Building and equipment repairs'],
            ['name' => 'Supplies', 'description' => 'Office and classroom supplies'],
            ['name' => 'Events', 'description' => 'School events and activities'],
            ['name' => 'Transport', 'description' => 'Vehicle fuel and maintenance'],
            ['name' => 'Other', 'description' => 'Miscellaneous expenses'],
        ];

        $incomeCategories = [
            ['name' => 'Donations', 'description' => 'Donations from parents and sponsors'],
            ['name' => 'Grants', 'description' => 'Government and NGO grants'],
            ['name' => 'Events', 'description' => 'Income from school events'],
            ['name' => 'Canteen', 'description' => 'School canteen revenue'],
            ['name' => 'Other', 'description' => 'Miscellaneous income'],
        ];

        foreach ($expenseCategories as $cat) {
            ExpenseCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }

        foreach ($incomeCategories as $cat) {
            IncomeCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }
    }
}
