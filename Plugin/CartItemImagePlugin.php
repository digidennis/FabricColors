<?php
namespace Digidennis\FabricColors\Plugin;

use Magento\Checkout\Block\Cart\Item\Renderer as CartItemRenderer;
use Digidennis\FabricColors\Model\ResourceModel\ColorCollectionFactory;

class CartItemImagePlugin
{
    protected $colorCollectionFactory;

    public function __construct(ColorCollectionFactory $colorCollectionFactory)
    {
        $this->colorCollectionFactory = $colorCollectionFactory;
    }

    public function afterGetProductThumbnail(CartItemRenderer $subject, $result)
    {
        $item = $subject->getItem();
        $options = $item->getOptionsByCode();

        // Her kan du læse din gemte color_id og slå image_path op
        // i digidennis_fabric_color og returnere et andet image‑objekt.

        return $result;
    }
}
