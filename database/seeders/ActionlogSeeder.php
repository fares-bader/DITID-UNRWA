<?php

namespace Database\Seeders;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\Concerns\ReportsMemory;
use Illuminate\Database\Seeder;

class ActionlogSeeder extends Seeder
{
    use ReportsMemory;

    public function run()
    {
        Actionlog::where('action_type', 'checkout')->delete();

        if (! Asset::count()) {
            $this->call(AssetSeeder::class);
        }

        if (! Location::count()) {
            $this->call(LocationSeeder::class);
        }

        $admin = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();

        $this->reportMemory('ActionlogSeeder start');

        // تخفيض العدد إلى 20 عملية تسليم لموظفين ليتناسب مع البيانات التجريبية
        memory_reset_peak_usage();
        Actionlog::factory()
            ->count(20)
            ->assetCheckoutToUser()
            ->create(['created_by' => $admin->id]);
        gc_collect_cycles();
        $this->reportMemory('ActionlogSeeder after 20 assetCheckoutToUser');

        // تخفيض العدد إلى 10 عمليات تسليم لمواقع
        memory_reset_peak_usage();
        Actionlog::factory()
            ->count(10)
            ->assetCheckoutToLocation()
            ->create(['created_by' => $admin->id]);
        gc_collect_cycles();
        $this->reportMemory('ActionlogSeeder after 10 assetCheckoutToLocation');

        // تخفيض العدد إلى 5 عمليات تسليم تراخيص
        memory_reset_peak_usage();
        Actionlog::factory()
            ->count(5)
            ->licenseCheckoutToUser()
            ->create(['created_by' => $admin->id]);
        gc_collect_cycles();
        $this->reportMemory('ActionlogSeeder after 5 licenseCheckoutToUser');
    }
}