<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Psr\Log\LoggerInterface;
use Magento\{
    Customer\Model\CustomerFactory,
    Framework\Event\Observer,
    Framework\Event\ObserverInterface,
    Framework\Registry,
    Newsletter\Model\Subscriber,
    Store\Model\StoreManagerInterface
};
use Emarsys\Emarsys\{
    Model\Api\Contact,
    Model\ResourceModel\Customer,
    Model\Logs,
    Helper\Data as EmarsysDataHelper
};

/**
 * Class RealTimeCustomer
 * @package Emarsys\Emarsys\Observer
 */
class RealTimeCustomer implements ObserverInterface
{
    /**
     * @var EmarsysDataHelper
     */
    protected $emarsysHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Contact
     */
    private $contactModel;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Customer
     */
    private $customerResourceModel;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var Subscriber
     */
    protected $subscriber;

    /**
     * RealTimeCustomer constructor.
     * @param LoggerInterface $logger
     * @param CustomerFactory $customerFactory
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param Contact $contactModel
     * @param EmarsysDataHelper $emarsysHelper
     * @param Customer $customerResourceModel
     * @param Logs $emarsysLogs
     * @param Subscriber $subscriber
     */
    public function __construct(
        LoggerInterface $logger,
        CustomerFactory $customerFactory,
        Registry $registry,
        StoreManagerInterface $storeManager,
        Contact $contactModel,
        EmarsysDataHelper $emarsysHelper,
        Customer $customerResourceModel,
        Logs $emarsysLogs,
        Subscriber $subscriber
    ) {
        $this->emarsysHelper = $emarsysHelper;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->contactModel = $contactModel;
        $this->storeManager = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->customerFactory = $customerFactory;
        $this->emarsysLogs = $emarsysLogs;
        $this->subscriber = $subscriber;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var \Magento\Customer\Model\Customer $customer */
            $customer = $observer->getEvent()->getCustomer();
            $storeId = $customer->getStoreId();
            $store = $this->storeManager->getStore($storeId);
            $websiteId = $customer->getWebsiteId();

            if (!$this->emarsysHelper->isEmarsysEnabled($websiteId)) {
                return;
            }

            $subscriberId = 0;
            $isNewCustomer = $customer->getOrigData('NewCustomerCheck');
            if ($isNewCustomer) {
                $checkSubscriber = $this->subscriber->loadByEmail($customer->getEmail());
                $subscriberId = $checkSubscriber->getId();
            }

            $customerId = $customer->getId();
            if ($store->getConfig(EmarsysDataHelper::XPATH_EMARSYS_REALTIME_SYNC) == 1) {
                $customerVar = 'create_customer_variable_' . $customerId;
                if ($this->registry->registry($customerVar) == 'created') {
                    return;
                }
                $this->contactModel->syncContact($customer, $websiteId, $storeId, 0, false, $subscriberId);
                $this->registry->register($customerVar, 'created');
            } else {
                $this->emarsysHelper->syncFail($customerId, $websiteId, $storeId, 0, 1);
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'RealTimeCustomer::execute()'
            );
        }
    }
}
