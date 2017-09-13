<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Orderexport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var
     */
    protected $session;

    /**
     * @param Context $context
     * @param \Magento\Backend\Model\Session $session
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
    
        parent::__construct($context);
        $this->adminSession = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $data = $this->adminSession->getFormData(true);
        $page = $this->resultPageFactory->create();
        $page->getLayout()->getBlock("head");
        $page->addBreadcrumb(__('Log'), __('Bulk Order Export'));
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex10');
        $page->getConfig()->getTitle()->prepend(__('Bulk Order Export'));
        return $page;
    }
}
