<?php
namespace Digidennis\FabricColors\Block\Product;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;
use Digidennis\FabricColors\Model\ResourceModel\ColorCollectionFactory;

class ColorJson extends Template
{
    protected $colorCollectionFactory;

    public function __construct(
        Template\Context $context,
        ColorCollectionFactory $colorCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->colorCollectionFactory = $colorCollectionFactory;
    }

    public function getColorData(Product $product): array
    {
        $collection = $this->colorCollectionFactory->create();
        $collection->addFieldToFilter('product_id', (int)$product->getId());

        $data = [];
        foreach ($collection as $color) {
            $data[] = [
                'option_type_id' => (int)$color->getData('option_type_id'),
                'label'          => $color->getData('color_label'),
                'url_key'        => $color->getData('url_key'),
                'image'          => $color->getData('image_path'),
                'hex'            => $color->getData('hex_color'),
            ];
        }

        return $data;
    }
}
