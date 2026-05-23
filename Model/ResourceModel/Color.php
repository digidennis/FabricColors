<?php

namespace Digidennis\FabricColors\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Color extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('digidennis_fabric_color', 'entity_id');
    }
}
