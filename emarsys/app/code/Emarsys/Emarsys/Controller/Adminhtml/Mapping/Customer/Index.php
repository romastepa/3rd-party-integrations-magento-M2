<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Customer;

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
     * 
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Emarsys\Emarsys\Helper\Data $emarsysHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper
    ) {
    
        parent::__construct($context);
        $this->emarsysHelper = $emarsysHelper;
        $this->resultPageFactory = $resultPageFactory;
    }

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
            return $this->resultRedirectFactory->create()->setUrl($this->getUrl('*/*',array('store'=>$storeId)));
        }
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_customer_mapping');
        $resultPage->addBreadcrumb(__('Emarsys - Customer Mapping'), __('Emarsys - Customer Mapping'));
        $resultPage->getConfig()->getTitle()->prepend(__('Emarsys - Customer Mapping'));
        return $resultPage;
    }
}
