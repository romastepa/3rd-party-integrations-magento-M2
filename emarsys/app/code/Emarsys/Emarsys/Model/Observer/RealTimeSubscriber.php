<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Model\Observer;

use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ObserverInterface;

class RealTimeSubscriber implements ObserverInterface
{
    private $logger;

    protected $customerFactory;

    protected $customerResourceModel;

    protected $_responseFactory;

    protected $_url;

    public function __construct(
        LoggerInterface $logger,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Model\Api\Subscriber $subscriberModel,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Emarsys\Emarsys\Helper\Data $dataHelper,
        \Magento\Framework\UrlInterface $url
    ) {
    
        $this->logger = $logger;
        $this->subscriberModel = $subscriberModel;
        $this->_storeManager = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->customerFactory = $customerFactory;
        $this->_responseFactory = $responseFactory;
        $this->_request = $request;
        $this->_url = $url;
        $this->dataHelper = $dataHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $pageHandle = $this->_request->getFullActionName();
        $subscriberId = $observer->getEvent()->getSubscriber()->getId();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        $storeId = $this->_storeManager->getStore()->getStoreId();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        $realtimeStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/emarsys_emarsys/realtime_sync');
        if ($realtimeStatus == 1) {
            $frontendFlag = 1;
            $result = $this->subscriberModel->syncSubscriber($subscriberId, $storeId, $frontendFlag, $pageHandle, $websiteId);
            if ($result['optInStatus'] == 'singleOptIn' && $result['apiResponseStatus'] == '200') {
                return;
            }
        } else {
            $this->dataHelper->syncFail($subscriberId, $websiteId, $storeId, 0, 2);
        }
    }
}
