<?php

namespace Digidennis\FabricColors\Model\Indexer;

use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class Color implements ActionInterface, MviewActionInterface
{
    private ResourceConnection $resource;
    private LoggerInterface $logger;

    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Full reindex – alle produkter
     */
    public function executeFull()
    {
        try {
            $connection = $this->resource->getConnection();

            $colorTable = $this->resource->getTableName('digidennis_fabric_color');
            $imageTable = $this->resource->getTableName('digidennis_fabric_color_image');
            $productTable = $this->resource->getTableName('catalog_product_entity');

            // ryd egne tabeller
            $connection->delete($imageTable);
            $connection->delete($colorTable);

            // hent alle simple + configurable produkter (tilpas hvis du vil)
            $productIds = $connection->fetchCol(
                $connection->select()
                    ->from($productTable, ['entity_id'])
            );

            foreach ($productIds as $productId) {
                $this->executeRow((int)$productId);
            }

        } catch (\Throwable $e) {
            $this->logger->error('Fabric Color Indexer FULL failed: ' . $e->getMessage());
        }
    }

    /**
     * Reindex liste af IDs
     */
    public function executeList(array $ids)
    {
        foreach ($ids as $id) {
            $this->executeRow($id);
        }
    }

    /**
     * Reindex ét produkt
     */
    public function executeRow($id)
    {
        $productId = (int)$id;

        try {
            $connection = $this->resource->getConnection();

            $colorTable = $this->resource->getTableName('digidennis_fabric_color');
            $imageTable = $this->resource->getTableName('digidennis_fabric_color_image');

            $optionTable = $this->resource->getTableName('catalog_product_option');
            $optionTypeTable = $this->resource->getTableName('catalog_product_option_type_value');
            $optionTypeTitleTable = $this->resource->getTableName('catalog_product_option_type_title');

            // 1) slet eksisterende farver + billeder for produktet
            $colorIds = $connection->fetchCol(
                $connection->select()
                    ->from($colorTable, ['entity_id'])
                    ->where('product_id = ?', $productId)
            );

            if (!empty($colorIds)) {
                $connection->delete($imageTable, ['color_id IN (?)' => $colorIds]);
                $connection->delete($colorTable, ['entity_id IN (?)' => $colorIds]);
            }

            // 2) find custom options for produktet (tilpas evt. til kun "color"-option)
            $options = $connection->fetchAll(
                $connection->select()
                    ->from($optionTable, ['option_id', 'type'])
                    ->where('product_id = ?', $productId)
            );

            if (!$options) {
                return;
            }

            foreach ($options as $option) {
                $optionId = (int)$option['option_id'];

                // vi antager dropdown/radio som farve-options – tilpas hvis nødvendigt
                if (!in_array($option['type'], ['drop_down', 'radio', 'multiple', 'checkbox'], true)) {
                    continue;
                }

                // 3) hent option values (option_type_id + label)
                $values = $connection->fetchAll(
                    $connection->select()
                        ->from(['v' => $optionTypeTable], ['option_type_id', 'sort_order'])
                        ->joinLeft(
                            ['t' => $optionTypeTitleTable],
                            't.option_type_id = v.option_type_id AND t.store_id = 0',
                            ['title']
                        )
                        ->where('v.option_id = ?', $optionId)
                );

                if (!$values) {
                    continue;
                }

                foreach ($values as $value) {
                    $optionTypeId = (int)$value['option_type_id'];
                    $label        = (string)$value['title'];

                    // 4) hent billeder for denne option_type_id fra din upload-tabel
                    $rawImages = $connection->fetchAll(
                        $connection->select()
                            ->from($imageTable, ['image', 'sort_order'])
                            ->where('option_type_id = ?', $optionTypeId)
                    );

                    // 5) indsæt række i digidennis_fabric_color
                    $now = (new \DateTime())->format('Y-m-d H:i:s');

                    $colorData = [
                        'product_id'     => $productId,
                        'option_id'      => $optionId,
                        'option_type_id' => $optionTypeId,
                        'store_id'       => 0,
                        'color_label'    => $label,
                        'search_label'   => $label, // tilpas hvis du vil have anden søgetekst
                        'image'          => $rawImages[0]['image'] ?? null, // primært billede
                        'url_key'        => $this->slugify($label) . '-' . $productId,
                        'is_active'      => 1,
                        'created_at'     => $now,
                        'updated_at'     => $now
                    ];

                    $connection->insert($colorTable, $colorData);
                    $colorId = (int)$connection->lastInsertId($colorTable);

                    // 6) opdater billederækker til at pege på color_id (og ryd option_type_id hvis du vil)
                    foreach ($rawImages as $img) {
                        $connection->update(
                            $imageTable,
                            [
                                'color_id'   => $colorId,
                                'sort_order' => (int)$img['sort_order']
                            ],
                            [
                                'option_type_id = ?' => $optionTypeId,
                                'image = ?'          => $img['image']
                            ]
                        );
                    }
                }
            }

        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('Fabric Color Indexer ROW failed for product %d: %s', $productId, $e->getMessage())
            );
        }
    }

    /**
     * Mview entry point
     */
    public function execute($ids)
    {
        $this->executeList($ids);
    }

    /**
     * Simpel slug-generator til url_key
     */
    private function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('~[^a-z0-9]+~', '-', $text);
        $text = trim($text, '-');

        return $text ?: 'color';
    }
}
