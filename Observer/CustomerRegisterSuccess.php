<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Observer;

use Magento\{
    Framework\Event\Observer,
    Framework\Event\ObserverInterface,
    Customer\Model\Session,
    Framework\Exception\NoSuchEntityException
};

/**
 * Class CustomerRegisterSuccess
 * @package Emarsys\Emarsys\Observer
 */
class CustomerRegisterSuccess implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * CustomerRegisterSuccess constructor.
     * @param Session $customerSession
     */
    public function __construct(
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * @param Observer $observer
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $this->customerSession->setWebExtendCustomerEmail($customer->getEmail());
    }
}
