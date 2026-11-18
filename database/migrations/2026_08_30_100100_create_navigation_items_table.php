<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flat navigation only for V1 — no `parent_id` (nested menus deferred) and
 * no `type` column: a plain string URL/path handles both internal paths
 * ("/about") and external links uniformly, so a discriminator isn't
 * needed until something (e.g. a Pages module) actually requires one.
 * No `site_id` — Denji is single-project-per-repository.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('url');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
    }
};
