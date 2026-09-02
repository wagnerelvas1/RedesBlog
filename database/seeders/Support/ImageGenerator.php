<?php

namespace Database\Seeders\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generates small placeholder PNGs in-process with GD.
 *
 * Seeding never reaches the network for images.
 */
final class ImageGenerator
{
    /**
     * Writes a tinted gradient PNG to the default disk and returns its key.
     */
    public static function store(string $folder, int $width = 800, int $height = 450): string
    {
        $path = trim($folder, '/').'/'.Str::ulid().'.png';

        Storage::disk((string) config('filesystems.default'))
            ->put($path, self::png($width, $height));

        return $path;
    }

    /**
     * Raw PNG bytes for a randomly tinted gradient.
     */
    public static function png(int $width = 800, int $height = 450): string
    {
        $image = imagecreatetruecolor(max(1, $width), max(1, $height));

        if ($image === false) {
            return '';
        }

        $red = random_int(30, 220);
        $green = random_int(30, 220);
        $blue = random_int(30, 220);

        for ($y = 0; $y < $height; $y++) {
            $shade = (int) ($y / max($height - 1, 1) * 60);
            $color = imagecolorallocate(
                $image,
                max(0, min(255, $red + $shade)),
                max(0, min(255, $green + $shade)),
                max(0, min(255, $blue + $shade)),
            );

            if ($color !== false) {
                imageline($image, 0, $y, $width, $y, $color);
            }
        }

        ob_start();
        imagepng($image, null, 6);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    /**
     * An in-memory uploaded file, for tests that exercise the real upload path.
     */
    public static function uploadedFile(string $name = 'image.png', int $width = 800, int $height = 450): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, self::png($width, $height));
    }
}
