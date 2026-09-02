<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommentVoteController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\CommunityMemberController;
use App\Http\Controllers\CommunityMembershipController;
use App\Http\Controllers\CommunitySettingsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostPinController;
use App\Http\Controllers\PostSaveController;
use App\Http\Controllers\PostVoteController;
use App\Http\Controllers\SavedPostController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Communities
|--------------------------------------------------------------------------
*/

Route::get('communities', [CommunityController::class, 'index'])->name('communities.index');

Route::middleware('auth')->group(function (): void {
    Route::get('communities/create', [CommunityController::class, 'create'])
        ->name('communities.create');
    Route::post('communities', [CommunityController::class, 'store'])
        ->name('communities.store');
});

Route::prefix('c/{community}')->scopeBindings()->group(function (): void {
    Route::get('/', [CommunityController::class, 'show'])->name('communities.show');
    Route::get('about', [CommunityController::class, 'about'])->name('communities.about');

    Route::middleware('auth')->group(function (): void {
        Route::get('settings', [CommunitySettingsController::class, 'edit'])
            ->middleware('can:update,community')
            ->name('communities.settings.edit');
        Route::patch('settings', [CommunitySettingsController::class, 'update'])
            ->middleware('can:update,community')
            ->name('communities.settings.update');
        Route::delete('/', [CommunitySettingsController::class, 'destroy'])
            ->middleware(['password.confirm', 'can:delete,community'])
            ->name('communities.destroy');

        Route::post('membership', [CommunityMembershipController::class, 'store'])
            ->name('communities.join');
        Route::delete('membership', [CommunityMembershipController::class, 'destroy'])
            ->name('communities.leave');

        Route::get('members', [CommunityMemberController::class, 'index'])
            ->middleware('can:manageMembers,community')
            ->name('communities.members.index');
        // `{user}` is any user, not a child of `{community}` — membership is
        // validated by the service, which returns a readable error instead.
        Route::patch('members/{user}', [CommunityMemberController::class, 'update'])
            ->withoutScopedBindings()
            ->name('communities.members.update');
        Route::delete('members/{user}', [CommunityMemberController::class, 'destroy'])
            ->middleware('can:manageMembers,community')
            ->withoutScopedBindings()
            ->name('communities.members.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Posts
|--------------------------------------------------------------------------
*/

Route::prefix('c/{community}')->scopeBindings()->group(function (): void {
    Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show');

    Route::middleware('auth')->group(function (): void {
        Route::get('submit', [PostController::class, 'create'])->name('posts.create');
        Route::post('posts', [PostController::class, 'store'])->name('posts.store');

        Route::get('posts/{post}/edit', [PostController::class, 'edit'])
            ->middleware('can:update,post')
            ->name('posts.edit');
        Route::patch('posts/{post}', [PostController::class, 'update'])
            ->middleware('can:update,post')
            ->name('posts.update');
        Route::delete('posts/{post}', [PostController::class, 'destroy'])
            ->middleware('can:delete,post')
            ->name('posts.destroy');

        Route::put('posts/{post}/pin', [PostPinController::class, 'store'])
            ->middleware('can:pin,post')
            ->name('posts.pin');
        Route::delete('posts/{post}/pin', [PostPinController::class, 'destroy'])
            ->middleware('can:pin,post')
            ->name('posts.unpin');
    });
});

Route::middleware('auth')->group(function (): void {
    Route::put('posts/{post}/save', [PostSaveController::class, 'store'])
        ->middleware('can:save,post')
        ->name('posts.save');
    Route::delete('posts/{post}/save', [PostSaveController::class, 'destroy'])
        ->middleware('can:save,post')
        ->name('posts.unsave');

    Route::get('saved', [SavedPostController::class, 'index'])->name('posts.saved');
});

/*
|--------------------------------------------------------------------------
| Comments
|--------------------------------------------------------------------------
*/

Route::prefix('c/{community}/posts/{post}')->scopeBindings()->group(function (): void {
    Route::get('comments', [CommentController::class, 'index'])->name('comments.index');

    Route::middleware('auth')->group(function (): void {
        Route::post('comments', [CommentController::class, 'store'])->name('comments.store');
        Route::patch('comments/{comment}', [CommentController::class, 'update'])
            ->middleware('can:update,comment')
            ->name('comments.update');
        Route::delete('comments/{comment}', [CommentController::class, 'destroy'])
            ->middleware('can:delete,comment')
            ->name('comments.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Voting
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'throttle:60,1'])->group(function (): void {
    Route::put('posts/{post}/vote', [PostVoteController::class, 'store'])->name('posts.vote');
    Route::delete('posts/{post}/vote', [PostVoteController::class, 'destroy'])->name('posts.unvote');

    Route::put('comments/{comment}/vote', [CommentVoteController::class, 'store'])
        ->name('comments.vote');
    Route::delete('comments/{comment}/vote', [CommentVoteController::class, 'destroy'])
        ->name('comments.unvote');
});
