<?php

namespace Digidennis\FabricColors\Model;

use Magento\Framework\Model\AbstractModel;

class Color extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(\Digidennis\FabricColors\Model\ResourceModel\Color::class);
    }
}
