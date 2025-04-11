<?php
declare(strict_types=1);

namespace Ibad\PartnerInvoiceShipment\Observer\Frontend\Sales;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Psr\Log\LoggerInterface;
use  \Magento\Sales\Model\Order\ShipmentFactory;
use \Magento\Sales\Model\Service\ShipmentService;
use \Magento\Sales\Model\Service\InvoiceService;
use \Magento\Sales\Model\Order\Shipment\ItemFactory;
use \Magento\Sales\Api\ShipmentRepositoryInterface;
use \Magento\Framework\DB\Transaction;
use \Magento\Sales\Model\Convert\Order;
use tests\unit\Magento\FunctionalTestFramework\Util\Validation\NameValidationUtilTest;

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
     * @var
     */
    protected  $invoiceService;

    /**
     * @var ItemFactory
     */
    protected $shipmentItemFactory;

    /**
     * @var ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @var Transaction
     */
    protected $transaction;

    protected $convertOrder;
    /**
     * @param LoggerInterface $logger
     * @param ShipmentFactory $shipmentFactory
     * @param ItemFactory $shipmentItemFactory
     * @param ShipmentRepository $shipmentRepository
     */
    public function __construct(
        LoggerInterface $logger,
        ShipmentFactory $shipmentFactory,
        ItemFactory $shipmentItemFactory,
        ShipmentRepositoryInterface $shipmentRepository,
        Transaction $transaction,
        Order $convertOrder
    ){
        $this->logger = $logger;
        $this->shipmentFactory = $shipmentFactory;
        $this->shipmentItemFactory = $shipmentItemFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->transaction = $transaction;
        $this->convertOrder = $convertOrder;
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
            $this->setPartnerName($_COOKIE['partner'],$order);

            // Get all visible items from the order
            $items = $order->getAllVisibleItems();
            $itemCount = count($items);
            $numShipments = ($itemCount > 1) ? 2 : 1;
            $chunks = array_chunk($items, (int) ceil($itemCount / $numShipments));

            $this->logger->debug('Chunkssss '. print_r($chunks,true));

            foreach ($chunks as $chunk) {
                $this->logger->debug('Chunk '. print_r($chunk,true));
                $shipment = $this->shipmentFactory->create($order);
                foreach ($chunk as $item) {

                    $this->logger->debug('Shipment Qty To Ship: ' . $item->getQtyToShip());
                    $this->logger->debug('Shipment Item: ' . $item->getQtyOrdered());

                    $qtyShipped = $item->getQtyToShip();
                    if ($qtyShipped > 0 && !$item->getIsVirtual()) {
                        $shipmentItem = $this->shipmentItemFactory->create();
                        $shipmentItem->setOrderItem($item);
                        $shipmentItem->setQty($qtyShipped);
                        $shipment->addItem($shipmentItem);
                    }
                }

                $this->logger->debug('Shipment Data: ' . print_r($shipment->debug(), true));
                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);

                try {
                    $shipment->save();
                    $shipment->getOrder()->save();
                    $this->logger->debug('Shipment created');
                } catch (\Exception $e) {
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
    public function setPartnerName($partner,$order)
    {
        if(!empty($partner)){
            $order->setData('partner_name', $_COOKIE['partner']);
            $this->logger->debug('Set Partner: ' . $_COOKIE['partner']);
        }
    }
}

