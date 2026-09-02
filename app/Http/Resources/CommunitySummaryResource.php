<?php

namespace App\Http\Resources;

use App\Models\Community;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Community
 */
class CommunitySummaryResource extends JsonResource
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
            'avatar_url' => $this->avatar_url,
            'members_count' => $this->members_count,
            'is_member' => $this->when(
                $this->resource->getAttribute('is_member') !== null,
                fn (): bool => (bool) $this->resource->getAttribute('is_member'),
            ),
        ];
    }
}
