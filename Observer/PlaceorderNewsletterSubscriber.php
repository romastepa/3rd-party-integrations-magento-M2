<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Emarsys\Emarsys\Model\Logs;
use Magento\{
    Checkout\Model\Session,
    Framework\Event\Observer,
    Newsletter\Model\SubscriberFactory,
    Sales\Model\Order,
    Store\Model\StoreManagerInterface,
    Framework\Event\ObserverInterface
};

/**
 * Class PlaceorderNewsletterSubscriber
 * @package Emarsys\Emarsys\Observer
 */
class PlaceorderNewsletterSubscriber implements ObserverInterface
{
    protected $order;
    protected $emarsysLogs;
    protected $storeManager;
    protected $subscriberFactory;
    protected $checkoutSession;

    /**
     * PlaceorderNewsletterSubscriber constructor.
     * @param StoreManagerInterface $storeManager
     * @param Session $checkoutSession
     * @param Logs $emarsysLogs
     * @param SubscriberFactory $subscriberFactory
     * @param Order $orderModel
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Session $checkoutSession,
        Logs $emarsysLogs,
        SubscriberFactory $subscriberFactory,
        Order $orderModel
    ) {
        $this->order = $orderModel;
        $this->emarsysLogs = $emarsysLogs;
        $this->storeManager = $storeManager;
        $this->subscriberFactory = $subscriberFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $orderID = $observer->getEvent()->getOrderIds()[0];
            $order = $this->order->load($orderID);
            $emailID = $order->getCustomerEmail();
            $checkoutNewsSub = $this->checkoutSession->getData()['newsletter_sub_checkout'];
            if ($checkoutNewsSub) {
                $this->subscriberFactory->create()->subscribe($emailID);
            }
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getId();
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'PlaceorderNewsletterSubscriber');
        }
    }
}
