<?php

namespace App\Support;

use App\Models\Review;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Review photos are never stored as uploaded. Each one is decoded and
 * redrawn with GD, which drops EXIF (GPS location, camera serials) and any
 * payload hidden in the file, then saved as WebP at two sizes:
 *   review-photos/{uuid}.webp      longest side 1600 px
 *   review-photos/{uuid}-sm.webp   longest side 480 px
 */
class ReviewPhotos
{
    public const MAX_PER_REVIEW = 3;

    /** @return array{path: string, width: int, height: int} */
    public static function store(UploadedFile $file): array
    {
        $source = $file->getRealPath();
        $image = match ((string) $file->getMimeType()) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png' => @imagecreatefrompng($source),
            'image/webp' => @imagecreatefromwebp($source),
            default => false,
        };

        if (! $image) {
            throw new RuntimeException('That photo could not be read.');
        }

        $image = self::orient($image, $file);
        $disk = Storage::disk('public');
        $disk->makeDirectory('review-photos');
        $name = 'review-photos/'.Str::uuid();

        [$width, $height] = self::save($image, $disk->path($name.'.webp'), 1600, 80);
        self::save($image, $disk->path($name.'-sm.webp'), 480, 72);
        imagedestroy($image);

        return ['path' => $name.'.webp', 'width' => $width, 'height' => $height];
    }

    public static function thumb(string $path): string
    {
        return Str::replaceLast('.webp', '-sm.webp', $path);
    }

    public static function delete(string $path): void
    {
        Storage::disk('public')->delete([$path, self::thumb($path)]);
    }

    /** @return list<array{id: int, url: string, thumb: string, width: int, height: int}> */
    public static function forReview(Review $review): array
    {
        return $review->photos->sortBy('position')->map(fn ($photo) => [
            'id' => (int) $photo->id,
            'url' => asset('storage/'.$photo->path),
            'thumb' => asset('storage/'.self::thumb($photo->path)),
            'width' => (int) $photo->width,
            'height' => (int) $photo->height,
        ])->values()->all();
    }

    /**
     * Adds new photos and removes the ones the author unticked, keeping at
     * most three. Runs after the review row exists.
     *
     * @param  list<UploadedFile>  $uploads
     * @param  list<int>  $remove
     */
    public static function sync(Review $review, array $uploads, array $remove): void
    {
        foreach ($review->photos()->whereIn('id', $remove)->get() as $photo) {
            self::delete($photo->path);
            $photo->delete();
        }

        $room = self::MAX_PER_REVIEW - $review->photos()->count();
        $position = (int) $review->photos()->max('position');
        foreach (array_slice($uploads, 0, max(0, $room)) as $upload) {
            $stored = self::store($upload);
            $review->photos()->create([...$stored, 'position' => ++$position]);
        }
    }

    /** @return array{0: int, 1: int} */
    private static function save(\GdImage $image, string $target, int $longest, int $quality): array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $longest / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagewebp($canvas, $target, $quality);
        imagedestroy($canvas);

        return [$newWidth, $newHeight];
    }

    /** Phones store rotation in EXIF; apply it before EXIF is thrown away. */
    private static function orient(\GdImage $image, UploadedFile $file): \GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data((string) $file->getRealPath())['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
