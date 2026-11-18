<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Homepage designation: a boolean flag rather than a reserved slug (e.g.
 * hardcoding "/home") — this lets an editor pick any real page as the
 * homepage without its slug needing to mean anything special, and lets a
 * page's slug stay independently meaningful. "At most one homepage" is
 * enforced in PageService (application-level invariant), not a DB
 * constraint — MySQL has no clean partial/filtered unique index, and this
 * mirrors the same pattern already used for "at most one active admin"
 * in Phase 1.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_home')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('is_home');
        });
    }
};
