<?php

namespace Ibad\PartnerInvoiceShipment\Plugin;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

class OrderGridCollectionPlugin
{
    public function beforeLoad(Collection $collection, $printQuery = false, $logQuery = false)
    {
        if (!$collection->isLoaded()) {
            $collection->getSelect()->joinLeft(
                ['so' => $collection->getTable('sales_order')],
                'main_table.entity_id = so.entity_id',
                ['partner_name']
            );
        }
        return [$printQuery, $logQuery];
    }
}
