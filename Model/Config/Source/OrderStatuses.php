<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Config\Source;

use Emarsys\Emarsys\Model\Logs;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class OrderStatuses
 * @package Emarsys\Emarsys\Model\Config\Source
 */
class OrderStatuses
{
    /**
     * @var CollectionFactory
     */
    protected $statusCollectionFactory;

    /**
     * @var StoreManagerInterface
     */

    protected $storeManager;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @param Logs $emarsysLogs
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $statusCollectionFactory
     */
    public function __construct(
        Logs $emarsysLogs,
        StoreManagerInterface $storeManager,
        CollectionFactory $statusCollectionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->emarsysLogs = $emarsysLogs;
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
       $orderStatusesCollection = $this->statusCollectionFactory->create()->joinStates()
            ->addFieldToFilter('state', ['in' => ['closed', 'complete', 'processing']]);

        $orderStatusesArray = [];
        foreach ($orderStatusesCollection as $order) {
            $orderStatusesArray[] = ['value' => $order->getStatus(), 'label' => $order->getLabel()];
        }
        return $orderStatusesArray;
    }
}
