<?php

use App\Support\DeskAuthors;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Desk bylines: the columns, and the desks themselves (see DeskAuthors for
 * why desks rather than people).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_author')->default(false)->after('is_admin')->index();
            $table->json('author_categories')->nullable()->after('bio');
        });

        DeskAuthors::sync();
    }

    public function down(): void
    {
        // Desks that have bylines stay; deleting them would orphan the posts.
        DB::table('users')
            ->where('is_author', true)
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('posts')->whereColumn('posts.author_id', 'users.id'))
            ->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_author']);
            $table->dropColumn(['is_author', 'author_categories']);
        });
    }
};
