<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // A single flag rather than a role system: this site has one
            // administrator, everyone else is a reader.
            $table->boolean('is_admin')->default(false)->after('id');
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('avatar')->nullable()->after('email_verified_at');
            $table->text('bio')->nullable()->after('avatar');
            $table->boolean('is_active')->default(true)->after('bio');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();

            $table->index('is_admin');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_admin']);
            $table->dropIndex(['is_active']);
            $table->dropSoftDeletes();
            $table->dropColumn(['is_admin', 'username', 'avatar', 'bio', 'is_active', 'last_login_at']);
        });
    }
};
