<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Emarsys\Emarsys\Model\OrderQueueFactory as OrderQueueFactoryAlias;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class OrderSave implements ObserverInterface
{
    /**
     * @var OrderQueueFactoryAlias
     */
    private $orderQueueFactory;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * OrderSave constructor.
     *
     * @param OrderQueueFactoryAlias $orderQueueFactory
     * @param EmarsysHelper $emarsysHelper
     */
    public function __construct(
        OrderQueueFactoryAlias $orderQueueFactory,
        EmarsysHelper $emarsysHelper
    ) {
        $this->orderQueueFactory = $orderQueueFactory;
        $this->emarsysHelper = $emarsysHelper;
    }

    public function execute(Observer $observer)
    {
        $websiteId = $observer->getEvent()->getOrder()->getStore()->getWebsiteId();
        if (!$this->emarsysHelper->getCheckSmartInsight($websiteId)) {
            return true;
        }

        $orderQueue = $this->orderQueueFactory->create();
        $orderQueueData = $orderQueue->getCollection()
            ->addFieldToFilter('entity_id', $observer->getEvent()->getOrder()->getId())
            ->addFieldToFilter('entity_type_id', 1);

        if ($orderQueueData->getSize()) {
            $orderQueue = $orderQueueData->getFirstItem();
        }

        if ($observer->getEvent()->getOrder()->getState() == \Magento\Sales\Model\Order::STATE_CLOSED) {
            return true;
        }

        $orderQueue->setEntityId($observer->getEvent()->getOrder()->getId())
            ->setEntityTypeId(1)
            ->setWebsiteId($observer->getEvent()->getOrder()->getStore()->getWebsiteId())
            ->setStoreId($observer->getEvent()->getOrder()->getStoreId())
            ->save();
    }
}
