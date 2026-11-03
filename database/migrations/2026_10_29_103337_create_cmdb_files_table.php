<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmdb_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cmdb_object_id')->constrained('cmdb_objects')->onDelete('cascade');
            $table->string('filename'); 
            $table->string('original_name'); 
            $table->string('extension', 10)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmdb_files');
    }
};