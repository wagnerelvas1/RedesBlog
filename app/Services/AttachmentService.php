<?php

namespace App\Services;

use App\Models\Attachment;
use App\Repositories\AttachmentRepository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores and removes uploaded images for any model that owns attachments.
 *
 * Files never touch the `local`/`public` disks: everything goes to the
 * configured default disk (`s3`, backed by MinIO in development).
 */
final class AttachmentService
{
    public function __construct(
        private readonly AttachmentRepository $attachments,
    ) {}

    /**
     * Stores a single file and links it to the given model.
     */
    public function attachOne(Model $attachable, UploadedFile $file, string $folder, ?int $position = null): Attachment
    {
        return DB::transaction(function () use ($attachable, $file, $folder, $position): Attachment {
            $path = $this->store($file, $folder);

            try {
                return $this->attachments->createFor($attachable, [
                    ...$this->metadataFor($file),
                    'disk' => $this->diskName(),
                    'path' => $path,
                    'position' => $position ?? $this->attachments->nextPosition($attachable),
                ]);
            } catch (\Throwable $exception) {
                $this->disk()->delete($path);

                throw $exception;
            }
        });
    }

    /**
     * Stores every uploaded file, preserving the order they were sent in.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, Attachment>
     */
    public function attachMany(Model $attachable, array $files, string $folder): array
    {
        if ($files === []) {
            return [];
        }

        return DB::transaction(function () use ($attachable, $files, $folder): array {
            $position = $this->attachments->nextPosition($attachable);
            $stored = [];
            $created = [];

            try {
                foreach (array_values($files) as $file) {
                    $path = $this->store($file, $folder);
                    $stored[] = $path;

                    $created[] = $this->attachments->createFor($attachable, [
                        ...$this->metadataFor($file),
                        'disk' => $this->diskName(),
                        'path' => $path,
                        'position' => $position++,
                    ]);
                }
            } catch (\Throwable $exception) {
                foreach ($stored as $path) {
                    $this->disk()->delete($path);
                }

                throw $exception;
            }

            return $created;
        });
    }

    /**
     * Reconciles a model's attachments with an edit: drops everything not in
     * `$keepIds`, then appends the newly uploaded files.
     *
     * @param  array<int, int>  $keepIds
     * @param  array<int, UploadedFile>  $newFiles
     * @return array<int, Attachment>
     */
    public function sync(Model $attachable, array $keepIds, array $newFiles, string $folder): array
    {
        return DB::transaction(function () use ($attachable, $keepIds, $newFiles, $folder): array {
            foreach ($this->attachments->forModelExcept($attachable, $keepIds) as $attachment) {
                $this->deleteAttachment($attachment);
            }

            $this->reindex($attachable);

            return $this->attachMany($attachable, $newFiles, $folder);
        });
    }

    /**
     * Removes every attachment of a model, blobs included.
     */
    public function detachAll(Model $attachable): void
    {
        DB::transaction(function () use ($attachable): void {
            foreach ($this->attachments->forModel($attachable) as $attachment) {
                $this->deleteAttachment($attachment);
            }
        });
    }

    /**
     * Stores a file that is not tracked in the `attachments` table
     * (community banners, user avatars) and returns its key.
     */
    public function storeStandalone(UploadedFile $file, string $folder): string
    {
        return $this->store($file, $folder);
    }

    /**
     * Removes a standalone file previously stored with `storeStandalone`.
     */
    public function deleteStandalone(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $this->disk()->delete($path);
    }

    public function diskName(): string
    {
        return (string) config('filesystems.default');
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    private function store(UploadedFile $file, string $folder): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
        $path = trim($folder, '/').'/'.(string) Str::ulid().'.'.Str::lower($extension);

        $this->disk()->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        return $path;
    }

    private function deleteAttachment(Attachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
        $this->attachments->delete($attachment);
    }

    /**
     * Closes the gaps left in `position` after attachments were removed.
     */
    private function reindex(Model $attachable): void
    {
        $position = 0;

        foreach ($this->attachments->forModel($attachable) as $attachment) {
            $attachment->position = $position++;
            $attachment->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataFor(UploadedFile $file): array
    {
        [$width, $height] = $this->dimensionsOf($file);

        return [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function dimensionsOf(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_readable($path)) {
            return [null, null];
        }

        $size = @getimagesize($path);

        if ($size === false) {
            return [null, null];
        }

        return [$size[0], $size[1]];
    }
}
