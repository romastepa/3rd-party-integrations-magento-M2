<?php
/**
 *
 * Copyright © 2015 Yagendracommerce. All rights reserved.
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Installation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Checklist extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

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
        $this->emarsysHelper = $emarsysHelper;
        $this->resultPageFactory = $resultPageFactory;
    }
    /**
     * Check the permission to run it
     *
     * @return bool
     */
    /*  protected function _isAllowed()
     {
         return $this->_authorization->isAllowed('Magento_Cms::page');
     } */

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $store = $this->getRequest()->getParam('store');
        if (!$store) {
            $storeId = $this->emarsysHelper->getFirstStoreId();
            return $this->resultRedirectFactory->create()->setUrl($this->getUrl('*/*/checklist',array('store'=>$storeId)));
        }
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Emarsys Installation Checklist'));
        return $resultPage;
    }
}
