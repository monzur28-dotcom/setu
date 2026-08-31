<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Seeded reference data. Store IDs on profiles, never names —
        // labels change when the Bangla translation is revised.
        Schema::create('geo_divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name_en', 40);
            $table->string('name_bn', 60);
            $table->string('slug', 40)->unique();
        });

        Schema::create('geo_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('geo_divisions');
            $table->string('name_en', 40);
            $table->string('name_bn', 60);
            $table->string('slug', 40)->unique();
            $table->index('division_id');
        });

        Schema::create('geo_upazilas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('geo_districts');
            $table->string('name_en', 60);
            $table->string('name_bn', 80);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_upazilas');
        Schema::dropIfExists('geo_districts');
        Schema::dropIfExists('geo_divisions');
    }
};
