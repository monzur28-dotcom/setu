<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Presentation choices an administrator makes at runtime.
 *
 * Distinct from config/setu.php on purpose: that file holds rules
 * engineering owns and a deploy changes. This holds things whoever runs the
 * site changes on a Tuesday afternoon without asking anyone — the front
 * page's slide timing, how transparent the doors are, and whatever comes
 * next of that kind.
 *
 * Key/value rather than a column per setting, because the alternative is a
 * migration every time somebody wants a new slider.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->string('value', 255)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
