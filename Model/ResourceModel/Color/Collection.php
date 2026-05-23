<?php

namespace Digidennis\FabricColors\Model\ResourceModel\Color;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Digidennis\FabricColors\Model\Color::class,
            \Digidennis\FabricColors\Model\ResourceModel\Color::class
        );
    }
}
