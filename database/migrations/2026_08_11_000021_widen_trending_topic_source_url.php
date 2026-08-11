<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Google News RSS links carry the whole article URL base64-encoded in the
     * path and routinely run past 255 characters, which made every fetch die on
     * a truncation error. Truncating the URL instead would only store a link
     * that no longer resolves.
     */
    public function up(): void
    {
        Schema::table('trending_topics', function (Blueprint $table) {
            $table->text('source_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('trending_topics', function (Blueprint $table) {
            $table->string('source_url')->nullable()->change();
        });
    }
};
