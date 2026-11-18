<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The WhatsApp invitation text the admin's guest list sends, with {nama}
 * and {link} placeholders. Null = Guest::DEFAULT_MESSAGE. Not part of
 * SettingResource (which is also public); edited via /api/guest-message.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('guest_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('guest_message');
        });
    }
};
