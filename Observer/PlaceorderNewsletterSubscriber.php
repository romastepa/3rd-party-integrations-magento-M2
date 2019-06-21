<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
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
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * PlaceorderNewsletterSubscriber constructor.
     * @param StoreManagerInterface $storeManager
     * @param Session $checkoutSession
     * @param EmarsysHelper $emarsysHelper
     * @param SubscriberFactory $subscriberFactory
     * @param Order $orderModel
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Session $checkoutSession,
        EmarsysHelper $emarsysHelper,
        SubscriberFactory $subscriberFactory,
        Order $orderModel
    ) {
        $this->order = $orderModel;
        $this->emarsysHelper = $emarsysHelper;
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
            $checkoutNewsSub = $this->checkoutSession->getData()['newsletter_sub_checkout'];
            if ($checkoutNewsSub) {
                $this->subscriberFactory->create()->subscribe($order->getCustomerEmail());
            }
        } catch (\Exception $e) {
            $storeId = $this->storeManager->getId();
            $this->emarsysHelper->addErrorLog(
                EmarsysHelper::LOG_MESSAGE_SUBSCRIBER,
                $e->getMessage(),
                $storeId,
                'PlaceorderNewsletterSubscriber'
            );
        }
    }
}
