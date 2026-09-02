<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the public profile fields and makes the identity columns
 * case-insensitive, so `/u/Ada` and `/u/ada` resolve to the same account and
 * `Ada@example.com` cannot register twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 30)->after('name');
            $table->string('avatar_path')->nullable()->after('password');
            $table->string('bio', 500)->nullable()->after('avatar_path');
        });

        DB::statement('ALTER TABLE users ALTER COLUMN username TYPE citext');
        DB::statement('ALTER TABLE users ALTER COLUMN email TYPE citext');

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_username_unique');
            $table->dropColumn(['username', 'avatar_path', 'bio']);
        });

        DB::statement('ALTER TABLE users ALTER COLUMN email TYPE varchar(255)');
    }
};
