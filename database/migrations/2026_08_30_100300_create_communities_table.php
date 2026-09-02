<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Communities are the Reddit-style containers for posts.
 *
 * `name` is the immutable identity used in the `/c/{name}` URL and is stored as
 * `citext`, so `WebDev` and `webdev` are the same community.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 21);
            $table->string('title', 100);
            $table->string('description', 500)->nullable();
            $table->text('rules')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->boolean('is_private')->default(false);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('members_count')->default(0);
            $table->unsignedInteger('posts_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE communities ALTER COLUMN name TYPE citext');

        Schema::table('communities', function (Blueprint $table): void {
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};
