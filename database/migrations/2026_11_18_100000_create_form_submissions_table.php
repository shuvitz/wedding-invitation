<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public submissions of a page's `form` block. `form_key` matches the
 * block's `data.form_key`; `data` holds only the fields that block defined
 * at submit time (see BlockRegistry::submissionRules). Deleting a page
 * deletes its submissions with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('form_key');
            $table->json('data');
            $table->timestamps();

            $table->index(['page_id', 'form_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
