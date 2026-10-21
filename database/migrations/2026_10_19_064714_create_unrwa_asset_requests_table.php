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
        Schema::create('unrwa_asset_requests', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unsignedInteger('category_id');
            
            $table->unsignedInteger('model_id')->nullable();
            
            $table->integer('quantity')->default(1);
            
            $table->string('request_tier', 50)->default('User');
            
            $table->text('notes')->nullable();
            
            $table->string('status', 50)->default('Pending');
            
            $table->unsignedInteger('fulfilled_asset_id')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unrwa_asset_requests');
    }
};