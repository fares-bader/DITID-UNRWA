<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompanySeeder extends Seeder
{
    public function run()
    {
        Log::debug('Seed companies (UNRWA Regions)');
        Company::truncate();
        
        Company::factory()->create(['name' => 'SFO (Syria Field Office)']);
        Company::factory()->create(['name' => 'LFO (Lebanon Field Office)']);
        Company::factory()->create(['name' => 'JFO (Jordan Field Office)']);
        Company::factory()->create(['name' => 'GFO (Gaza Field Office)']);
        Company::factory()->create(['name' => 'WBFO (West Bank Field Office)']);

        $src = public_path('/img/demo/companies/');
        $dst = 'companies'.'/';
        $del_files = Storage::files('companies/'.$dst);

        foreach ($del_files as $del_file) {
            $file_to_delete = str_replace($src, '', $del_file);
            try { Storage::disk('public')->delete($dst.$del_file); } catch (\Exception $e) {}
        }

        $add_files = glob($src.'/*.*');
        foreach ($add_files as $add_file) {
            $file_to_copy = str_replace($src, '', $add_file);
            try { Storage::disk('public')->put($dst.$file_to_copy, file_get_contents($src.$file_to_copy)); } catch (\Exception $e) {}
        }
    }
}