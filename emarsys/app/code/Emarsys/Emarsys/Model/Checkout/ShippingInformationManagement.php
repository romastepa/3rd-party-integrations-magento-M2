<?php

namespace Emarsys\Emarsys\Model\Checkout;

use Psr\Log\LoggerInterface;

class ShippingInformationManagement
{
    protected $quoteRepository;

    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,

        \Magento\Framework\App\Request\Http $request,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Emarsys\Emarsys\Model\Api\Subscriber $subscriberModel
    )
    {
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->_request = $request;
        $this->customerResourceModel = $customerResourceModel;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_storeManager = $storeManager;
        $this->subscriberModel = $subscriberModel;
    }

    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    )
    {
        $extAttributes = $addressInformation->getExtensionAttributes()->getSubscribe();
        $attributeData = explode("||", $extAttributes);

        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        $storeId = $this->_storeManager->getStore()->getStoreId();

        if ($attributeData[0] == 1) {
            $this->checkoutSession->setNewsletterSubCheckout(1);
        } else {
            $this->checkoutSession->setNewsletterSubCheckout(0);
        }
    }
}
