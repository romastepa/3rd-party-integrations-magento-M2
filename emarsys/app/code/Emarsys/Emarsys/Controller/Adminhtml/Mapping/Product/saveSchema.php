<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class saveSchema extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Product
     */
    protected $productResourceModel;

    /**
     *
     * @param Context $context
     * @param \Emarsys\Emarsys\Model\ResourceModel\Product $productResourceModel
     * @param \Emarsys\Log\Model\Logs $emarsysLogs
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Model\ResourceModel\Product $productResourceModel,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Emarsys\Log\Helper\Logs $logHelper,
        \Emarsys\Log\Model\Logs $emarsysLogs,
        PageFactory $resultPageFactory
    ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->emarsysHelper = $emarsysHelper;
        $this->date = $date;
        $this->_storeManager = $storeManager;
        $this->emarsysLogs = $emarsysLogs;
        $this->logHelper = $logHelper;
        $this->productResourceModel = $productResourceModel;
    }

    public function execute()
    {
        if ($this->getRequest()->getParam('store')) {
            $storeId = $this->getRequest()->getParam('store');
        }else{
            $storeId = $this->emarsysHelper->getFirstStoreId();
        }
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        try {
            $productFields = array();
            $logsArray['job_code'] = 'Product Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Running Update Schema';
            $logsArray['description'] = 'Started Updating Product Mapping';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['website_id'] = $websiteId;
            $logsArray['store_id'] = $storeId;
            $logId = $this->logHelper->manualLogs($logsArray);
            $productFields = $this->productResourceModel->updateProductSchema($storeId);
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Update Product Mapping';
            $logsArray['action'] = 'Update Product Mapping Successful';
            $logsArray['message_type'] = 'Success';
            $logsArray['description'] = 'Product Mapping Updated as '.print_r($productFields,true);
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['log_action'] = 'True';
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Update Product Mapping Saved Successfully';
            $this->logHelper->logs($logsArray);
            $this->logHelper->manualLogs($logsArray);
            $this->messageManager->addSuccess("product schema added/updated successfully");
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(),$storeId,'SaveSchema(Product)');
        }
    }
}
