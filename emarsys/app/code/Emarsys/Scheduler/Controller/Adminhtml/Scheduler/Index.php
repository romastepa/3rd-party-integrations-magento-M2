<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Schedular
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Scheduler\Controller\Adminhtml\Scheduler;

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
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $store = $this->getRequest()->getParam('store');
        if (!$store) {
            $storeId = $this->emarsysHelper->getFirstStoreId();
            return $this->resultRedirectFactory->create()->setUrl($this->getUrl('*/*',array('store'=>$storeId)));
        }

        $resultPage = $this->resultPageFactory->create();
        $this->_setActiveMenu('Emarsys_Emarsys::emarsys_emarsysadminindex10');
        $resultPage->addBreadcrumb(__('Logs'), __('Logs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Logs'));
        return $resultPage;
    }
}
