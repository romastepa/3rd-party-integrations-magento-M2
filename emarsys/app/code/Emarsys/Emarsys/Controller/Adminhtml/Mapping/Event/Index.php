<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Event;

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
     * @param \Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory $CollectionFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Emarsysmagentoevents\CollectionFactory $CollectionFactory
    ) {
    
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->emarsysHelper = $emarsysHelper;
        $this->CollectionFactory = $CollectionFactory;
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
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex7');
        $resultPage->addBreadcrumb(__('Emarsys - Event Mapping'), __('Emarsys - Event Mapping'));
        $resultPage->getConfig()->getTitle()->prepend(__('Emarsys - Event Mapping'));
        return $resultPage;
    }
}
