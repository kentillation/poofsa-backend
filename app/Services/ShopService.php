<?php

namespace App\Services;

use App\Models\ShopModel;
use App\Models\ShopHistoryModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ShopService
{
    /**
     * Update shop information with image support
     *
     * @param \Illuminate\Http\Request $request
     * @param int $shopId
     * @param int $adminId
     * @return array
     */
    public static function updateShopService($request, $shopId, $adminId)
    {
        DB::beginTransaction();

        try {
            $shop = ShopModel::where('shop_id', $shopId)->first();

            if (!$shop) {
                throw new \Exception('Shop not found');
            }

            $hasImage = false;
            $removeImage = false;

            if ($request->hasFile('image')) {
                Log::info('Processing shop image upload', [
                    'shop_id' => $shopId,
                    'file_name' => $request->file('image')->getClientOriginalName(),
                    'file_size' => $request->file('image')->getSize()
                ]);

                $shopData = [
                    'shop_name' => $request->input('shop_name'),
                    'shop_type' => $request->input('shop_type'),
                    'shop_owner' => $request->input('shop_owner'),
                    'shop_address' => $request->input('shop_address'),
                    'shop_email' => $request->input('shop_email'),
                    'shop_contact_number' => $request->input('shop_contact_number'),
                    'is_active' => $request->input('is_active', $shop->is_active),
                    'open_at' => $request->input('open_at'),
                    'close_at' => $request->input('close_at'),
                    'is_overnight' => $request->input('is_overnight', false),
                ];
                $hasImage = true;
                $removeImage = $request->input('remove_image') === 'true';
            } else {
                $shopData = $request->json()->all();
                $hasImage = false;
                $removeImage = false;

                unset($shopData['admin_id']);
            }

            if (isset($shopData['admin_id'])) {
                unset($shopData['admin_id']);
            }

            if (isset($shopData['open_at']) && $shopData['open_at']) {
                if (preg_match('/^\d{2}:\d{2}$/', $shopData['open_at'])) {
                    $shopData['open_at'] = $shopData['open_at'] . ':00';
                }
            }

            if (isset($shopData['close_at']) && $shopData['close_at']) {
                if (preg_match('/^\d{2}:\d{2}$/', $shopData['close_at'])) {
                    $shopData['close_at'] = $shopData['close_at'] . ':00';
                }
            }

            if ($shopData['shop_email'] !== $shop->shop_email) {
                $exists = ShopModel::where('shop_email', $shopData['shop_email'])
                    ->where('shop_id', '!=', $shopId)
                    ->exists();

                if ($exists) {
                    throw new \Exception('Shop email already exists');
                }
            }

            $thumbnailPath = $shop->thumbnail_path;
            $standardPath = $shop->standard_image_path;
            $imageSizeKb = $shop->image_size_kb;

            if ($removeImage) {
                Log::info('Removing shop images', ['shop_id' => $shopId]);

                if ($shop->thumbnail_path) {
                    Storage::disk('public')->delete($shop->thumbnail_path);
                }
                if ($shop->standard_image_path) {
                    Storage::disk('public')->delete($shop->standard_image_path);
                }
                $thumbnailPath = null;
                $standardPath = null;
                $imageSizeKb = null;
            } elseif ($hasImage && $request->hasFile('image')) {
                Log::info('Uploading new shop images', ['shop_id' => $shopId]);

                if ($shop->thumbnail_path) {
                    Storage::disk('public')->delete($shop->thumbnail_path);
                }
                if ($shop->standard_image_path) {
                    Storage::disk('public')->delete($shop->standard_image_path);
                }

                $imageFile = $request->file('image');
                $imageOptimizer = new ImageOptimizerService();
                $imageData = $imageOptimizer->optimizeAndSaveShopImage($imageFile, (string)$shopId);

                Log::info('Shop images saved', [
                    'shop_id' => $shopId,
                    'thumbnail_path' => $imageData['thumbnail_path'],
                    'standard_path' => $imageData['standard_path'],
                    'size_kb' => $imageData['size_kb']
                ]);

                $thumbnailPath = $imageData['thumbnail_path'];
                $standardPath = $imageData['standard_path'];
                $imageSizeKb = $imageData['size_kb'];
            }

            $originalValues = $shop->getOriginal();
            $dirtyFields = [];

            $validFields = [
                'shop_name',
                'shop_type',
                'shop_owner',
                'shop_address',
                'shop_email',
                'shop_contact_number',
                'is_active',
                'open_at',
                'close_at',
                'is_overnight'
            ];

            foreach ($shopData as $field => $newValue) {
                if ($field === 'updated_at' || !in_array($field, $validFields)) {
                    continue;
                }

                $originalValue = $originalValues[$field] ?? null;

                if (in_array($field, ['open_at', 'close_at']) && $originalValue && $newValue) {
                    $originalTime = date('H:i', strtotime($originalValue));
                    $newTime = date('H:i', strtotime($newValue));
                    if ($originalTime != $newTime) {
                        $dirtyFields[$field] = [
                            'from' => $originalTime,
                            'to' => $newTime
                        ];
                    }
                } elseif ($originalValue != $newValue) {
                    $dirtyFields[$field] = [
                        'from' => $originalValue,
                        'to' => $newValue
                    ];
                }
            }

            // Add image changes to dirty fields (for history only)
            if ($removeImage) {
                $dirtyFields['image'] = [
                    'from' => 'Has image',
                    'to' => 'Image removed'
                ];
            } elseif ($hasImage && $request->hasFile('image') && $thumbnailPath) {
                $dirtyFields['image'] = [
                    'from' => $shop->thumbnail_path ? 'Has image' : 'No image',
                    'to' => 'Image updated'
                ];
            }

            // Prepare update data (only shop fields)
            $updateData = [
                'close_at' => $shopData['close_at'],
                'is_active' => (int)$shopData['is_active'],
                'is_overnight' => (int)$shopData['is_overnight'],
                'open_at' => $shopData['open_at'],
                'shop_address' => $shopData['shop_address'],
                'shop_contact_number' => $shopData['shop_contact_number'],
                'shop_email' => $shopData['shop_email'],
                'shop_name' => $shopData['shop_name'],
                'shop_owner' => $shopData['shop_owner'],
                'shop_type' => $shopData['shop_type'],
                'thumbnail_path' => $thumbnailPath,
                'standard_image_path' => $standardPath,
                'image_size_kb' => $imageSizeKb,
            ];

            // Update shop
            $shop->update($updateData);

            // Save history if there are changes
            if (!empty($dirtyFields)) {
                $description = self::formatShopChangesDescription($dirtyFields);

                // Create shop history record
                ShopHistoryModel::create([
                    'shop_id' => $shop->shop_id,
                    'modified_type_id' => 2, // 2 for update
                    'description' => $description,
                    'user_id' => $adminId,
                ]);
            }

            DB::commit();

            // Load fresh data
            $shop = $shop->fresh();

            // Format time fields for response (H:i format)
            if ($shop->open_at) {
                $shop->open_at = date('H:i', strtotime($shop->open_at));
            }
            if ($shop->close_at) {
                $shop->close_at = date('H:i', strtotime($shop->close_at));
            }

            return [
                'success' => true,
                'message' => 'Shop updated successfully' . ($hasImage ? ' with new logo' : ''),
                'data' => $shop,
                'changes' => $dirtyFields
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Update Shop Error: ' . $e->getMessage(), [
                'shop_id' => $shopId,
                'admin_id' => $adminId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Format shop changes description for history
     *
     * @param array $changes
     * @return string
     */
    private static function formatShopChangesDescription($changes)
    {
        $description = '';

        $fieldLabels = [
            'shop_name' => 'Shop name',
            'shop_type' => 'Shop type',
            'shop_owner' => 'Shop owner',
            'shop_address' => 'Shop address',
            'shop_email' => 'Shop email',
            'shop_contact_number' => 'Contact number',
            'is_active' => 'Status',
            'open_at' => 'Opening time',
            'close_at' => 'Closing time',
            'is_overnight' => 'Overnight operation',
            'image' => 'Shop image',
        ];

        foreach ($changes as $field => $change) {
            $label = $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field));

            // Format special values
            $fromValue = $change['from'];
            $toValue = $change['to'];

            if ($field === 'is_active') {
                $fromValue = $fromValue ? 'Active' : 'Inactive';
                $toValue = $toValue ? 'Active' : 'Inactive';
            } elseif ($field === 'is_overnight') {
                $fromValue = $fromValue ? 'Yes' : 'No';
                $toValue = $toValue ? 'Yes' : 'No';
            } elseif ($field === 'shop_contact_number') {
                $fromValue = $fromValue ?: 'Not set';
                $toValue = $toValue ?: 'Not set';
            } elseif (in_array($field, ['open_at', 'close_at'])) {
                $fromValue = $fromValue ? date('h:i A', strtotime($fromValue)) : 'Not set';
                $toValue = $toValue ? date('h:i A', strtotime($toValue)) : 'Not set';
            } elseif ($field === 'image') {
                // Already formatted
            } else {
                $fromValue = $fromValue ?: 'Not set';
                $toValue = $toValue ?: 'Not set';
            }

            $description .= "{$label}: From [{$fromValue}] To [{$toValue}]. ";
        }

        return trim($description);
    }
}
