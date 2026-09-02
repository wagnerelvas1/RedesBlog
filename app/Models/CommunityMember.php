<?php

namespace App\Models;

use App\Enums\CommunityRole;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * The `community_user` pivot row describing one membership.
 *
 * @property int $id
 * @property int $community_id
 * @property int $user_id
 * @property CommunityRole $role
 * @property bool $is_creator
 * @property CarbonInterface|null $banned_at
 * @property int|null $banned_by
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class CommunityMember extends Pivot
{
    protected $table = 'community_user';

    public $incrementing = true;

    /**
     * The creator always counts as an admin.
     */
    public function isAdmin(): bool
    {
        return ! $this->isBanned()
            && ($this->is_creator || $this->role === CommunityRole::Admin);
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => CommunityRole::class,
            'is_creator' => 'boolean',
            'banned_at' => 'datetime',
        ];
    }
}
