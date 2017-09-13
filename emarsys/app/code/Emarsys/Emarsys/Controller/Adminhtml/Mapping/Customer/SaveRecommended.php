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
    protected $customerFactory;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer
     */
    protected $resourceModelCustomer;

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
     * @param \Emarsys\Emarsys\Model\CustomerFactory $customerFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $resourceModelCustomer
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Model\CustomerFactory $customerFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $resourceModelCustomer,
        PageFactory $resultPageFactory,
        \Emarsys\Log\Model\Logs $emarsysLogs,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Emarsys\Log\Helper\Logs $logHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->customerFactory = $customerFactory;
        $this->resourceModelCustomer = $resourceModelCustomer;
        $this->date = $date;
        $this->emarsysLogs = $emarsysLogs;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->emarsysLogs = $emarsysLogs;
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

            // Truncating the Mapping Table first
            $this->resourceModelCustomer->truncateMappingTable($storeId);

            // Here We need set the recommended attribute values
            $recommendedData = $this->resourceModelCustomer->getRecommendedCustomerAttribute($storeId);
            $emarsysCodes = array(
                        'first_name' => 'firstname',
                        'middle_name' => 'middlename',
                        'last_name' => 'lastname',
                        'email' => 'email',
                        'gender' => 'gender',
                        'birth_date' => 'dob'
                    );
            if (isset($recommendedData['magento'])) {
                foreach ($recommendedData['magento'] as $key => $code) {
                    if (isset($recommendedData['emarsys'][$key])) {
                        $model = $this->customerFactory->create();
                        $custMageId = $this->resourceModelCustomer->getCustAttIdByCode($emarsysCodes[$key],$storeId);
                        $model->setEmarsysContactField($recommendedData['emarsys'][$key]);
                        $model->setMagentoAttributeId($code);
                        $model->setMagentoCustomAttributeId($custMageId['id']);
                        $model->setStoreId($storeId);
                        $model->save();
                    }
                }
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Recommended Mapping';
                $logsArray['description'] = 'Saved Recommended Mapping as '.print_r($emarsysCodes,true);
                $logsArray['action'] = 'Update Schema Successful';
                $logsArray['message_type'] = 'Success';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['log_action'] = 'True';
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Update Schema Completed Successfully';
                $this->logHelper->logs($logsArray);
                $this->logHelper->manualLogs($logsArray);
                $this->messageManager->addSuccess("Recommended Customer attributes mapped successfully");
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setRefererOrBaseUrl();
            } else {
                $this->messageManager->addError("No Recommendations are added");
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setRefererOrBaseUrl();
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(),$storeId,'SaveSchema(Customer)');
            $this->messageManager->addError("Error occurred while mapping Customer attribute");
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        }
    }
}
