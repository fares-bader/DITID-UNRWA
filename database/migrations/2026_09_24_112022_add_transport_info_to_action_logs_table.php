<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
    {
        Schema::table('action_logs', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('note');
            $table->string('vehicle_number')->nullable()->after('driver_name');
            $table->string('contract_po_number')->nullable()->after('vehicle_number');
        });
    }

    public function down()
    {
        Schema::table('action_logs', function (Blueprint $table) {
            $table->dropColumn(['driver_name', 'vehicle_number', 'contract_po_number']);
        });
    }
};
