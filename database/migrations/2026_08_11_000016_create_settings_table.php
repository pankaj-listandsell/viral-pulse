<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('general');
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->enum('type', ['string', 'text', 'boolean', 'integer', 'json', 'file'])->default('string');
            // Only is_public settings may ever be shared with the frontend.
            $table->boolean('is_public')->default(false);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
