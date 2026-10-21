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
        Schema::table('categories', function (Blueprint $table) {
            // إضافة الحقل الجديد ليكون فارغاً بشكل افتراضي (للفئات الأساسية)
            if (!Schema::hasColumn('categories', 'parent_id')) {
                $table->unsignedInteger('parent_id')->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'parent_id')) {
                $table->dropColumn('parent_id');
            }
        });
    }
};