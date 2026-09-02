<?php

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

it('seeds a browsable application whose invariants hold', function (): void {
    Storage::fake('s3');

    $this->seed();

    expect(User::query()->count())->toBeGreaterThan(1)
        ->and(Community::query()->count())->toBeGreaterThan(0)
        ->and(Post::query()->count())->toBeGreaterThan(0);

    // The known development account exists.
    expect(User::query()->where('email', 'dev@redesblog.test')->exists())->toBeTrue();

    // Exactly one creator per community.
    $badCreators = DB::table('community_user')
        ->select('community_id')
        ->where('is_creator', true)
        ->groupBy('community_id')
        ->havingRaw('count(*) <> 1')
        ->count();

    expect($badCreators)->toBe(0);

    // members_count matches the non-banned pivot rows.
    foreach (Community::query()->get() as $community) {
        expect($community->members_count)->toBe(
            $community->members()->wherePivotNull('banned_at')->count(),
        );
    }

    // comments_count matches the live comments.
    foreach (Post::query()->limit(20)->get() as $post) {
        expect($post->comments_count)->toBe($post->allComments()->count());
    }

    // Votes are unique per user and item, and the scores were recomputed.
    $duplicateVotes = DB::table('votes')
        ->select('user_id', 'votable_type', 'votable_id')
        ->groupBy('user_id', 'votable_type', 'votable_id')
        ->havingRaw('count(*) > 1')
        ->count();

    expect($duplicateVotes)->toBe(0)
        ->and(Post::query()->whereNotNull('hot_rank')->count())
        ->toBe(Post::query()->count());

    // No attachment points at a row that is gone.
    $orphans = DB::table('attachments')
        ->where('attachable_type', (new Post)->getMorphClass())
        ->whereNotIn('attachable_id', fn ($query) => $query->select('id')->from('posts'))
        ->count();

    expect($orphans)->toBe(0);
})->group('slow');
