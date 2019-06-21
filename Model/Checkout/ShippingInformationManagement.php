<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model\Checkout;

use Emarsys\Emarsys\Model\Logs;
use Magento\{
    Checkout\Model\Session,
    Store\Model\StoreManagerInterface,
    Checkout\Model\ShippingInformationManagement as CheckoutShippingInformationManagement,
    Checkout\Api\Data\ShippingInformationInterface
};

/**
 * Class ShippingInformationManagement
 * @package Emarsys\Emarsys\Model\Checkout
 */
class ShippingInformationManagement
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * ShippingInformationManagement constructor.
     *
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Logs $emarsysLogs
     */
    public function __construct(
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Logs $emarsysLogs
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->emarsysLogs = $emarsysLogs;
    }

    /**
     * @param CheckoutShippingInformationManagement $subject
     * @param $cartId
     * @param ShippingInformationInterface $addressInformation
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSaveAddressInformation(
        CheckoutShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $storeId = $this->storeManager->getStore()->getStoreId();
        try {
            $extAttributes = $addressInformation->getExtensionAttributes();

            if ($extAttributes && is_object($extAttributes)) {
                $isCustomerSubscribed = $extAttributes->getSubscribe();
                $attributeData = explode("||", $isCustomerSubscribed);

                if ($attributeData[0] == 1) {
                    $this->checkoutSession->setNewsletterSubCheckout(1);
                } else {
                    $this->checkoutSession->setNewsletterSubCheckout(0);
                }
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                \Emarsys\Emarsys\Helper\Data::LOG_MESSAGE_SUBSCRIBER,
                $e->getMessage(),
                $storeId,
                'beforeSaveAddressInformation'
            );
        }
    }
}