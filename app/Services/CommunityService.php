<?php

namespace App\Services;

use App\Enums\CommunityRole;
use App\Exceptions\CommunityException;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\User;
use App\Repositories\CommunityRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Business rules for communities and membership.
 *
 * Every members_count mutation and every creator invariant is enforced here.
 */
final class CommunityService
{
    public function __construct(
        private readonly CommunityRepository $communities,
        private readonly AttachmentService $attachments,
    ) {}

    /**
     * Creates the community and attaches its creator as an immovable admin.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $creator, array $data, ?UploadedFile $avatar = null, ?UploadedFile $banner = null): Community
    {
        return DB::transaction(function () use ($creator, $data, $avatar, $banner): Community {
            $community = $this->communities->create([
                'name' => $data['name'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'rules' => $data['rules'] ?? null,
            ], $creator);

            if ($avatar !== null) {
                $community->avatar_path = $this->attachments->storeStandalone($avatar, 'communities');
            }

            if ($banner !== null) {
                $community->banner_path = $this->attachments->storeStandalone($banner, 'communities');
            }

            $community->save();

            $this->communities->attachMember($community, $creator, CommunityRole::Admin, true);
            $this->communities->syncMembersCount($community);

            return $community->refresh();
        });
    }

    /**
     * Updates everything except the name, which is immutable.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(
        Community $community,
        array $data,
        ?UploadedFile $avatar = null,
        ?UploadedFile $banner = null,
        bool $removeAvatar = false,
        bool $removeBanner = false,
    ): Community {
        return DB::transaction(function () use (
            $community,
            $data,
            $avatar,
            $banner,
            $removeAvatar,
            $removeBanner,
        ): Community {
            $this->communities->update($community, [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'rules' => $data['rules'] ?? null,
            ]);

            $community->avatar_path = $this->replaceImage(
                $community->avatar_path, $avatar, $removeAvatar,
            );
            $community->banner_path = $this->replaceImage(
                $community->banner_path, $banner, $removeBanner,
            );
            $community->save();

            return $community->refresh();
        });
    }

    public function grantAdmin(Community $community, User $target): void
    {
        $membership = $this->requireMembership($community, $target);

        if ($membership->is_creator) {
            throw CommunityException::creatorIsProtected();
        }

        $this->communities->updateMember($community, $target, [
            'role' => CommunityRole::Admin->value,
        ]);
    }

    public function revokeAdmin(Community $community, User $target): void
    {
        $membership = $this->requireMembership($community, $target);

        if ($membership->is_creator) {
            throw CommunityException::creatorIsProtected();
        }

        $this->communities->updateMember($community, $target, [
            'role' => CommunityRole::Member->value,
        ]);
    }

    public function banMember(Community $community, User $actor, User $target): void
    {
        $membership = $this->requireMembership($community, $target);

        if ($membership->is_creator) {
            throw CommunityException::creatorIsProtected();
        }

        if ($actor->id === $target->id) {
            throw CommunityException::cannotActOnSelf();
        }

        $this->communities->updateMember($community, $target, [
            'role' => CommunityRole::Member->value,
            'banned_at' => now(),
            'banned_by' => $actor->id,
        ]);

        $this->communities->syncMembersCount($community);
    }

    public function unbanMember(Community $community, User $target): void
    {
        $this->requireMembership($community, $target);

        $this->communities->updateMember($community, $target, [
            'banned_at' => null,
            'banned_by' => null,
        ]);

        $this->communities->syncMembersCount($community);
    }

    public function removeMember(Community $community, User $actor, User $target): void
    {
        $membership = $this->requireMembership($community, $target);

        if ($membership->is_creator) {
            throw CommunityException::creatorIsProtected();
        }

        if ($actor->id === $target->id) {
            throw CommunityException::cannotActOnSelf();
        }

        $this->communities->detachMember($community, $target);
        $this->communities->syncMembersCount($community);
    }

    public function join(Community $community, User $user): void
    {
        $membership = $this->communities->membership($community, $user);

        if ($membership !== null && $membership->isBanned()) {
            throw CommunityException::banned();
        }

        if ($membership !== null) {
            throw CommunityException::alreadyMember();
        }

        DB::transaction(function () use ($community, $user): void {
            $this->communities->attachMember($community, $user);
            $this->communities->syncMembersCount($community);
        });
    }

    public function leave(Community $community, User $user): void
    {
        $membership = $this->requireMembership($community, $user);

        if ($membership->is_creator) {
            throw CommunityException::creatorCannotLeave();
        }

        DB::transaction(function () use ($community, $user): void {
            $this->communities->detachMember($community, $user);
            $this->communities->syncMembersCount($community);
        });
    }

    /**
     * Soft delete; the stored images survive until a purge job runs.
     */
    public function delete(Community $community): void
    {
        $community->delete();
    }

    private function requireMembership(Community $community, User $user): CommunityMember
    {
        $membership = $this->communities->membership($community, $user);

        if ($membership === null) {
            throw CommunityException::notMember();
        }

        return $membership;
    }

    private function replaceImage(?string $current, ?UploadedFile $file, bool $remove): ?string
    {
        if ($file !== null) {
            $this->attachments->deleteStandalone($current);

            return $this->attachments->storeStandalone($file, 'communities');
        }

        if ($remove) {
            $this->attachments->deleteStandalone($current);

            return null;
        }

        return $current;
    }
}
