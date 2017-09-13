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

class Refreshplaceholders extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * 
     * @param Context $context
     * @param \Emarsys\Emarsys\Helper\Data $EmarsysHelper
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Helper\Data $EmarsysHelper,
        PageFactory $resultPageFactory
    ) {
    
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->EmarsysHelper = $EmarsysHelper;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {

        $placeholderData = $this->getRequest()->getParams();
        $this->EmarsysHelper->refreshPlaceholders($placeholderData['mapping_id'], $this->getRequest()->getParam('store'));
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setRefererOrBaseUrl();
    }
}
