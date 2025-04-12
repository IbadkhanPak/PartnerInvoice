<?php
declare(strict_types=1);

namespace Ibad\PartnerInvoiceShipment\Observer\Frontend\Sales;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use  Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Shipment\ItemFactory;
use Magento\Framework\DB\TransactionFactory;


class OrderPlaceAfter implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var ShipmentFactory
     */
    protected $shipmentFactory;
    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var ItemFactory
     */
    protected $shipmentItemFactory;

    /**
     * @var TransactionFactory
     */
    protected $transaction;

    /**
     * @param LoggerInterface $logger
     * @param ShipmentFactory $shipmentFactory
     * @param ItemFactory $shipmentItemFactory
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        LoggerInterface             $logger,
        ShipmentFactory             $shipmentFactory,
        ItemFactory                 $shipmentItemFactory,
        InvoiceService              $invoiceService,
        TransactionFactory          $transactionFactory
    )
    {
        $this->logger = $logger;
        $this->shipmentFactory = $shipmentFactory;
        $this->shipmentItemFactory = $shipmentItemFactory;
        $this->transaction = $transactionFactory;
        $this->invoiceService = $invoiceService;
    }

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
            //set parter name in order
            $this->setPartnerName($_COOKIE['partner'], $order);

            $items = $order->getAllVisibleItems();
            $itemCount = count($items);
            $splitItems = $this->splitItems($itemCount,$items);

            foreach ($splitItems as $splitItem) {

                $shipment = $this->createSplitShipment($order,$splitItem);
                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);

                // Create Invoice
                $invoiceItems = $this->createSplitInvoice($splitItem);
                $invoice = $this->invoiceService->prepareInvoice($order, $invoiceItems);
                $invoice->register();
                $invoice->getOrder()->setIsInProcess(true);

                try {
                    // save transaction
                    $this->saveTransaction($shipment, $invoice);
                    $this->logger->debug('Shipment created successfully');
                } catch (Exception $e) {
                    $this->logger->error('Shipment creation failed: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @param $partner
     * @param $order
     * @return void
     */
    public function setPartnerName($partner, $order)
    {
        if (!empty($partner)) {
            $order->setData('partner_name', $_COOKIE['partner']);
            $this->logger->debug('Set Partner: ' . $_COOKIE['partner']);
        }
    }

    /**
     * @param $order
     * @param $chunk
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createSplitShipment($order,$chunk)
    {
        $shipment = $this->shipmentFactory->create($order);
        foreach ($chunk as $item) {
            $qtyToShip = $item->getQtyToShip();
            if ($qtyToShip > 0 && !$item->getIsVirtual()) {
                $shipmentItem = $this->shipmentItemFactory->create();
                $shipmentItem->setOrderItem($item);
                $shipmentItem->setQty($qtyToShip);
                $shipment->addItem($shipmentItem);
            }
        }
        return $shipment;
    }

    /**
     * @param $chunk
     * @return array
     */
    private function createSplitInvoice($chunk)
    {
        $invoiceItems = [];
        foreach ($chunk as $item) {
            $qtyToInvoice = $item->getQtyToInvoice();
            if ($qtyToInvoice > 0) {
                $invoiceItems[$item->getId()] = $qtyToInvoice;
            }
        }
        return $invoiceItems;
    }

    /**
     * @param $shipment
     * @param $invoice
     * @return void
     */
    private function saveTransaction($shipment, $invoice)
    {
        if($shipment && $invoice){
            $transaction = $this->transaction->create()
                ->addObject($shipment)
                ->addObject($invoice)
                ->addObject($shipment->getOrder());
            $transaction->save();
        }
    }

    /**
     * @param $itemCount
     * @param $items
     * @return array|string
     */
    private function splitItems($itemCount,$items)
    {
        $splitItems = "";
        if(!empty($items)) {
            $numGroups = $itemCount > 1 ? 2 : 1;
            $splitItems = array_chunk($items, (int)ceil($itemCount / $numGroups));
        }
        return $splitItems;
    }

}