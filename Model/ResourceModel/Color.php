<?php

namespace Digidennis\FabricColors\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Digidennis\FabricColors\Model\Color as ColorModel;

class Color extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('digidennis_fabric_color', 'entity_id');
    }

    public function loadByOptionTypeIntoModel(
        ColorModel $model,
        int $productId,
        int $optionId,
        int $optionTypeId
    ): ColorModel {
        $connection = $this->getConnection();
        $table      = $this->getMainTable();

        $data = $connection->fetchRow(
            $connection->select()
                ->from($table)
                ->where('product_id = ?', $productId)
                ->where('option_id = ?', $optionId)
                ->where('option_type_id = ?', $optionTypeId)
                ->limit(1)
        );

        if ($data) {
            $model->setData($data);
            $model->setId((int)$data['entity_id']);
        }

        return $model;
    }
}
