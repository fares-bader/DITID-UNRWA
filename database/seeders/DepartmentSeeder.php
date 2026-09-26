<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Location;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        Department::truncate();

        if (! Location::count()) {
            $this->call(LocationSeeder::class);
        }

        $locationIds = Location::all()->pluck('id');
        $sfoCompanyId = Company::where('name', 'like', '%SFO%')->first()->id ?? null;
        $admin = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();

        $unrwaDepartments = [
            'DITID (Information Technology)',
            'HR (Human Resources)',
            'Finance',
            'Procurement & Logistics',
            'Health Department',
            'Education Department',
            'Relief and Social Services (RSSP)'
        ];

        foreach ($unrwaDepartments as $deptName) {
            Department::factory()->create([
                'name' => $deptName,
                'location_id' => $locationIds->random(),
                'company_id' => $sfoCompanyId,
                'created_by' => $admin->id,
            ]);
        }
    }
}