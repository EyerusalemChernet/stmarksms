<?php
namespace Database\Seeders;

use App\Models\Lga;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LgasTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('lgas')->delete();

        // Addis Ababa sub-cities (state_id = 1)
        $addisSubcities = [
            'Addis Ketema', 'Akaky Kaliti', 'Arada', 'Bole',
            'Gullele', 'Kirkos', 'Kolfe Keranio', 'Lideta',
            'Nifas Silk-Lafto', 'Yeka', 'Lemi Kura',
        ];
        foreach ($addisSubcities as $sc) {
            Lga::create(['state_id' => 1, 'name' => $sc]);
        }

        // Oromia zones (state_id = 2)
        $oromiaZones = [
            'Arsi', 'Bale', 'Borena', 'East Hararghe', 'East Shewa',
            'East Wellega', 'Guji', 'Horo Guduru Wellega', 'Illubabor',
            'Jimma', 'Kelam Wellega', 'North Shewa', 'South West Shewa',
            'West Arsi', 'West Guji', 'West Hararghe', 'West Shewa', 'West Wellega',
        ];
        foreach ($oromiaZones as $z) {
            Lga::create(['state_id' => 2, 'name' => $z]);
        }

        // Amhara zones (state_id = 3)
        $amharaZones = [
            'Awi', 'Central Gondar', 'East Gojjam', 'North Gondar',
            'North Shewa', 'North Wollo', 'Oromia', 'South Gondar',
            'South Wollo', 'Wag Hemra', 'West Gojjam',
        ];
        foreach ($amharaZones as $z) {
            Lga::create(['state_id' => 3, 'name' => $z]);
        }

        // Tigray zones (state_id = 4)
        $tigrayZones = [
            'Central Tigray', 'Eastern Tigray', 'North Western Tigray',
            'Southern Tigray', 'South Eastern Tigray', 'Western Tigray',
        ];
        foreach ($tigrayZones as $z) {
            Lga::create(['state_id' => 4, 'name' => $z]);
        }

        // SNNPR zones (state_id = 5)
        $snnprZones = [
            'Dawro', 'Gamo', 'Gedeo', 'Gurage', 'Hadiya', 'Kaffa',
            'Konso', 'Sheka', 'Silte', 'Wolayita', 'Yem',
        ];
        foreach ($snnprZones as $z) {
            Lga::create(['state_id' => 5, 'name' => $z]);
        }

        // Remaining regions — add capital/main city as placeholder (state_ids 6-13)
        $others = [
            6  => ['Jigjiga', 'Degehabur', 'Gode', 'Kebri Dahar'],
            7  => ['Semera', 'Asaita', 'Dubti', 'Gewane'],
            8  => ['Assosa', 'Bambasi', 'Kamashi', 'Mao-Komo'],
            9  => ['Gambela', 'Abobo', 'Itang', 'Jikawo'],
            10 => ['Harar'],
            11 => ['Dire Dawa'],
            12 => ['Hawassa', 'Aleta Wendo', 'Bona Zuria', 'Chire'],
            13 => ['Mizan-Aman', 'Bench Sheko', 'Dawro', 'Kaffa'],
        ];
        foreach ($others as $stateId => $cities) {
            foreach ($cities as $city) {
                Lga::create(['state_id' => $stateId, 'name' => $city]);
            }
        }
    }
}
