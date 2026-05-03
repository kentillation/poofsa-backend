<?php

namespace App\Services;

use Intervention\Image\Drivers\Gd\Driver;  // Use the working driver
use Intervention\Image\ImageManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageOptimizerService
{
    const THUMBNAIL_SIZE = 300;
    const STANDARD_SIZE = 800;
    const THUMBNAIL_QUALITY = 60;
    const STANDARD_QUALITY = 75;

    protected ImageManager $manager;

    public function __construct()
    {
        // Use the same working driver from your test
        $this->manager = new ImageManager(new Driver());
    }

    // for Shops
    public function optimizeAndSaveShopImage(UploadedFile $file, string $shopId)
    {
        $filename = Str::random(40);

        // Create thumbnail (smaller, lower quality)
        $thumbnail = $this->createVersion($file, $filename, self::THUMBNAIL_SIZE, 'shop-images', 'thumb', self::THUMBNAIL_QUALITY);

        // Create standard version (larger, better quality)
        $standard = $this->createVersion($file, $filename, self::STANDARD_SIZE, 'shop-images', 'standard', self::STANDARD_QUALITY);

        return [
            'thumbnail_path' => $thumbnail['path'],
            'standard_path' => $standard['path'],
            'size_kb' => $standard['size']
        ];
    }

    // for Products
    public function optimizeAndSave(UploadedFile $file, string $productId)
    {
        $filename = Str::random(40);

        // Create thumbnail (smaller, lower quality)
        $thumbnail = $this->createVersion($file, $filename, self::THUMBNAIL_SIZE, 'product-images', 'thumb', self::THUMBNAIL_QUALITY);

        // Create standard version (larger, better quality)
        $standard = $this->createVersion($file, $filename, self::STANDARD_SIZE, 'product-images', 'standard', self::STANDARD_QUALITY);

        return [
            'thumbnail_path' => $thumbnail['path'],
            'standard_path' => $standard['path'],
            'size_kb' => $standard['size']
        ];
    }

    private function createVersion(UploadedFile $file, string $filename, int $maxWidth, string $directory, string $type, int $quality)
    {
        try {
            // Read image using the manager
            $image = $this->manager->read($file->getRealPath());

            // Scale down if needed (maintains aspect ratio automatically)
            if ($image->width() > $maxWidth) {
                $image->scale(width: $maxWidth);
            }

            // Encode as JPEG with specified quality
            $encodedImage = $image->encodeByExtension('jpg', quality: $quality);
            $imageData = (string) $encodedImage;
            $sizeKB = round(strlen($imageData) / 1024);

            // Ensure directory exists
            $directory = "{$directory}/{$type}";
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            // Save file
            $path = "{$directory}/{$filename}.jpg";
            Storage::disk('public')->put($path, $imageData);

            return [
                'path' => $path,
                'size' => $sizeKB
            ];

        } catch (\Exception $e) {
            Log::error('Image optimization failed: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'type' => $type
            ]);
            throw $e;
        }
    }
}
