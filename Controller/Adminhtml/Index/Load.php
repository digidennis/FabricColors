<?php

namespace Digidennis\FabricColors\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;

class Load extends Action
{
    protected $jsonFactory;
    protected $resource;

    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->resource = $resource;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $optionTypeId = $this->getRequest()->getParam('option_type_id');

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('digidennis_fabric_color_image');

        $rows = $connection->fetchAll(
            "SELECT image AS file, CONCAT('/media/fabriccolors/', image) AS url
             FROM $table WHERE option_type_id = ?", [$optionTypeId]
        );

        return $result->setData(['images' => $rows]);
    }
}
