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

    public function updateImageMeta(int $colorId, string $path, string $label, int $sortOrder): void
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $connection->update(
            $table,
            ['label' => $label, 'sort_order' => $sortOrder],
            ['color_id = ?' => $colorId, 'image = ?' => $path]
        );
    }

    public function getPrimaryImagePath(int $colorId): ?string
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $select = $connection->select()
            ->from($table, ['image'])
            ->where('color_id = ?', $colorId)
            ->order('sort_order ASC')
            ->limit(1);

        return $connection->fetchOne($select) ?: null;
    }

    /**
     * Return all images as an array of rows
     *
     * @return array
     */
    public function getAllImages(): array
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $select = $connection->select()
            ->from($table, ['image_id', 'color_id', 'image', 'label', 'sort_order', 'updated_at'])
            ->order('color_id ASC, sort_order ASC');

        return $connection->fetchAll($select);
    }
}
