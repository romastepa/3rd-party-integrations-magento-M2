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
     * @param Context $context
     * @param \Emarsys\Emarsys\Helper\Event $eventHelper
     * @param \Emarsys\Emarsys\Helper\Data $emarsysHelper
     * @param \Emarsys\Emarsys\Model\ResourceModel\Event $eventResourceModel
     * @param PageFactory $resultPageFactory
     * @param \Emarsys\Log\Helper\Logs $logHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Helper\Event $eventHelper,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Event $eventResourceModel,
        PageFactory $resultPageFactory,
        \Emarsys\Log\Helper\Logs $logHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->eventResourceModel = $eventResourceModel;
        $this->eventHelper = $eventHelper;
        $this->logHelper = $logHelper;
        $this->date = $date;
        $this->_storeManager = $storeManager;
        $this->emarsysHelper = $emarsysHelper;
    }

    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store');
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        try{
        $logsArray['job_code'] = 'Event Mapping';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Running Update Schema';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Automatic';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
            if($this->emarsysHelper->isEmarsysEnabled($websiteId)=='false'){
                $logsArray['messages'] = 'Emarsys is not Enabled for this Store';
                $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $resultRedirect = $this->resultRedirectFactory->create();
                $logId = $this->logHelper->manualLogs($logsArray);
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Update Schema';
                $logsArray['description'] ='Update Schema was not Successful';
                $logsArray['action'] = 'Schame Updated';
                $logsArray['message_type'] = 'Emarsys was not Enabled';
                $logsArray['log_action'] = 'True';
                $logsArray['website_id'] = $websiteId;
                $this->logHelper->logs($logsArray);
                $this->messageManager->addError('Emarsys is not Enabled for this store');
                return $resultRedirect->setRefererOrBaseUrl();
            }
        $logId = $this->logHelper->manualLogs($logsArray);
        $this->emarsysHelper->importEvents($logId);
        $this->messageManager->addSuccess('Event schema added/updated successfully');
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setRefererOrBaseUrl();
        /**
         * To Get the schema from Emarsys and add/update in magento mapping table
         */
        }catch (\Exception $e){
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Update Schema';
            $logsArray['description'] = $e->getMessage();
            $logsArray['action'] = 'Update Schema not successful';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'True';
            $logsArray['website_id'] = $websiteId;
            $this->logHelper->logs($logsArray);
            $this->messageManager->addError('Error occurred while Updating Schema'.$e->getMessage());
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        }
    }
}
