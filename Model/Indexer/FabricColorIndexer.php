<?php

namespace Digidennis\FabricColors\Model\Indexer;

use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Digidennis\FabricColors\Service\PixelScanner;
use Digidennis\FabricColors\Model\ResourceModel\ColorImage\CollectionFactory as ColorImageCollectionFactory;

class FabricColorIndexer implements IndexerActionInterface, MviewActionInterface
{
    private ResourceConnection $resource;
    private ColorImageCollectionFactory $colorImageCollectionFactory;
    private PixelScanner $scanner;
    private LoggerInterface $logger;
    private int $batchSize = 100;

    public function __construct(
        ResourceConnection $resource,
        ColorImageCollectionFactory $colorImageCollectionFactory,
        PixelScanner $scanner,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->colorImageCollectionFactory = $colorImageCollectionFactory;
        $this->scanner = $scanner;
        $this->logger = $logger;
    }

    /**
     * Full reindex
     *
     * @return void
     */
    public function executeFull(): void
    {
        $connection = $this->resource->getConnection();
        $indexTable = $this->resource->getTableName('digidennis_fabric_color_index');

        try {
            $connection->truncateTable($indexTable);
        } catch (\Throwable $e) {
            $this->logger->error('FabricColorIndexer: truncate failed: ' . $e->getMessage());
        }

        $this->logger->info('FabricColorIndexer: starting full reindex');

        $collection = $this->colorImageCollectionFactory->create();
        $collection->addFieldToSelect(['image_id', 'color_id', 'image']);
        $collection->setPageSize($this->batchSize);

        $lastPage = (int)$collection->getLastPageNumber();
        if ($lastPage < 1) {
            $lastPage = 1;
        }

        for ($page = 1; $page <= $lastPage; $page++) {
            $collection->setCurPage($page);
            $collection->load();

            foreach ($collection as $item) {
                $this->processImageRow((int)$item->getData('color_id'), (string)$item->getData('image'));
            }

            $collection->clear();
            $this->logger->info(sprintf('FabricColorIndexer: completed page %d/%d', $page, $lastPage));
        }

        $this->logger->info('FabricColorIndexer: full reindex finished');
    }

    /**
     * Partial reindex for a list of ids
     *
     * @param array $ids
     * @return void
     */
    public function executeList(array $ids): void
    {
        foreach ($ids as $colorId) {
            $this->executeForColor((int)$colorId);
        }
    }

    /**
     * Reindex a single row
     *
     * @param int $id
     * @return void
     */
    public function executeRow($id): void
    {
        $this->executeForColor((int)$id);
    }

    /**
     * Mview entrypoint. Called by materialized view when rows change.
     *
     * @param mixed $ids
     * @return void
     */
    public function execute($ids): void
    {
        if ($ids === null) {
            $this->executeFull();
            return;
        }

        if (is_array($ids)) {
            $this->executeList($ids);
            return;
        }

        $this->executeRow($ids);
    }

    /**
     * Helper: reindex all images for a single color id
     *
     * @param int $colorId
     * @return void
     */
    private function executeForColor(int $colorId): void
    {
        $collection = $this->colorImageCollectionFactory->create();
        $collection->addFieldToSelect(['image_id', 'color_id', 'image']);
        $collection->addFieldToFilter('color_id', $colorId);
        $collection->setPageSize($this->batchSize);

        $lastPage = (int)$collection->getLastPageNumber();
        if ($lastPage < 1) {
            $lastPage = 1;
        }

        for ($page = 1; $page <= $lastPage; $page++) {
            $collection->setCurPage($page);
            $collection->load();

            foreach ($collection as $item) {
                $this->processImageRow((int)$item->getData('color_id'), (string)$item->getData('image'));
            }

            $collection->clear();
        }
    }

    /**
     * Process a single image and write result to index table
     *
     * @param int $colorId
     * @param string $imagePath
     * @return void
     */
    private function processImageRow(int $colorId, string $imagePath): void
    {
        $connection = $this->resource->getConnection();
        $indexTable = $this->resource->getTableName('digidennis_fabric_color_index');

        try {
            $start = microtime(true);
            $result = $this->scanner->scan($imagePath);
            $duration = round(microtime(true) - $start, 3);

            $data = [
                'color_id'      => $colorId,
                'image'         => $imagePath,
                'avg_r'         => $result['r'],
                'avg_g'         => $result['g'],
                'avg_b'         => $result['b'],
                'hex'           => $result['hex'],
                'lab_l'         => $result['lab']['l'],
                'lab_a'         => $result['lab']['a'],
                'lab_b'         => $result['lab']['b'],
                'status'        => 1,
                'error_message' => null,
                'scanned_at'    => (new \DateTime())->format('Y-m-d H:i:s')
            ];

            $connection->insertOnDuplicate($indexTable, $data, array_keys($data));

            $this->logger->info(sprintf(
                'FabricColorIndexer: scanned color_id=%d image=%s time=%ss',
                $colorId,
                $imagePath,
                $duration
            ));
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'FabricColorIndexer: scan failed for color_id=%d image=%s error=%s',
                $colorId,
                $imagePath,
                $e->getMessage()
            ));

            $errorData = [
                'color_id'      => $colorId,
                'image'         => $imagePath,
                'status'        => 0,
                'error_message' => $e->getMessage(),
                'scanned_at'    => (new \DateTime())->format('Y-m-d H:i:s')
            ];

            $connection->insertOnDuplicate($indexTable, $errorData, array_keys($errorData));
        }
    }
}
