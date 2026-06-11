<?php

namespace Digidennis\FabricColors\Service;

use Digidennis\FabricColors\Model\ColorFactory;
use Digidennis\FabricColors\Model\ResourceModel\Color as ColorResource;
use Digidennis\FabricColors\Model\ColorImageFactory;
use Digidennis\FabricColors\Model\ResourceModel\ColorImage as ColorImageResource;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class FabricColorSaver
{
    private ColorFactory $colorFactory;
    private ColorResource $colorResource;
    private ColorImageFactory $imageFactory;
    private ColorImageResource $imageResource;
    private Filesystem $filesystem;
    private OptionTypeLookup $optionTypeLookup;

    public function __construct(
        ColorFactory $colorFactory,
        ColorResource $colorResource,
        ColorImageFactory $imageFactory,
        ColorImageResource $imageResource,
        Filesystem $filesystem,
        OptionTypeLookup $optionTypeLookup
    ) {
        $this->colorFactory     = $colorFactory;
        $this->colorResource    = $colorResource;
        $this->imageFactory     = $imageFactory;
        $this->imageResource    = $imageResource;
        $this->filesystem       = $filesystem;
        $this->optionTypeLookup = $optionTypeLookup;
    }

    public function save(int $productId, array $options): void
    {
        foreach ($options as $option) {
            if (empty($option['values'])) {
                continue;
            }

            foreach ($option['values'] as $value) {
                if (empty($value['fabric_image'])) {
                    continue;
                }

                $optionTypeId = isset($value['option_type_id'])
                    ? (int)$value['option_type_id']
                    : $this->optionTypeLookup->getOptionTypeId(
                        (int)$option['option_id'],
                        (int)$value['sort_order']
                    );

                if (!$optionTypeId) {
                    continue;
                }

                $color  = $this->loadOrCreateColor($productId, (int)$option['option_id'], $optionTypeId, $value);
                $images = $value['fabric_image'];

                $this->saveImages((int)$color->getId(), $images);
            }
        }
    }

    private function loadOrCreateColor(
        int $productId,
        int $optionId,
        int $optionTypeId,
        array $value
    ) {
        $color = $this->colorFactory->create();
        $this->colorResource->loadByOptionTypeIntoModel($color, $productId, $optionId, $optionTypeId);

        if ($color->getId()) {
            return $color;
        }

        $color->setData([
            'product_id'     => $productId,
            'option_id'      => $optionId,
            'option_type_id' => $optionTypeId,
            'color_label'    => $value['title'] ?? '',
            'search_label'   => $value['title'] ?? '',
            'color_code'     => $value['sku'] ?? null,
            'is_active'      => 1,
        ]);

        $this->colorResource->save($color);

        return $color;
    }

    private function saveImages(int $colorId, array $images): void
    {
        // Normalize incoming paths
        $normalize = function (string $path): string {
            $path = ltrim($path, '/');

            // Already has base folder?
            if (strpos($path, 'fabriccolors/') === 0) {
                return $path;
            }

            return 'fabriccolors/' . $path;
        };

        // Normalize new paths
        $newImages = [];
        foreach ($images as $sort => $img) {
            $newImages[] = [
                'path' => $normalize($img['file']),
                'label' => $img['name'] ?? '',
                'sort_order' => (int)$sort
            ];
        }

        // Load old images
        $oldImages = $this->imageResource->getImagesByColorId($colorId);

        // Normalize old paths
        $oldImagesNorm = [];
        foreach ($oldImages as $old) {
            $oldImagesNorm[] = [
                'path' => $normalize($old['image']),
                'label' => $old['label'],
                'sort_order' => (int)$old['sort_order']
            ];
        }

        // If identical → do nothing
        if ($newImages === $oldImagesNorm) {
            return; // NO-TOUCH
        }

        // Delete removed images
        $oldPaths = array_column($oldImagesNorm, 'path');
        $newPaths = array_column($newImages, 'path');

        $toDelete = array_diff($oldPaths, $newPaths);

        foreach ($toDelete as $path) {
            $this->deleteFile($path);
            $this->imageResource->deleteByPath($colorId, $path);
        }

        // Insert or update new images
        foreach ($newImages as $img) {
            // If exists, update sort_order + label
            if (in_array($img['path'], $oldPaths, true)) {
                $this->imageResource->updateImageMeta(
                    $colorId,
                    $img['path'],
                    $img['label'],
                    $img['sort_order']
                );
                continue;
            }

            // Otherwise insert new
            $image = $this->imageFactory->create();
            $image->setData([
                'color_id'   => $colorId,
                'image'      => $img['path'],
                'label'      => $img['label'],
                'sort_order' => $img['sort_order']
            ]);
            $this->imageResource->save($image);
        }
    }

    private function deleteFile(string $path): void
    {
        /*
        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        if ($media->isExist($path)) {
            $media->delete($path);
        }*/
    }
}
