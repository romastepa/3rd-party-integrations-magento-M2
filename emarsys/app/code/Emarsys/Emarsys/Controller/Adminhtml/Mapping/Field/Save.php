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

class Save extends \Magento\Backend\App\Action
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
     * @var \Emarsys\Emarsys\Model\FieldFactory
     */
    protected $fieldFactory;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Field
     */
    protected $resourceModelField;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

   /**
    *
    * @param Context $context
    * @param \Emarsys\Emarsys\Model\FieldFactory $fieldFactory
    * @param \Emarsys\Emarsys\Model\ResourceModel\Field $resourceModelField
    * @param PageFactory $resultPageFactory
    * @param \Magento\Store\Model\StoreManagerInterface $storeManager
    */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Model\FieldFactory $fieldFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Field $resourceModelField,
        PageFactory $resultPageFactory,
        \Emarsys\Log\Helper\Logs $logHelper,
        \Emarsys\Log\Model\Logs $emarsysLogs,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->date = $date;
        $this->emarsysHelper = $emarsysHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->emarsysLogs = $emarsysLogs;
        $this->resourceModelField = $resourceModelField;
        $this->fieldFactory = $fieldFactory;
        $this->logHelper = $logHelper;
        $this->_storeManager = $storeManager;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (isset($session['store'])) {
            $storeId = $session['store'];
        }else{
            $storeId = $this->emarsysHelper->getFirstStoreId();
        }
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
            $model = $this->fieldFactory->create();
            $session = $this->session->getData();

            $gridSessionStoreId = '';
            $gridSessionData = [];
            if (isset($session['gridData'])) {
                $gridSessionData = $session['gridData'];
            }
            if (isset($gridSessionData) && $gridSessionData != '') {
                foreach ($gridSessionData as $magentoOptionId => $emarsysFieldOption) {
                    if ($emarsysFieldOption == '') {
                        continue;
                    }

                    $emarsysField = explode('-', $emarsysFieldOption);
                    $emarsysOptionId = $emarsysField[1];
                    $emarsysFieldId = $emarsysField[0];
                    $modelColl = $model->getCollection()
                        ->addFieldToFilter('magento_option_id', $magentoOptionId)
                        ->addFieldToFilter('store_id', $storeId);
                    $modelCollData = $modelColl->getData();

                    foreach ($modelCollData as $cols) {
                        $colsValue = $cols['id'];
                    }

                    if (isset($colsValue)) {
                        $model = $model->load($colsValue);
                    }

                    if (!empty($modelCollData)) {
                        foreach ($modelColl as $model) {
                            if (isset($emarsysOptionId)) {
                                $model->setEmarsysOptionId($emarsysOptionId);
                                $model->setEmarsysFieldId($emarsysFieldId);
                            }
                            $model->save();
                        }
                    } else {
                        $model = $this->fieldFactory->create();
                        $model->setEmarsysOptionId($emarsysOptionId);
                        $model->setEmarsysFieldId($emarsysFieldId);
                        $model->setMagentoOptionId($magentoOptionId);
                        $model->setStoreId($storeId);
                        $model->save();
                    }
                }
            }
            $logId = $this->logHelper->manualLogs($logsArray);
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Save Customer Filed Mapping';
            $logsArray['description'] = 'Save Customer Filed Mapping Successful';
            $logsArray['action'] = 'Save Customer Filed Mapping Successful';
            $logsArray['message_type'] = 'Success';
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['log_action'] = 'True';
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Save Customer Filed Mapping Successful';
            $this->logHelper->logs($logsArray);
            $this->logHelper->manualLogs($logsArray);
            $this->messageManager->addSuccess('Customer-Field attributes mapped successfully');
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(), $storeId, 'Save (Customer Filed)');
            $this->messageManager->addError('Error occurred while mapping Customer-Field');
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        }
    }
}
