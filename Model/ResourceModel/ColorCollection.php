<?php
namespace Digidennis\FabricColors\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Digidennis\FabricColors\Model\Color as ColorModel;
use Digidennis\FabricColors\Model\ResourceModel\Color as ColorResource;

class ColorCollection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(ColorModel::class, ColorResource::class);
    }
}
