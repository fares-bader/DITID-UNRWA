<?php

namespace Database\Seeders;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\Concerns\ReportsMemory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AssetSeeder extends Seeder
{
    use ReportsMemory;

    private $admin;

    private $locationIds;

    private $supplierIds;

public function run()
    {
        Actionlog::where('item_type', Asset::class)->delete();
        Asset::truncate();

        $this->ensureLocationsSeeded();
        $this->ensureSuppliersSeeded();

        $this->adminuser = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();
        $this->locationIds = Location::all()->pluck('id');
        $this->supplierIds = Supplier::all()->pluck('id');

        Asset::factory()->count(20)->laptopMbp()->state(new Sequence($this->getState()))->create();
        Asset::factory()->count(15)->laptopAir()->state(new Sequence($this->getState()))->create();
        Asset::factory()->count(10)->laptopSurface()->state(new Sequence($this->getState()))->create();
        Asset::factory()->count(20)->desktopLenovoI5()->state(new Sequence($this->getState()))->create();
        Asset::factory()->count(20)->desktopOptiplex()->state(new Sequence($this->getState()))->create();
        Asset::factory()->count(10)->tabletIpad()->state(new Sequence($this->getState()))->create();
        Asset::factory()->count(15)->phoneIphone12()->state(new Sequence($this->getState()))->create();
        Asset::factory()->count(10)->ultrasharp()->state(new Sequence($this->getState()))->create();

        $del_files = Storage::files('assets');
        foreach ($del_files as $del_file) {
            try { Storage::disk('public')->delete('assets'.'/'.$del_files); } catch (\Exception $e) {}
        }

        DB::table('checkout_requests')->truncate();
    }

    private function ensureLocationsSeeded()
    {
        if (! Location::count()) {
            $this->call(LocationSeeder::class);
        }
    }

    private function ensureSuppliersSeeded()
    {
        if (! Supplier::count()) {
            $this->call(SupplierSeeder::class);
        }
    }

    private function getState()
    {
        return function () {
            // Seeded assets are unassigned at creation, so location_id matches
            // rtd_location_id. Setting both here removes the need to run
            // snipeit:sync-asset-locations after seeding (that command exists
            // as a manual maintenance tool for production drift, not as a seed
            // dependency).
            $locationId = $this->locationIds->random();

            return [
                'rtd_location_id' => $locationId,
                'location_id' => $locationId,
                'supplier_id' => $this->supplierIds->random(),
                'created_by' => $this->adminuser->id,
            ];
        };
    }
}
