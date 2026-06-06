<?php

namespace Digidennis\FabricColors\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Digidennis\FabricColors\Service\FabricColorSaver;
use Psr\Log\LoggerInterface;

class SaveFabricColors implements ObserverInterface
{
    private FabricColorSaver $saver;
    private LoggerInterface $logger;

    public function __construct(
        FabricColorSaver $saver,
        LoggerInterface $logger
    )
    {
        $this->saver = $saver;
        $this->logger = $logger;
        $this->logger->debug("Save Observer - Construct");
    }

    public function execute(Observer $observer): void
    {
        try {
            $controller = $observer->getEvent()->getControllerAction();
            $request = $controller->getRequest();

            // Produkt ID fra URL-parametrene (efter redirect)
            $productId = (int)$request->getParam('id');

            if (!$productId) {
                $this->logger->debug('FabricColors: No productId from request param');
                return;
            }

            // POST-data (kan være tom efter redirect)
            $post = $request->getPostValue();

            if (!isset($post['product']['options'])) {
                $this->logger->debug('FabricColors: No options in POST');
                return;
            }

            $options = $post['product']['options'];

            $this->logger->debug('FabricColors Save Triggered', [
                'product_id' => $productId,
                'options_count' => count($options)
            ]);

            $this->saver->save($productId, $options);

        } catch (\Throwable $e) {
            $this->logger->error('FabricColors Save Error: ' . $e->getMessage());
        }
    }
}