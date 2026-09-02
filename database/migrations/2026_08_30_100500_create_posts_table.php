<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Posts belong to a community and may carry any number of images through the
 * polymorphic `attachments` table.
 *
 * The vote counters and ranks are denormalised here and maintained by the
 * vote service so the feed sorts stay a single indexed read.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 300);
            $table->text('body')->nullable();
            $table->string('slug', 320);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('pinned_at')->nullable();
            $table->foreignId('pinned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('score')->default(0);
            $table->unsignedInteger('upvotes_count')->default(0);
            $table->unsignedInteger('downvotes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->double('hot_rank')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['community_id', 'is_pinned', 'created_at']);
            $table->index(['community_id', 'score']);
            $table->index(['community_id', 'hot_rank']);
            $table->index('user_id');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
