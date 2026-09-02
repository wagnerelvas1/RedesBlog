<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nested comments stored with a materialised path so a whole subtree is one
 * indexed prefix scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->unsignedSmallInteger('depth')->default(0);
            $table->string('path', 255)->default('');
            $table->integer('score')->default(0);
            $table->unsignedInteger('upvotes_count')->default(0);
            $table->unsignedInteger('downvotes_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            $table->double('best_rank')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['post_id', 'parent_id', 'score']);
            $table->index('parent_id');
        });

        // A plain btree index cannot serve LIKE 'prefix%' under a non-C locale.
        DB::statement(
            'CREATE INDEX comments_path_prefix ON comments (post_id, path text_pattern_ops)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS comments_path_prefix');

        Schema::dropIfExists('comments');
    }
};
