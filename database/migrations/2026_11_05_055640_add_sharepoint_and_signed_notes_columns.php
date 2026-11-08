<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('sharepoint_tenant_id')->nullable();
            $table->string('sharepoint_client_id')->nullable();
            $table->text('sharepoint_client_secret')->nullable();
            $table->string('sharepoint_site_id')->nullable();
            $table->string('sharepoint_drive_id')->nullable();
        });

        Schema::table('action_logs', function (Blueprint $table) {
            $table->string('signed_note_path')->nullable()->after('note');
        });
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'sharepoint_tenant_id', 
                'sharepoint_client_id', 
                'sharepoint_client_secret', 
                'sharepoint_site_id', 
                'sharepoint_drive_id'
            ]);
        });

        Schema::table('action_logs', function (Blueprint $table) {
            $table->dropColumn('signed_note_path');
        });
    }
};