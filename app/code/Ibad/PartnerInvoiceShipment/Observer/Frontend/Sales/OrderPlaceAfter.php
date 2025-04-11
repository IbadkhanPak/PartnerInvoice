<?php
declare(strict_types=1);

namespace Ibad\PartnerInvoiceShipment\Observer\Frontend\Sales;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class OrderPlaceAfter implements ObserverInterface
{

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        if (isset($_COOKIE['partner'])) {
            $order->setData('partner_name', $_COOKIE['partner']);


        }
    }
}

