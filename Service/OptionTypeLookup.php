<?php

namespace Digidennis\FabricColors\Service;

use Magento\Framework\App\ResourceConnection;

class OptionTypeLookup
{
    private ResourceConnection $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function getOptionTypeId(int $optionId, int $sortOrder): ?int
    {
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName('catalog_product_option_type_value');

        $result = $connection->fetchOne(
            "SELECT option_type_id 
             FROM $table 
             WHERE option_id = :option_id 
               AND sort_order = :sort_order
             LIMIT 1",
            [
                'option_id'  => $optionId,
                'sort_order' => $sortOrder,
            ]
        );

        return $result ? (int)$result : null;
    }
}
