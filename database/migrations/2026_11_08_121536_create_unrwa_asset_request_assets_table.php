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
        Schema::create('unrwa_asset_request_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedInteger('asset_id');
            $table->unsignedInteger('checkout_action_log_id')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('unrwa_asset_requests')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->foreign('checkout_action_log_id')->references('id')->on('action_logs')->nullOnDelete();
            $table->unique(['request_id', 'asset_id']);
            $table->unique('checkout_action_log_id');
            $table->index('batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unrwa_asset_request_assets');
    }
};
