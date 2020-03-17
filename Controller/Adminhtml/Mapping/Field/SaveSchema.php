<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Field;

use Magento\{
    Backend\App\Action,
    Backend\App\Action\Context,
    Framework\View\Result\PageFactory,
    Framework\Stdlib\DateTime\DateTime,
    Store\Model\StoreManagerInterface
};
use Emarsys\Emarsys\{
    Helper\Data as EmarsysHelper,
    Model\ResourceModel\Field as EmarsysResourceModelField,
    Helper\Logs,
    Model\Api\Api as EmarsysModelApiApi,
    Model\ResourceModel\Customer as EmarsysResourceModelCustomer
};

/**
 * Class SaveSchema
 * @package Emarsys\Emarsys\Controller\Adminhtml\Mapping\Field
 */
class SaveSchema extends Action
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
     * @var EmarsysResourceModelField
     */
    protected $fieldResourceModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var EmarsysModelApiApi
     */
    protected $api;

    /**
     * @var EmarsysResourceModelCustomer
     */
    protected $customerResourceModel;

    /**
     * @var Logs
     */
    protected $logsHelper;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * SaveSchema constructor.
     *
     * @param Context $context
     * @param EmarsysHelper $emarsysHelper
     * @param EmarsysResourceModelField $fieldResourceModel
     * @param PageFactory $resultPageFactory
     * @param Logs $logsHelper
     * @param DateTime $date
     * @param StoreManagerInterface $storeManager
     * @param EmarsysModelApiApi $api
     * @param EmarsysResourceModelCustomer $customer
     */
    public function __construct(
        Context $context,
        EmarsysHelper $emarsysHelper,
        EmarsysResourceModelField $fieldResourceModel,
        PageFactory $resultPageFactory,
        Logs $logsHelper,
        DateTime $date,
        StoreManagerInterface $storeManager,
        EmarsysModelApiApi $api,
        EmarsysResourceModelCustomer $customer
    ) {
        ini_set('default_socket_timeout', 5000);
        parent::__construct($context);
        $this->emarsysHelper = $emarsysHelper;
        $this->fieldResourceModel = $fieldResourceModel;
        $this->resultPageFactory = $resultPageFactory;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->storeManager = $storeManager;
        $this->api = $api;
        $this->customerResourceModel = $customer;
        $this->session = $context->getSession();
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        /**
         * To Get the schema from Emarsys and add/update in magento mapping table
         */
        $storeId = $this->getRequest()->getParam('store');
        $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsiteByStoreId($storeId);
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $logsArray['job_code'] = 'Customer Filed Mapping';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Running Update Schema';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Automatic';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['website_id'] = $websiteId;
        $logsArray['store_id'] = $storeId;
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $schemaData = $this->getEmarsysOptionSchema($storeId, $websiteId);
            if (count($schemaData) > 0) {
                $this->fieldResourceModel->updateOptionSchema($schemaData, $storeId);
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logId = $this->logsHelper->manualLogs($logsArray);
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Update Schema';
                $logsArray['description'] = 'Updated Schema as ' . \Zend_Json::encode($schemaData);
                $logsArray['action'] = 'Update Schema Successful';
                $logsArray['message_type'] = 'Success';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['log_action'] = 'True';
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Update Schema Completed Successfully';
                $this->logsHelper->manualLogs($logsArray);
                $this->messageManager->addSuccessMessage('Customer-Field schema added/updated successfully');
            } else {
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logId = $this->logsHelper->manualLogs($logsArray);
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Update Schema';
                $logsArray['description'] = 'Failed to update Customer-Field Schema';
                $logsArray['action'] = 'Failed Update Schema';
                $logsArray['message_type'] = 'Success';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['log_action'] = 'fail';
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'Failed Update Schema';
                $this->logsHelper->manualLogs($logsArray);
                $this->messageManager->addErrorMessage('Failed to update Customer-Field Schema');
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Failed to update Customer-Field Schema');
            $this->emarsysHelper->addErrorLog(
                'Customer Field Update Schema',
                $e->getMessage(),
                $storeId,
                'SaveSchema(Field)'
            );
        }

        return $resultRedirect->setRefererOrBaseUrl();
    }

    /**
     * @param $storeId
     * @param $websiteId
     * @return array
     * @throws \Exception
     */
    public function getEmarsysOptionSchema($storeId, $websiteId)
    {
        $this->api->setWebsiteId($websiteId);
        $emarsysContactFields = $this->customerResourceModel->getEmarsysContactFields($storeId);
        $emarsysFieldOptions = [];
        foreach ($emarsysContactFields as $emarsysField) {
            if ($emarsysField['type'] == "singlechoice" || $emarsysField['type'] == "multichoice") {
                $response = $this->api->sendRequest('GET', 'field/' . $emarsysField['emarsys_field_id'] . '/choice');
                if (isset($response['body']['data']) && is_array($response['body']['data'])) {
                    foreach ($response['body']['data'] as $optionField) {
                        $emarsysFieldOptions[$emarsysField['emarsys_field_id']][] = $optionField;
                    }
                }
            }
        }

        return $emarsysFieldOptions;
    }
}
