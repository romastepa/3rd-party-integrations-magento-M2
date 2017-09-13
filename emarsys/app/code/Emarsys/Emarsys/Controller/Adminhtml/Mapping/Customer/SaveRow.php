<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class SaveRow extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * 
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $requestParams = $this->getRequest()->getParams();
        $attributeCode = $requestParams['attribute'];
        $entityTypeId = $requestParams['entityTypeId'];
        $attributeValue = $requestParams['attributeValue'];
        $coulmnAttr = $requestParams['coulmnAttr'];
        $session = $this->session->getData();
        if (isset($session['gridData'])) {
            $gridSession = $session['gridData'];
        }
        if ($entityTypeId == 2) {
            $gridSession['BILLADD_' . $attributeCode][$coulmnAttr] = $attributeValue;
        } else {
            $gridSession[$attributeCode][$coulmnAttr] = $attributeValue;
        }
        $gridSession[$attributeCode][$coulmnAttr] = $attributeValue;
        $this->session->setData('gridData', $gridSession);
    }
}
