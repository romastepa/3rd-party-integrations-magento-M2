<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Plugin\Checkout;

use Magento\{
    Checkout\Model\Session,
    Checkout\Model\ShippingInformationManagement as SIM,
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
     * ShippingInformationManagement constructor.
     *
     * @param Session $checkoutSession
     */
    public function __construct(Session $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param SIM $subject
     * @param $cartId
     * @param ShippingInformationInterface $addressInformation
     */
    public function beforeSaveAddressInformation(
        SIM $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $extAttributes = $addressInformation->getShippingAddress()->getExtensionAttributes();
        if ($extAttributes) {
            $this->checkoutSession->setNewsletterSubCheckout(
                $extAttributes->getEmarsysSubscriber() ? 1 : 0
            );
        }
    }
}
