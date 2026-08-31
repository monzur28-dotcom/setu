<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The photographs that cycle behind the front-page headline.
 *
 * These are MARKETING assets, not member media, and the distinction is the
 * whole reason this table exists rather than reusing `photos`. Member
 * photographs live on a private disk behind short-lived signed URLs because
 * they are somebody's face and somebody's consent. A stock wedding
 * photograph the admin uploaded is a public file with none of that weight,
 * and putting it through the signed-URL path would mean a marketing image
 * expiring every fifteen minutes.
 *
 * `product` is what makes one table serve a two-door front page: a slide can
 * belong to the matrimony half, the dating half, or the shared backdrop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('path');                         // on the `hero` disk
            $table->string('caption', 120)->nullable();      // credit or alt text
            $table->enum('product', ['BOTH', 'MATRIMONIAL', 'CONNECT'])->default('BOTH');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};
