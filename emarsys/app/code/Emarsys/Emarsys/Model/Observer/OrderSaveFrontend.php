<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Model\Observer;

use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ObserverInterface;

class OrderSaveFrontend implements ObserverInterface
{
    private $logger;

    protected $customerFactory;

    protected $orderQueueModel;

    protected $_responseFactory;

    protected $_url;

    public function __construct(
        LoggerInterface $logger,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Model\OrderQueueFactory $orderQueueFactory,
        \Emarsys\Emarsys\Model\OrderExportStatusFactory $orderExportStatusFactory,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order $orderModel
    ) {
    
        $this->logger = $logger;
        $this->_storeManager = $storeManager;
        $this->orderQueueFactory = $orderQueueFactory;
        $this->customerFactory = $customerFactory;
        $this->_responseFactory = $responseFactory;
        $this->orderExportStatusFactory = $orderExportStatusFactory;
        $this->_url = $url;
        $this->productFactory = $productFactory;
        $this->orderFactory = $orderFactory;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_order = $orderModel;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderID = $observer->getEvent()->getOrderIds()[0];
        $order = $this->_order->load($orderID);
        $emailID = $order->getCustomerEmail();
        $checkoutNewsSub = '';
        if (isset($this->_checkoutSession->getData()['newsletter_sub_checkout'])) {
            $checkoutNewsSub = $this->_checkoutSession->getData()['newsletter_sub_checkout'];
        }
        if ($checkoutNewsSub) {
            $this->_subscriberFactory->create()->subscribe($emailID);
        }
        $orderExportStatusData = $this->orderExportStatusFactory->create()->getCollection()->addFieldToFilter('order_id', $observer->getEvent()->getOrderIds()[0]);
        $orderExported = false;
        if (empty($orderExportStatusData->getData())) {
            $orderStatus = $this->orderExportStatusFactory->create();
        } else {
            $orderStatusData = $orderExportStatusData->getData();
            $orderStatus = $this->orderExportStatusFactory->create()->load($orderStatusData[0]['id']);
            if ($orderStatus->getExported() == 1) {
                $orderExported = true;
            }
        }
        if ($orderExported == true) {
            return;
        }
        $order = $this->orderFactory->create()->load($observer->getEvent()->getOrderIds()[0]);
        $orderStatus->setOrderId($observer->getEvent()->getOrderIds()[0]);
        $orderStatus->setExported(0);
        $orderStatus->setStatusCode($order->getStatus());
        $orderStatus->save();
        $orderQueueData = $this->orderQueueFactory->create()->getCollection()->addFieldToFilter('entity_id', $observer->getEvent()->getOrderIds()[0]);
        if (empty($orderQueueData->getData())) {
            $orderQueue = $this->orderQueueFactory->create();
        } else {
            $orderData = $orderQueueData->getData();
            $orderQueue = $this->orderQueueFactory->create()->load($orderData[0]['id']);
        }

        $orderQueue->setEntityId($observer->getEvent()->getOrderIds()[0]);
        $orderQueue->setEntityTypeId(1);
        $orderQueue->setWebsiteId($order->getStore()->getWebsiteId());
        $orderQueue->setStoreId($order->getStoreId());
        $orderQueue->save();
    }
}
