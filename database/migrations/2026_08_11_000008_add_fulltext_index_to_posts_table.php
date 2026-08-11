<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kept separate from the posts migration so the schema still builds on
     * drivers without FULLTEXT support (sqlite in particular).
     */
    public function up(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->fullText(['title', 'excerpt', 'content'], 'posts_search_fulltext');
        });
    }

    public function down(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->dropFullText('posts_search_fulltext');
        });
    }

    private function supportsFullText(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
