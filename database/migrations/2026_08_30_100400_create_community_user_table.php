<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membership pivot carrying the per-community role, the creator flag and the
 * ban state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->boolean('is_creator')->default(false);
            $table->timestamp('banned_at')->nullable();
            $table->foreignId('banned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['community_id', 'user_id']);
            $table->index(['community_id', 'role']);
        });

        // Exactly one creator per community, enforced by the database.
        DB::statement(
            'CREATE UNIQUE INDEX community_user_one_creator ON community_user (community_id) WHERE is_creator'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS community_user_one_creator');

        Schema::dropIfExists('community_user');
    }
};
