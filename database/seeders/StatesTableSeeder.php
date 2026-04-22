<?php
namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('states')->delete();

        $regions = [
            'Addis Ababa',
            'Oromia',
            'Amhara',
            'Tigray',
            'SNNPR (Southern Nations)',
            'Somali',
            'Afar',
            'Benishangul-Gumuz',
            'Gambela',
            'Harari',
            'Dire Dawa',
            'Sidama',
            'South West Ethiopia',
        ];

        foreach ($regions as $region) {
            State::create(['name' => $region]);
        }
    }
}
