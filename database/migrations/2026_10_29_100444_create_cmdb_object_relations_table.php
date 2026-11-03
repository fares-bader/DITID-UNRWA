<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmdb_object_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('cmdb_objects')->onDelete('cascade');
            $table->foreignId('child_id')->constrained('cmdb_objects')->onDelete('cascade');
            $table->string('relation_type')->nullable(); 
            
            $table->timestamps();
            
            $table->unique(['parent_id', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmdb_object_relations');
    }
};