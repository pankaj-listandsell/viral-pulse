<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds "encrypted" to the settings type column.
     *
     * Raw SQL rather than a Blueprint change: altering an enum needs
     * doctrine/dbal, which this project does not carry, and the statement is
     * clearer than the workaround would be.
     */
    private const TYPES = "'string','text','boolean','integer','json','file'";

    public function up(): void
    {
        DB::statement(
            'ALTER TABLE settings MODIFY COLUMN type ENUM('.self::TYPES.",'encrypted') NOT NULL DEFAULT 'string'"
        );
    }

    public function down(): void
    {
        // Secrets first: a row left as 'encrypted' would not fit the old column,
        // and its value is unreadable ciphertext to anything that follows.
        DB::table('settings')->where('type', 'encrypted')->update(['value' => null, 'type' => 'string']);

        DB::statement(
            'ALTER TABLE settings MODIFY COLUMN type ENUM('.self::TYPES.") NOT NULL DEFAULT 'string'"
        );
    }
};
