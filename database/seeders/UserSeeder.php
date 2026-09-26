<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\Concerns\ReportsMemory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserSeeder extends Seeder
{
    use ReportsMemory;

    public function run()
    {
        User::truncate();
        DB::table('company_user')->truncate();

        if (! Company::count()) { $this->call(CompanySeeder::class); }
        $companyIds = Company::all()->pluck('id');

        if (! Department::count()) { $this->call(DepartmentSeeder::class); }
        $departmentIds = Department::all()->pluck('id');

        foreach (['firstAdmin', 'snipeAdmin', 'testAdmin'] as $state) {
            $user = User::factory()->{$state}()->withoutCompany()->create([
                'department_id' => $departmentIds->random(),
            ]);
            $user->companies()->sync($companyIds->random(2)->toArray());
            $user->syncLegacyCompanyIdMirror();
        }

        $departmentState = fn () => new Sequence(fn () => [
            'department_id' => $departmentIds->random(),
        ]);

        User::factory()->count(20)->viewAssets()
            ->withoutCompany()
            ->state($departmentState())
            ->create()
            ->each(function (User $user) use ($companyIds) {
                $user->companies()->sync([$companyIds->random()]);
                $user->syncLegacyCompanyIdMirror();
            });

        User::factory()->count(20)->viewAssets()
            ->withoutCompany()
            ->state($departmentState())
            ->create();

        $src = public_path('/img/demo/avatars/');
        $dst = 'avatars'.'/';
        $del_files = Storage::files($dst);
        foreach ($del_files as $del_file) {
            try { Storage::disk('public')->delete($dst.$del_file); } catch (\Exception $e) {}
        }
        $add_files = glob($src.'/*.*');
        foreach ($add_files as $add_file) {
            $file_to_copy = str_replace($src, '', $add_file);
            try { Storage::disk('public')->put($dst.$file_to_copy, file_get_contents($src.$file_to_copy)); } catch (\Exception $e) {}
        }

        $users = User::orderBy('id', 'asc')->take(20)->get();
        $file_number = 1;
        foreach ($users as $user) {
            $user->avatar = $file_number.'.jpg';
            $user->save();
            $file_number++;
        }
    }
}