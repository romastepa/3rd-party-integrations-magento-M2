<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class RealTimeAdminSubscriber
 * @package Emarsys\Emarsys\Observer
 */
class RealTimeAdminSubscriber implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer
     */
    protected $customerResourceModel;

    /**
     * RealTimeAdminSubscriber constructor.
     * @param LoggerInterface $logger
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Emarsys\Emarsys\Model\Api\Subscriber $subscriberModel
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Emarsys\Emarsys\Helper\Data $dataHelper
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
     */
    public function __construct(
        LoggerInterface $logger,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Model\Api\Subscriber $subscriberModel,
        \Magento\Framework\App\Request\Http $request,
        \Emarsys\Emarsys\Helper\Data $dataHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
    )
    {
        $this->logger = $logger;
        $this->subscriberModel = $subscriberModel;
        $this->_storeManager = $storeManager;
        $this->dataHelper = $dataHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->_request = $request;
        $this->customerFactory = $customerFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $pageHandle = $this->_request->getFullActionName();
        $subscriberId = $observer->getEvent()->getSubscriber()->getId();
        $storeId = $observer->getEvent()->getSubscriber()->getStoreId();

        /** @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore($storeId);
        if ($this->dataHelper->isEmarsysEnabled($store->getWebsiteId()) == 'false') {
            return;
        }

        $realtimeStatus = $store->getConfig(\Emarsys\Emarsys\Helper\Data::XPATH_EMARSYS_REALTIME_SYNC);
        if ($realtimeStatus == 1) {
            $frontendFlag = '';
            $this->subscriberModel->syncSubscriber($subscriberId, $storeId, $frontendFlag, $pageHandle, $store->getWebsiteId());
        } else {
            $this->dataHelper->syncFail($subscriberId, $store->getWebsiteId(), $storeId, 0, 2);
        }
    }
}