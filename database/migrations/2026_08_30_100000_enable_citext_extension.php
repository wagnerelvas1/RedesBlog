<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enables the PostgreSQL `citext` extension.
 *
 * Case-insensitive columns (`users.username`, `users.email`, `communities.name`)
 * depend on it, so this migration must run before any of them is created.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS citext');
    }
};
