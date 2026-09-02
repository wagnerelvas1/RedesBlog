<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A comment node plus the replies already loaded beneath it.
 *
 * Deleted comments keep their place in the tree so their children stay
 * reachable, but their body and author are withheld.
 *
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isDeleted = $this->deleted_at !== null;

        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,
            'body' => $isDeleted ? '' : $this->body,
            'depth' => $this->depth,
            'score' => $this->score,
            'replies_count' => $this->replies_count,
            'edited_at' => $this->edited_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'is_deleted' => $isDeleted,
            'author' => $isDeleted || $this->author === null
                ? null
                : new UserSummaryResource($this->author),
            'attachment' => $isDeleted || $this->attachment === null
                ? null
                : new AttachmentResource($this->attachment),
            'viewer_vote' => (int) ($this->resource->getAttribute('viewer_vote') ?? 0),
            'can_update' => ! $isDeleted && $user?->can('update', $this->resource) === true,
            'can_delete' => ! $isDeleted && $user?->can('delete', $this->resource) === true,
            'replies' => $this->resource->relationLoaded('replies')
                ? self::collection($this->resource->getRelation('replies'))->resolve()
                : [],
            'has_more_replies' => (bool) ($this->resource->getAttribute('has_more_replies') ?? false),
        ];
    }
}
