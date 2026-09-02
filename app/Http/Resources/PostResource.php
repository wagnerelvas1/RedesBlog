<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'slug' => $this->slug,
            'is_pinned' => $this->is_pinned,
            'score' => $this->score,
            'upvotes_count' => $this->upvotes_count,
            'downvotes_count' => $this->downvotes_count,
            'comments_count' => $this->comments_count,
            'edited_at' => $this->edited_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => $this->author === null
                ? null
                : new UserSummaryResource($this->author),
            'community' => new CommunitySummaryResource($this->whenLoaded('community')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'viewer_vote' => (int) ($this->resource->getAttribute('viewer_vote') ?? 0),
            'is_saved' => (bool) ($this->resource->getAttribute('is_saved') ?? false),
            'can_update' => $user?->can('update', $this->resource) === true,
            'can_delete' => $user?->can('delete', $this->resource) === true,
            'can_pin' => $user?->can('pin', $this->resource) === true,
        ];
    }
}
