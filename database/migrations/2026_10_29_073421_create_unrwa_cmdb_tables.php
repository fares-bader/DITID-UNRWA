<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmdb_object_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); 
            $table->string('icon', 50)->default('fas fa-server'); 
            $table->text('description')->nullable();
            

            $table->json('schema_definition')->nullable(); 
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cmdb_objects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('type_id')->constrained('cmdb_object_types')->onDelete('cascade');
            
            $table->json('attributes')->nullable(); 
            
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asset_cmdb_object', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('asset_id');
            $table->foreignId('cmdb_object_id')->constrained('cmdb_objects')->onDelete('cascade');
            
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            
            $table->timestamps();
            
            $table->unique(['asset_id', 'cmdb_object_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_cmdb_object');
        Schema::dropIfExists('cmdb_objects');
        Schema::dropIfExists('cmdb_object_types');
    }
};