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
     * @var \Emarsys\Emarsys\Model\EventFactory
     */
    protected $eventFactory;

    /**
     * 
     * @param Context $context
     * @param \Emarsys\Emarsys\Model\EventFactory $eventFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Event $eventResourceModel
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Emarsys\Log\Helper\Logs $logHelper
     * @param \Emarsys\Emarsys\Model\EmarsyseventmappingFactory $EmarsyseventmappingFactory
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Model\EventFactory $eventFactory,
        \Emarsys\Emarsys\Model\ResourceModel\Event $eventResourceModel,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Log\Helper\Logs $logHelper,
        \Emarsys\Emarsys\Model\EmarsyseventmappingFactory $EmarsyseventmappingFactory,
        PageFactory $resultPageFactory
    ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->eventResourceModel = $eventResourceModel;
        $this->storeManager = $storeManager;
        $this->EmarsyseventmappingFactory = $EmarsyseventmappingFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->date = $date;
        $this->eventFactory = $eventFactory;
        $this->logHelper = $logHelper;
        $this->_urlInterface = $context->getUrl();
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $session = $this->session;
        $storeId = $this->session->getStoreId();
        $gridSessionData = $this->session->getMappingGridData();
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $logsArray['job_code'] = 'Event Mapping';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'Save Event Mapping';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Automatic';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logId = $this->logHelper->manualLogs($logsArray);
        try{
        foreach ($gridSessionData as $key => $value) {
            $model = $this->EmarsyseventmappingFactory->create();
            $model->setId($key);
            $model->setStoreId($storeId);
            $model->setMagentoEventId($gridSessionData[$key]['magento_event_id']);
            $model->setEmarsysEventId($gridSessionData[$key]['emarsys_event_id']);
            $model->save();
        }
        $logsArray['id'] = $logId;
        $logsArray['emarsys_info'] = 'Save Event Mapping';
        $logsArray['description'] = 'Saved Event Mapping';
        $logsArray['action'] = 'Event Mapping';
        $logsArray['message_type'] = 'Success';
        $logsArray['log_action'] = 'True';
        $logsArray['website_id'] = $websiteId;
        $this->logHelper->logs($logsArray);
        $this->messageManager->addSuccess("Events mapped successfully");
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setUrl($this->_urlInterface->getUrl('emarsys_emarsys/mapping_event', ["store" => $storeId]));
        }catch (\Exception $e){
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Save Event Mapping';
            $logsArray['description'] = $e->getMessage();
            $logsArray['action'] = 'Event Mapping';
            $logsArray['message_type'] = 'Success';
            $logsArray['log_action'] = 'True';
            $logsArray['website_id'] = $websiteId;
        }
    }
}
