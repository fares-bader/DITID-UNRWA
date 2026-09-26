<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LocationSeeder extends Seeder
{
    public function run()
    {
        Location::truncate();
        
        $unrwaLocations = [
            'Damascus - Field Office',
            'Damascus - Area Office',
            'Aleppo - Area Office',
            'Homs - Area Office',
            'Deraa - Area Office',
            'Hama - Camp',
            'Lattakia - Camp'
        ];

        foreach ($unrwaLocations as $locName) {
            Location::factory()->create(['name' => $locName]);
        }


        $src = public_path('/img/demo/locations/');
        $dst = 'locations'.'/';
        $del_files = Storage::files($dst);
        foreach ($del_files as $del_file) {
            try { Storage::disk('public')->delete($dst.$del_file); } catch (\Exception $e) {}
        }
        $add_files = glob($src.'/*.*');
        foreach ($add_files as $add_file) {
            $file_to_copy = str_replace($src, '', $add_file);
            try { Storage::disk('public')->put($dst.$file_to_copy, file_get_contents($src.$file_to_copy)); } catch (\Exception $e) {}
        }
    }
}