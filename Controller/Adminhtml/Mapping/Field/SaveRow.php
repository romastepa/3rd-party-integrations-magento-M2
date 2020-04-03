<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Field;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * Class SaveRow
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Field
 */
class SaveRow extends Action
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * SaveRow constructor.
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
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $requestParams = $this->getRequest()->getParams();
        $session = $this->session->getData();
        $gridSession = [];
        if (isset($session['gridData'])) {
            $gridSession = $session['gridData'];
            if (!is_array($gridSession)) {
                $gridSession = [];
            }
        }
        $optionId = (int)$requestParams['optionId'];
        $entityOptionId = $requestParams['emarsysOptionId'];
        $gridSession[$optionId] = $entityOptionId;
        $this->session->setData('gridData', $gridSession);
    }
}
