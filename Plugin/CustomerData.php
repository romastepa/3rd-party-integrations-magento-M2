<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Plugin;

use Magento\Customer\CustomerData\Customer;
use Magento\Customer\Model\Session;

/**
 * Class CustomerData
 * @package Emarsys\Emarsys\Plugin
 */
class CustomerData extends Customer
{
    /**
     * CustomerData constructor.
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param OriginalCustomerData $subject
     * @param array $result
     *
     * @return array
     */
    public function afterGetSectionData(Customer $subject, $result)
    {
        $customerId = $subject->currentCustomer->getCustomerId();
        if ($customerId) {
            $customer = $subject->currentCustomer->getCustomer();
            $result['id'] = $customerId;
            $result['email'] = $customer->getEmail();
        } elseif ($this->session->getWebExtendCustomerEmail()) {
            $result['email'] = $this->session->getWebExtendCustomerEmail();
        }

        return $result;
    }
}
