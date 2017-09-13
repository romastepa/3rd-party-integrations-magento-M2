<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Field;

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
        $session = $this->session->getData();
        if (isset($session['gridData'])) {
            $gridSession = $session['gridData'];
        }
        $optionId = $requestParams['optionId'];
        $entityOptionId = $requestParams['emarsysOptionId'];
        $gridSession[$optionId] = $entityOptionId;
        $this->session->setData('gridData', $gridSession);
    }
}
