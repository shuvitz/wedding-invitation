<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `?to=` name the visitor's invitation link carried when they
 * submitted, so the admin's guest list can show who replied. Taken from
 * the URL, so anyone can type any name: a label, not proof of identity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->string('invited_as', 100)->nullable()->after('form_key')->index();
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn('invited_as');
        });
    }
};
