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
     * @var \Emarsys\Emarsys\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer
     */
    protected $resourceModelCustomer;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

   /**
    *
    * @param Context $context
    * @param \Emarsys\Emarsys\Model\CustomerFactory $customerFactory
    * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $resourceModelCustomer
    * @param PageFactory $resultPageFactory
    * @param \Magento\Store\Model\StoreManagerInterface $storeManager
    */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Model\CustomerFactory $customerFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $resourceModelCustomer,
        \Emarsys\Emarsys\Helper\Data $emsrsysHelper,
        \Emarsys\Log\Helper\Logs $logHelper,
        \Emarsys\Log\Model\Logs $emarsysLogs,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        PageFactory $resultPageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager

    )
    {
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->emarsysLogs = $emarsysLogs;
        $this->emsrsysHelper = $emsrsysHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->resourceModelCustomer = $resourceModelCustomer;
        $this->customerFactory = $customerFactory;
        $this->logHelper = $logHelper;
        $this->date = $date;
        $this->_storeManager = $storeManager;

    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $session = $this->session->getData();
        if (isset($session['store'])) {
            $storeId = $session['store'];
        }else{
            $storeId = $this->emsrsysHelper->getFirstStoreId();
        }
        try {
            $savedFields = array();
            $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
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
            $stringJSONData = json_decode(stripslashes($this->getRequest()->getParam('jsonstringdata')));
            $stringArrayData = (array)$stringJSONData;
            foreach ($stringArrayData as $key=>$value)
            {
                $customId = $this->resourceModelCustomer->getCustAttIdByCode($key,$storeId);
                $attData = $this->customerFactory->create()->getCollection()->addFieldToFilter('store_id', array('eq'=>$storeId))->addFieldToFilter('magento_custom_attribute_id',array('eq'=>$customId['id']));
                if (is_array($attData->getData()) && !empty($attData->getData()) )
                {
                    $attDataAll = $attData->getData();
                    $attModel = $this->customerFactory->create()->load($attDataAll[0]['id']);
                     if($value == ' '){
                         $savedFields[] = $value;
                        $attModel->setData('emarsys_contact_field',NULL);
                     }else {
                         $savedFields[] = $value;
                         $attModel->setData('emarsys_contact_field', $value);
                     }
                    $attModel->setData('magento_custom_attribute_id',$customId['id']);
                    $attModel->setData('store_id',$storeId);
                    $attModel->save();
                }
                else
                {
                    $attModel = $this->customerFactory->create();
                     if($value == ' '){
                         $savedFields[] = $value;
                        $attModel->setData('emarsys_contact_field',NULL);

                     }else {
                         $savedFields[] = $value;
                         $attModel->setData('emarsys_contact_field', $value);
                     }
                    $attModel->setData('magento_custom_attribute_id',$customId['id']);
                    $attModel->setData('store_id',$storeId);
                    $attModel->save();
                }
            }
            if($savedFields){
                $logsArray['description'] = 'Saved Fields Id(s) '.print_r(implode(",",$savedFields),true);
            }else{
                $logsArray['description'] = 'Customer Mapping Saved';
            }
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Save Customer Mapping';
            $logsArray['action'] = 'Save Customer Mapping Successful';
            $logsArray['message_type'] = 'Success';
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['log_action'] = 'True';
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Save Customer Mapping Saved Successfully';
            $this->logHelper->logs($logsArray);
            $this->logHelper->manualLogs($logsArray);
            $this->messageManager->addSuccess('Customer attributes mapped successfully');
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(),$storeId,'SaveSchema(Customer)');
            $this->messageManager->addError('("Error occurred while mapping Customer attribute');
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        }
    }
}
