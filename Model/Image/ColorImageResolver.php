<?php
namespace Digidennis\FabricColors\Model\Image;

use Magento\Catalog\Model\Product;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class ColorImageResolver
{
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getAbsolutePath(Product $product, array $optionValue): ?string
    {
        if (empty($optionValue['image'])) {
            return null;
        }

        $mediaDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $path = 'catalog/product/option/' . ltrim($optionValue['image'], '/');

        $absolute = $mediaDir->getAbsolutePath($path);

        return file_exists($absolute) ? $absolute : null;
    }

    public function getRelativePath(array $optionValue): ?string
    {
        return $optionValue['image'] ?? null;
    }
}
