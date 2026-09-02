<?php

namespace App\Http\Resources;

use App\Enums\CommunityRole;
use App\Models\CommunityMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A community member: the user plus their pivot state.
 *
 * @mixin User
 */
class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pivot = $this->resource->getRelationValue('pivot');
        $membership = $pivot instanceof CommunityMember ? $pivot : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'avatar_url' => $this->avatar_url,
            'role' => ($membership === null ? CommunityRole::Member : $membership->role)->value,
            'is_creator' => (bool) $membership?->is_creator,
            'banned_at' => $membership?->banned_at?->toIso8601String(),
            'joined_at' => $membership?->created_at?->toIso8601String(),
        ];
    }
}
