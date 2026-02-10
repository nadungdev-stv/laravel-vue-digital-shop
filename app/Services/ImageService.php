<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Default quality for JPEG compression (0-100)
     */
    private const DEFAULT_JPEG_QUALITY = 85;

    /**
     * Default quality for WebP compression (0-100)
     */
    private const DEFAULT_WEBP_QUALITY = 80;

    /**
     * Maximum dimension for resizing (maintains aspect ratio)
     */
    private const MAX_DIMENSION = 1200;

    /**
     * Thumbnail dimension
     */
    private const THUMBNAIL_SIZE = 300;

    /**
     * Store and optimize an uploaded image
     *
     * @param UploadedFile $file The uploaded file
     * @param string $directory Storage directory (e.g., 'products')
     * @param array $options Optional settings (max_width, max_height, quality)
     * @return string|null The storage path or null on failure
     */
    public function storeOptimized(UploadedFile $file, string $directory, array $options = []): ?string
    {
        $maxWidth = $options['max_width'] ?? self::MAX_DIMENSION;
        $maxHeight = $options['max_height'] ?? self::MAX_DIMENSION;
        $quality = $options['quality'] ?? self::DEFAULT_JPEG_QUALITY;

        // Get original image info
        $mimeType = $file->getMimeType();
        $originalPath = $file->getPathname();

        // Create image resource based on type
        $image = $this->createImageFromFile($originalPath, $mimeType);
        if (!$image) {
            // Fallback to regular storage if GD fails
            $path = $file->store($directory, 'public');
            return $path ? '/storage/' . $path : null;
        }

        // Get original dimensions
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // Calculate new dimensions (maintain aspect ratio)
        [$newWidth, $newHeight] = $this->calculateDimensions(
            $originalWidth,
            $originalHeight,
            $maxWidth,
            $maxHeight
        );

        // Resize if needed
        if ($newWidth !== $originalWidth || $newHeight !== $originalHeight) {
            $resized = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG
            if ($mimeType === 'image/png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefill($resized, 0, 0, $transparent);
            }

            imagecopyresampled(
                $resized,
                $image,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );

            imagedestroy($image);
            $image = $resized;
        }

        // Generate unique filename
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $storagePath = $directory . '/' . $filename;
        $fullPath = storage_path('app/public/' . $storagePath);

        // Ensure directory exists
        $dirPath = dirname($fullPath);
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        // Save optimized image
        $saved = $this->saveImage($image, $fullPath, $mimeType, $quality);
        imagedestroy($image);

        if (!$saved) {
            // Fallback to regular storage
            $path = $file->store($directory, 'public');
            return $path ? '/storage/' . $path : null;
        }

        return '/storage/' . $storagePath;
    }

    /**
     * Create a thumbnail from an image path
     *
     * @param string $imagePath Path relative to public storage
     * @param int $size Thumbnail size
     * @return string|null Thumbnail path or null on failure
     */
    public function createThumbnail(string $imagePath, int $size = self::THUMBNAIL_SIZE): ?string
    {
        // Convert storage path to full path
        $cleanPath = str_replace('/storage/', '', $imagePath);
        $fullPath = storage_path('app/public/' . $cleanPath);

        if (!file_exists($fullPath)) {
            return null;
        }

        $mimeType = mime_content_type($fullPath);
        $image = $this->createImageFromFile($fullPath, $mimeType);
        if (!$image) {
            return null;
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // Calculate dimensions for square crop from center
        $minDim = min($originalWidth, $originalHeight);
        $srcX = ($originalWidth - $minDim) / 2;
        $srcY = ($originalHeight - $minDim) / 2;

        // Create square thumbnail
        $thumbnail = imagecreatetruecolor($size, $size);

        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }

        imagecopyresampled(
            $thumbnail,
            $image,
            0, 0, (int)$srcX, (int)$srcY,
            $size, $size,
            $minDim, $minDim
        );

        imagedestroy($image);

        // Generate thumbnail path
        $pathInfo = pathinfo($cleanPath);
        $thumbFilename = $pathInfo['filename'] . '_thumb.' . ($pathInfo['extension'] ?? 'jpg');
        $thumbPath = $pathInfo['dirname'] . '/thumbs/' . $thumbFilename;
        $thumbFullPath = storage_path('app/public/' . $thumbPath);

        // Ensure directory exists
        $thumbDir = dirname($thumbFullPath);
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $saved = $this->saveImage($thumbnail, $thumbFullPath, $mimeType, self::DEFAULT_JPEG_QUALITY);
        imagedestroy($thumbnail);

        return $saved ? '/storage/' . $thumbPath : null;
    }

    /**
     * Create image resource from file
     */
    private function createImageFromFile(string $path, string $mimeType)
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return null; // GD not available
        }

        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
    }

    /**
     * Save image resource to file
     */
    private function saveImage($image, string $path, string $mimeType, int $quality): bool
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($image, $path, $quality),
            'image/png' => imagepng($image, $path, (int)(9 - ($quality / 10))), // PNG quality is 0-9
            'image/gif' => imagegif($image, $path),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, $path, $quality) : false,
            default => false,
        };
    }

    /**
     * Calculate new dimensions maintaining aspect ratio
     */
    private function calculateDimensions(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return [$width, $height];
        }

        $ratio = $width / $height;

        if ($width > $height) {
            $newWidth = min($width, $maxWidth);
            $newHeight = (int)($newWidth / $ratio);
        } else {
            $newHeight = min($height, $maxHeight);
            $newWidth = (int)($newHeight * $ratio);
        }

        // Double check bounds
        if ($newWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int)($newWidth / $ratio);
        }
        if ($newHeight > $maxHeight) {
            $newHeight = $maxHeight;
            $newWidth = (int)($newHeight * $ratio);
        }

        return [$newWidth, $newHeight];
    }

    /**
     * Get optimized image URL with optional width parameter
     * For use with image CDN or on-the-fly resizing
     */
    public function getOptimizedUrl(string $path, ?int $width = null): string
    {
        if (!$width || !$path) {
            return $path;
        }

        // If using a CDN with resize capability, add width parameter
        // For now, just return the original path
        return $path;
    }
}
