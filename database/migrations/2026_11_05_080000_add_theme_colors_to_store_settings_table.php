<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('theme_primary', 7)->nullable();
            $table->string('theme_dark', 7)->nullable();
            $table->string('theme_accent', 7)->nullable();
            $table->string('theme_background', 7)->nullable();
            $table->string('theme_surface', 7)->nullable();
            $table->string('theme_text', 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'theme_primary', 'theme_dark', 'theme_accent',
                'theme_background', 'theme_surface', 'theme_text',
            ]);
        });
    }
};
