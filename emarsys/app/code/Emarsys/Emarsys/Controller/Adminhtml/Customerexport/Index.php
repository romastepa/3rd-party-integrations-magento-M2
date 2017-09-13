<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Customerexport;

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
     * 
     * @param Context $context
     * @param \Emarsys\Emarsys\Helper\Data $emarsysHelper
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper,
        PageFactory $resultPageFactory
    ) {
    
        parent::__construct($context);
        $this->adminSession = $context->getSession();
        $this->emarsysHelper = $emarsysHelper;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $store = $this->getRequest()->getParam('store');
        if (!$store) {
            $storeId = $this->emarsysHelper->getFirstStoreId();
            return $this->resultRedirectFactory->create()->setUrl($this->getUrl('*/*',array('store'=>$storeId)));
        }
        $data = $this->adminSession->getFormData(true);
        $page = $this->resultPageFactory->create();
        $page->getLayout()->getBlock("head");
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex9');
        $page->addBreadcrumb(__('Log'), __('Bulk Customer Export'));
        $page->getConfig()->getTitle()->prepend(__('Bulk Customer Export'));
        return $page;
    }
}
