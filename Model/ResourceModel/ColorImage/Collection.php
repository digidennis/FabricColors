<?php

namespace Digidennis\FabricColors\Model\ResourceModel\ColorImage;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Digidennis\FabricColors\Model\ColorImage as ColorImageModel;
use Digidennis\FabricColors\Model\ResourceModel\ColorImage as ColorImageResource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(ColorImageModel::class, ColorImageResource::class);
    }
}
