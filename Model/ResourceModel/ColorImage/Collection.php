<?php

namespace Digidennis\FabricColors\Model\ResourceModel\ColorImage;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Digidennis\FabricColors\Model\ColorImage::class,
            \Digidennis\FabricColors\Model\ResourceModel\ColorImage::class
        );
    }
}
