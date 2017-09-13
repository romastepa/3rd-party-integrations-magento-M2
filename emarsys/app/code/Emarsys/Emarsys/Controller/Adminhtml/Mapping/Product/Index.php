<?php
/**
 * @category   emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Product;

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
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $store = $this->getRequest()->getParam('store');
        if (!$store) {
            $storeId = $this->emarsysHelper->getFirstStoreId();
            return $this->resultRedirectFactory->create()->setUrl($this->getUrl('*/*',array('store'=>$storeId)));
        }
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex5');
        $resultPage->addBreadcrumb(__('Emarsys - Product Mapping'), __('Emarsys - Product Mapping'));
        $resultPage->getConfig()->getTitle()->prepend(__('Emarsys - Product Mapping'));
        return $resultPage;
    }
}
