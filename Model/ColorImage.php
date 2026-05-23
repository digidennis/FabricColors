<?php

namespace Digidennis\FabricColors\Model;

use Magento\Framework\Model\AbstractModel;

class ColorImage extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Digidennis\FabricColors\Model\ResourceModel\ColorImage::class);
    }
}
