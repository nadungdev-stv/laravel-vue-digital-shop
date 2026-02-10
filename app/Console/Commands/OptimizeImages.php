<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize {--dry-run : Show what would be optimized without making changes} {--all : Scan all subdirectories}';
    protected $description = 'Optimize existing images in storage (resize and compress)';

    private const MAX_WIDTH = 1200;
    private const MAX_HEIGHT = 1200;
    private const JPEG_QUALITY = 85;

    private int $optimizedCount = 0;
    private int $skippedCount = 0;
    private int $savedBytes = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $scanAll = $this->option('all');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting image optimization...');
        $this->info('Storage path: ' . storage_path('app/public'));

        $basePath = storage_path('app/public');

        if ($scanAll) {
            // Scan all subdirectories automatically
            $this->scanAndOptimize($basePath, $dryRun);
        } else {
            // Scan predefined directories
            $directories = ['products', 'gallery', 'variants', 'banners', 'categories', 'uploads', 'chat-images'];
            foreach ($directories as $directory) {
                $this->optimizeDirectory($directory, $dryRun);
            }
        }

        $this->newLine();
        $this->info('===== SUMMARY =====');
        $this->info("Optimized: {$this->optimizedCount} images");
        $this->info("Skipped: {$this->skippedCount} images");
        $this->info("Space saved: " . $this->formatBytes($this->savedBytes));

        return Command::SUCCESS;
    }

    private function scanAndOptimize(string $basePath, bool $dryRun): void
    {
        if (!is_dir($basePath)) {
            $this->error("Base path not found: {$basePath}");
            return;
        }

        // Get all subdirectories
        $dirs = glob($basePath . '/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $dirName = basename($dir);
            $this->optimizeDirectory($dirName, $dryRun);
        }

        // Also check root level images
        $this->info("\nProcessing: root");
        $files = glob($basePath . '/*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG}', GLOB_BRACE);
        foreach ($files as $file) {
            $this->optimizeImage($file, $dryRun);
        }
    }

    private function optimizeDirectory(string $directory, bool $dryRun): void
    {
        $path = storage_path('app/public/' . $directory);

        if (!is_dir($path)) {
            return; // Silently skip non-existent directories
        }

        $this->info("\nProcessing: {$directory}");

        // Include both lowercase and uppercase extensions
        $files = glob($path . '/*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);

        $this->line("  Found " . count($files) . " images");

        foreach ($files as $file) {
            $this->optimizeImage($file, $dryRun);
        }
    }

    private function optimizeImage(string $filePath, bool $dryRun): void
    {
        $filename = basename($filePath);
        $originalSize = filesize($filePath);

        // Get image info
        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            $this->warn("  Skip (not an image): {$filename}");
            $this->skippedCount++;
            return;
        }

        [$width, $height] = $imageInfo;
        $mimeType = $imageInfo['mime'];

        // Check if optimization is needed
        $needsResize = $width > self::MAX_WIDTH || $height > self::MAX_HEIGHT;

        if (!$needsResize && $mimeType !== 'image/jpeg') {
            // Skip if already small and not JPEG (can't compress much)
            $this->skippedCount++;
            return;
        }

        if ($dryRun) {
            $this->line("  Would optimize: {$filename} ({$width}x{$height})");
            $this->optimizedCount++;
            return;
        }

        // Create image resource
        $image = $this->createImageFromFile($filePath, $mimeType);
        if (!$image) {
            $this->warn("  Failed to load: {$filename}");
            $this->skippedCount++;
            return;
        }

        // Calculate new dimensions
        $newWidth = $width;
        $newHeight = $height;

        if ($needsResize) {
            $ratio = $width / $height;
            if ($width > $height) {
                $newWidth = min($width, self::MAX_WIDTH);
                $newHeight = (int)($newWidth / $ratio);
            } else {
                $newHeight = min($height, self::MAX_HEIGHT);
                $newWidth = (int)($newHeight * $ratio);
            }
        }

        // Resize if needed
        if ($newWidth !== $width || $newHeight !== $height) {
            $resized = imagecreatetruecolor($newWidth, $newHeight);

            if ($mimeType === 'image/png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        // Save to temp file first
        $tempPath = $filePath . '.tmp';
        $saved = $this->saveImage($image, $tempPath, $mimeType, self::JPEG_QUALITY);
        imagedestroy($image);

        if (!$saved) {
            @unlink($tempPath);
            $this->warn("  Failed to save: {$filename}");
            $this->skippedCount++;
            return;
        }

        $newSize = filesize($tempPath);

        // Only keep optimized version if smaller
        if ($newSize < $originalSize) {
            rename($tempPath, $filePath);
            $saved = $originalSize - $newSize;
            $this->savedBytes += $saved;
            $percent = round((1 - $newSize / $originalSize) * 100);
            $this->line("  Optimized: {$filename} (-{$percent}%, saved " . $this->formatBytes($saved) . ")");
            $this->optimizedCount++;
        } else {
            @unlink($tempPath);
            $this->skippedCount++;
        }
    }

    private function createImageFromFile(string $path, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
    }

    private function saveImage($image, string $path, string $mimeType, int $quality): bool
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($image, $path, $quality),
            'image/png' => imagepng($image, $path, (int)(9 - ($quality / 10))),
            'image/gif' => imagegif($image, $path),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, $path, $quality) : false,
            default => false,
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
