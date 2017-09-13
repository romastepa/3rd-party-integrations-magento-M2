<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class SaveRow extends \Magento\Backend\App\Action
{

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
        $attributeValue = $requestParams['attributeValue'];
        $coulmnAttr = $requestParams['coulmnAttr'];
        $session = $this->session->getData();
        $gridSession = $session['gridData'];
        $gridSession[$attributeCode][$coulmnAttr] = $attributeValue;
        $this->session->setData('gridData', $gridSession);
    }
}
