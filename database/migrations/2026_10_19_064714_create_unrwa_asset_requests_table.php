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
            
            // المستخدم الذي قام بالطلب
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // الفئة المطلوبة (للموظف العادي - إجباري)
            $table->unsignedInteger('category_id');
            
            // الموديل المطلوب (للمدير - اختياري)
            $table->unsignedInteger('model_id')->nullable();
            
            // عدد الأجهزة المطلوبة
            $table->integer('quantity')->default(1);
            
            // الأولوية أو مستوى الطلب (User, Manager, Admin)
            $table->string('request_tier', 50)->default('User');
            
            // مبررات الطلب / الملاحظات
            $table->text('notes')->nullable();
            
            // حالة الطلب (Pending, Approved, Fulfilled, Canceled)
            $table->string('status', 50)->default('Pending');
            
            // ربط الطلب بالـ Asset النهائي عند الصرف
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