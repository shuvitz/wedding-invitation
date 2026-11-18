<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops Sanctum's bearer-token table. It was published but never wired up
 * — App\Models\User never used HasApiTokens, so no token could ever be
 * issued or checked against it. Authentication is (and remains) Sanctum's
 * SPA cookie/session mode, which doesn't need this table. Reversible below
 * in case bearer-token API access is intentionally added later.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }
};
