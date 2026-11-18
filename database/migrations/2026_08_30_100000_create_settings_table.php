<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Singleton settings row — one Denji CMS install = one website, so this
 * table only ever holds a single record (see Setting::current()). Grouped
 * JSON columns instead of flat columns: each group maps 1:1 onto a form
 * section, and its shape can grow later (e.g. a new social platform)
 * without a migration. Not a generic key/value table — each column's
 * structure is still enforced by UpdateSettingRequest's nested validation.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->json('site_identity')->nullable();
            $table->json('contact')->nullable();
            $table->json('social')->nullable();
            $table->json('seo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
