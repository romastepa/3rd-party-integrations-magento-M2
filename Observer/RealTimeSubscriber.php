<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Magento\{
    Customer\Model\Session,
    Framework\Event\Observer,
    Framework\Event\ObserverInterface,
    Framework\App\Request\Http,
    Store\Model\StoreManagerInterface
};
use Emarsys\Emarsys\{
    Model\Api\Subscriber,
    Model\ResourceModel\Customer,
    Helper\Data as EmarsysDataHelper
};

/**
 * Class RealTimeSubscriber
 * @package Emarsys\Emarsys\Observer
 */
class RealTimeSubscriber implements ObserverInterface
{
    /**
     * @var Subscriber
     */
    protected $subscriberModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Customer
     */
    protected $customerResourceModel;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var EmarsysDataHelper
     */
    protected $emarsysHelper;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * RealTimeSubscriber constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Subscriber $subscriberModel
     * @param Customer $customerResourceModel
     * @param Http $request
     * @param EmarsysDataHelper $emarsysHelper
     * @param Session $customerSession
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Subscriber $subscriberModel,
        Customer $customerResourceModel,
        Http $request,
        EmarsysDataHelper $emarsysHelper,
        Session $customerSession
    ) {
        $this->subscriberModel = $subscriberModel;
        $this->storeManager = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->request = $request;
        $this->emarsysHelper = $emarsysHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param Observer $observer
     * @return bool|void
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $subscriber = $event->getSubscriber();
        $store = $this->storeManager->getStore($subscriber->getStoreId());

        if ($subscriber->getEmarsysNoExport() || $store->getConfig(EmarsysDataHelper::XPATH_EMARSYS_REALTIME_SYNC) != 1) {
            return true;
        }

        $subscriberId = $subscriber->getId();
        $storeId = $store->getStoreId();
        $websiteId = $store->getWebsiteId();
        $pageHandle = $this->request->getFullActionName();

        if (!$this->emarsysHelper->isEmarsysEnabled($websiteId)) {
            return;
        }

        $this->customerSession->setWebExtendCustomerEmail($subscriber->getSubscriberEmail());

        try {
            $frontendFlag = 1;
            $this->emarsysHelper->realtimeTimeBasedOptinSync($subscriber);
            $result = $this->subscriberModel->syncSubscriber($subscriberId, $storeId, $frontendFlag, $pageHandle);

            if ($result['apiResponseStatus'] == '200') {
                return true;
            }
        } catch (\Exception $e) {
            $this->emarsysHelper->syncFail($subscriberId, $websiteId, $storeId, 0, 2);
            $this->emarsysHelper->addErrorLog(
                $e->getMessage(),
                $storeId,
                'RealTimeSubscriber::execute'
            );
        }
        return false;
    }
}


