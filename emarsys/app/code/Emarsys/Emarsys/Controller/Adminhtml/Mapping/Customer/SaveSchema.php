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
     * @var \Emarsys\Emarsys\Helper\Customer
     */
    protected $customerHelper;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer
     */
    protected $customerResourceModel;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     *
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    protected  $attribute;
    /**
     *
     * @param Context $context
     * @param \Magento\Backend\Model\Session $session
     * @param \Emarsys\Emarsys\Helper\Customer $emarsysCustomerHelper
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
     * @param PageFactory $resultPageFactory
     * @param \Emarsys\Log\Model\Logs $emarsysLogs
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Helper\Customer $emarsysCustomerHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        PageFactory $resultPageFactory,
        \Emarsys\Log\Model\Logs $emarsysLogs,
        \Emarsys\Log\Helper\Logs $logHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
         \Magento\Eav\Model\Entity\Attribute $attribute
        ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->emarsysCustomerHelper = $emarsysCustomerHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->date = $date;
        $this->emarsysLogs = $emarsysLogs;
        $this->logHelper = $logHelper;
        $this->_storeManager = $storeManager;
        $this->attribute = $attribute;
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
        try {

            $logsArray['job_code'] = 'Customer Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Running Update Schema';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['website_id'] = $websiteId;
            $logsArray['store_id'] = $storeId;
            $logId = $this->logHelper->manualLogs($logsArray);
            $customerAttData = $this->attribute->getCollection()->addFieldToSelect('frontend_label')->addFieldToSelect('attribute_code')->addFieldToSelect('entity_type_id')->addFieldToFilter('entity_type_id', array('in'=>'1,2'))->getData();
            $this->customerResourceModel->insertCustomerMageAtts($customerAttData,$storeId);
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Update Schema';
            $logsArray['description'] = 'Updated Schema as '.print_r($customerAttData,true);
            $logsArray['action'] = 'Update Schema Successful';
            $logsArray['message_type'] = 'Success';
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['log_action'] = 'True';
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Update Schema Completed Successfully';
            $this->logHelper->logs($logsArray);
            $this->logHelper->manualLogs($logsArray);
            $schemaData = $this->emarsysCustomerHelper->getEmarsysCustomerSchema($storeId);
            if ($schemaData['data'] != '') {
                $this->customerResourceModel->updateCustomerSchema($schemaData, $storeId);
                $this->messageManager->addSuccess('Customer schema added/updated successfully');
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setRefererOrBaseUrl();
            } else {
                $this->messageManager->addError($schemaData['replyText']);
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setRefererOrBaseUrl();
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(),$storeId,'SaveSchema(Customer)');
        }
    }
}
