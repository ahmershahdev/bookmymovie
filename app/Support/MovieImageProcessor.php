<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MovieImageProcessor
{
    private const WEBP_QUALITY = 88;

    public function storeAsWebp(UploadedFile $file, string $directory): string
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory($directory);

        $relativePath = trim($directory, '/').'/'.Str::uuid().'.webp';
        $absolutePath = $disk->path($relativePath);
        $sourcePath = $file->getRealPath();
        $mime = (string) $file->getMimeType();

        if (! $sourcePath) {
            throw new RuntimeException('The uploaded image could not be read.');
        }

        if ($this->storeWithImagick($sourcePath, $absolutePath)) {
            return $relativePath;
        }

        if ($this->storeWithGd($sourcePath, $absolutePath, $mime)) {
            return $relativePath;
        }

        if ($mime === 'image/webp') {
            $disk->put($relativePath, file_get_contents($sourcePath));

            return $relativePath;
        }

        throw new RuntimeException('WebP compression needs the PHP GD or Imagick extension enabled on this server.');
    }

    private function storeWithImagick(string $sourcePath, string $absolutePath): bool
    {
        if (! class_exists(\Imagick::class)) {
            return false;
        }

        $image = new \Imagick($sourcePath);
        $image->autoOrient();
        $image->stripImage();
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(self::WEBP_QUALITY);
        $image->writeImage($absolutePath);
        $image->clear();
        $image->destroy();

        return file_exists($absolutePath);
    }

    private function storeWithGd(string $sourcePath, string $absolutePath, string $mime): bool
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            return false;
        }

        $image = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? imagecreatefromjpeg($sourcePath) : false,
            'image/png' => function_exists('imagecreatefrompng') ? imagecreatefrompng($sourcePath) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (! $image) {
            return false;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $stored = imagewebp($image, $absolutePath, self::WEBP_QUALITY);
        imagedestroy($image);

        return $stored && file_exists($absolutePath);
    }
}
