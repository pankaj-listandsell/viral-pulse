<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One written reading: one sign, one language, one period.
 *
 * The horoscope pages fall back to a static pool when no row exists here, so
 * this table is an upgrade rather than a dependency - a failed generation run
 * costs the site freshness for a day, never a blank page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horoscope_readings', function (Blueprint $table) {
            $table->id();

            // The English key, in every language. Translating the identity as
            // well as the words would mean 'mesh' and 'aries' were two
            // different signs to the database.
            $table->string('sign', 20);
            $table->string('locale', 5);
            $table->string('period', 10);

            // The first day the reading covers: the date itself for a daily,
            // the Monday for a weekly, the 1st for a monthly. Storing the start
            // rather than a range keeps the lookup a single equality check.
            $table->date('period_start');

            $table->text('overview');
            $table->text('love');
            $table->text('career');
            $table->text('health');
            $table->text('money');
            $table->text('mantra')->nullable();

            $table->unsignedSmallInteger('lucky_number')->nullable();
            $table->string('lucky_color', 60)->nullable();
            $table->string('lucky_time', 60)->nullable();
            $table->string('lucky_direction', 60)->nullable();
            $table->string('mood', 60)->nullable();

            $table->unsignedTinyInteger('score')->nullable();
            $table->json('scores')->nullable();

            // 'ai' or 'manual'. An editor who rewrites a reading by hand must
            // not have it overwritten by the next generation run, so the
            // command skips anything not marked 'ai'.
            $table->string('source', 20)->default('ai');
            $table->string('model', 60)->nullable();

            $table->timestamps();

            // The page asks for exactly one row per sign, language and period,
            // and the generator relies on this to be idempotent: a second run
            // for the same morning updates rather than duplicating.
            $table->unique(['sign', 'locale', 'period', 'period_start'], 'horoscope_readings_unique');

            // Covers the cleanup job and the "did today already generate?"
            // check, both of which scan by period and date rather than by sign.
            $table->index(['period', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horoscope_readings');
    }
};
