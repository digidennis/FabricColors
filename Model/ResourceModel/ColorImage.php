<?php

namespace Digidennis\FabricColors\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ColorImage extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('digidennis_fabric_color_image', 'image_id');
    }

    public function getImagesByColorId(int $colorId): array
    {
        $connection = $this->getConnection();
        $table      = $this->getMainTable();

        return $connection->fetchAll(
            $connection->select()
                ->from($table)
                ->where('color_id = ?', $colorId)
                ->order('sort_order ASC')
        );
    }

    public function deleteByPath(int $colorId, string $path): void
    {
        $connection = $this->getConnection();
        $table      = $this->getMainTable();

        $connection->delete($table, [
            'color_id = ?' => $colorId,
            'image = ?'    => $path,
        ]);
    }
}
