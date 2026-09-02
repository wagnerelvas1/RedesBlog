<?php

namespace App\Http\Resources;

use App\Models\Community;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Community
 */
class CommunityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'rules' => $this->rules,
            'avatar_url' => $this->avatar_url,
            'banner_url' => $this->banner_url,
            'members_count' => $this->members_count,
            'posts_count' => $this->posts_count,
            'created_at' => $this->created_at?->toIso8601String(),
            'creator' => new UserSummaryResource($this->whenLoaded('creator')),
        ];
    }
}
