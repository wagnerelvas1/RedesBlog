<?php

namespace App\Repositories;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Persistence for the polymorphic `attachments` table.
 */
final class AttachmentRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createFor(Model $attachable, array $attributes): Attachment
    {
        $attachment = new Attachment($attributes);
        $attachment->attachable()->associate($attachable);
        $attachment->save();

        return $attachment;
    }

    /**
     * @return Collection<int, Attachment>
     */
    public function forModel(Model $attachable): Collection
    {
        return Attachment::query()
            ->where('attachable_type', $attachable->getMorphClass())
            ->where('attachable_id', $attachable->getKey())
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Attachment>
     */
    public function forModelExcept(Model $attachable, array $ids): Collection
    {
        return Attachment::query()
            ->where('attachable_type', $attachable->getMorphClass())
            ->where('attachable_id', $attachable->getKey())
            ->when($ids !== [], fn ($query) => $query->whereNotIn('id', $ids))
            ->get();
    }

    public function delete(Attachment $attachment): void
    {
        $attachment->delete();
    }

    public function nextPosition(Model $attachable): int
    {
        $max = Attachment::query()
            ->where('attachable_type', $attachable->getMorphClass())
            ->where('attachable_id', $attachable->getKey())
            ->max('position');

        return $max === null ? 0 : ((int) $max) + 1;
    }
}
