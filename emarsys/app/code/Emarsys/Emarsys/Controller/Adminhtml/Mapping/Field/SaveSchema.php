<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Field;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class SaveSchema extends \Magento\Backend\App\Action
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
     * @var \Emarsys\Emarsys\Helper\Field
     */
    protected $fieldHelper;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Field
     */
    protected $fieldResourceModel;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     *
     * @param Context $context
     * @param \Emarsys\Emarsys\Helper\Field $fieldHelper
     * @param \Emarsys\Emarsys\Model\ResourceModel\Field $fieldResourceModel
     * @param PageFactory $resultPageFactory
     * @param \Emarsys\Log\Model\Logs $emarsysLogs
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Helper\Field $fieldHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Field $fieldResourceModel,
        PageFactory $resultPageFactory,
        \Emarsys\Log\Helper\Logs $logHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Emarsys\Log\Model\Logs $emarsysLogs,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->fieldHelper = $fieldHelper;
        $this->emarsysLogs = $emarsysLogs;
        $this->fieldResourceModel = $fieldResourceModel;
        $this->_storeManager = $storeManager;
        $this->date = $date;
        $this->emarsysLogs = $emarsysLogs;
        $this->logHelper = $logHelper;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /**
         * To Get the schema from Emarsys and add/update in magento mapping table
         */
        $storeId = $this->getRequest()->getParam('store');
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        $logsArray['job_code'] = 'Customer Filed Mapping';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Running Update Schema';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Automatic';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['website_id'] = $websiteId;
        $logsArray['store_id'] = $storeId;
        try {
            $schemaData = $this->fieldHelper->getEmarsysOptionSchema($storeId);
            if (count($schemaData) > 0) {
                $this->fieldResourceModel->updateOptionSchema($schemaData, $storeId);
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logId = $this->logHelper->manualLogs($logsArray);
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Update Schema';
                $logsArray['description'] = 'Updated Schema as '.print_r($schemaData,true);
                $logsArray['action'] = 'Update Schema Successful';
                $logsArray['message_type'] = 'Success';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['log_action'] = 'True';
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Update Schema Completed Successfully';
                $this->logHelper->logs($logsArray);
                $this->logHelper->manualLogs($logsArray);
                $this->messageManager->addSuccess('Customer-Field schema added/updated successfully');
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setRefererOrBaseUrl();
            } else {
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logId = $this->logHelper->manualLogs($logsArray);
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Update Schema';
                $logsArray['description'] = 'Failed to update Customer-Field Schema';
                $logsArray['action'] = 'Failed Update Schema';
                $logsArray['message_type'] = 'Success';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['log_action'] = 'True';
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Failed Update Schema';
                $this->logHelper->logs($logsArray);
                $this->logHelper->manualLogs($logsArray);
                $this->messageManager->addError('Failed to update Customer-Field Schema');
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setRefererOrBaseUrl();
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(),$storeId,'SaveSchema(Field)');
        }
    }
}
