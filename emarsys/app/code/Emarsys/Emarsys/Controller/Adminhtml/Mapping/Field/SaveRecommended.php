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

class SaveRecommended extends \Magento\Backend\App\Action
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
     * @var \Emarsys\Emarsys\Model\CustomerFactory
     */
    protected $fieldFactory;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Field
     */
    protected $resourceModelField;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param Context $context
     * @param \Magento\Backend\Model\Session $session
     * @param \Emarsys\Emarsys\Model\FieldFactory $fieldFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Field $resourceModelField
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Model\FieldFactory $fieldFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Field $resourceModelField,
        PageFactory $resultPageFactory,
        \Emarsys\Log\Helper\Logs $logHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Emarsys\Log\Model\Logs $emarsysLogs,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->date = $date;
        $this->emarsysLogs = $emarsysLogs;
        $this->fieldFactory = $fieldFactory;
        $this->resourceModelField = $resourceModelField;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->_storeManager = $storeManager;
        $this->logHelper = $logHelper;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store');
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        try {
            $logsArray['job_code'] = 'Customer Filed Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Running Recommended Mapping';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['website_id'] = $websiteId;
            $logsArray['store_id'] = $storeId;
            $this->resourceModelField->truncateMappingTable($storeId);
            $recommendedData = $this->resourceModelField->getRecommendedFieldAttribute($storeId);
            if (count($recommendedData)) {
                foreach ($recommendedData as $key => $code) {
                    if (isset($recommendedData)) {
                        $value = explode('-', $code);
                        $model = $this->fieldFactory->create();
                        $model->setEmarsysOptionId($value[0]);
                        $model->setEmarsysFieldId($value[1]);
                        $model->setMagentoOptionId($key);
                        $model->setStoreId($storeId);
                        $model->save();
                    }
                }
                $logId = $this->logHelper->manualLogs($logsArray);
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Recommended Mapping';
                $logsArray['description'] = 'Saved Recommended Mapping as '.print_r($recommendedData,true);
                $logsArray['action'] = 'Saved Recommended Mapping Successful';
                $logsArray['message_type'] = 'Success';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['log_action'] = 'True';
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Saved Recommended Mapping Successful';
                $this->logHelper->logs($logsArray);
                $this->logHelper->manualLogs($logsArray);
                $this->messageManager->addSuccess("Recommended Customer-Field attributes mapped successfully");
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setRefererOrBaseUrl();
            } else {
                $logId = $this->logHelper->manualLogs($logsArray);
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Recommended Mapping';
                $logsArray['description'] = 'No Recommended Mappings';
                $logsArray['action'] = 'Recommended Mapping Completed';
                $logsArray['message_type'] = 'Error';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['log_action'] = 'True';
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'Saved Recommended Mapping Completed';
                $this->logHelper->logs($logsArray);
                $this->logHelper->manualLogs($logsArray);
                $this->messageManager->addError("No Recommendations are added");
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setRefererOrBaseUrl();
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'SaveRecommended (Customer Filed)');
            $this->messageManager->addError("Error occurred while mapping Customer-Field");
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        }
    }
}
