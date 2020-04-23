<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Emarsys\Emarsys\Model\OrderQueueFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CreditmemoSave implements ObserverInterface
{
    /**
     * @var OrderQueueFactory
     */
    protected $orderQueueModel;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * CreditmemoSave constructor.
     *
     * @param OrderQueueFactory $orderQueueFactory
     * @param EmarsysHelper $emarsysHelper
     */
    public function __construct(
        OrderQueueFactory $orderQueueFactory,
        EmarsysHelper $emarsysHelper
    ) {
        $this->orderQueueFactory = $orderQueueFactory;
        $this->emarsysHelper = $emarsysHelper;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getDataObject()->getOrder();
        $websiteId = $order->getStore()->getWebsiteId();
        if (!$this->emarsysHelper->getCheckSmartInsight($websiteId)) {
            return true;
        }

        $orderQueue = $this->orderQueueFactory->create();

        $orderQueueData = $orderQueue->getCollection()
            ->addFieldToFilter('entity_id', $order->getId())
            ->addFieldToFilter('entity_type_id', 2);

        if ($orderQueueData->getSize()) {
            $orderQueue = $orderQueueData->getFirstItem();
        }

        $orderQueue->setEntityId($order->getId())
            ->setEntityTypeId(2)
            ->setWebsiteId($websiteId)
            ->setStoreId($order->getStoreId())
            ->save();
    }
}
