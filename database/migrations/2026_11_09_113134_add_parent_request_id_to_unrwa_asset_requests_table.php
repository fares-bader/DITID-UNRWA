<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('unrwa_asset_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_request_id')->nullable()->after('id');
            $table->foreign('parent_request_id')
                ->references('id')
                ->on('unrwa_asset_requests')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unrwa_asset_requests', function (Blueprint $table) {
            $table->dropForeign(['parent_request_id']);
            $table->dropColumn('parent_request_id');
        });
    }
};
