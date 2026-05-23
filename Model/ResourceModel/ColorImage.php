<?php

namespace Digidennis\FabricColors\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ColorImage extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('digidennis_fabric_color_image', 'image_id');
    }
}
