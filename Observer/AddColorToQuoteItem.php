<?php
namespace Digidennis\FabricColors\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddColorToQuoteItem implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $quoteItem = $observer->getEvent()->getQuoteItem();
        $request   = $observer->getEvent()->getRequest();

        $buyRequest = $quoteItem->getBuyRequest();
        if (!$buyRequest) {
            return;
        }

        $options = $buyRequest->getData('options') ?: [];
        if (!is_array($options)) {
            return;
        }

        // Her kan du gemme fx option_type_id som "color_id"
        // i quote item options, så du kan slå farve op senere.
    }
}
