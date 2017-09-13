<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Order;

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
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */

    protected $session;

    protected $orderResourceModel;

    /**
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Order $orderResourceModel
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Emarsys\Log\Helper\Logs $logsHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Order $orderResourceModel
    ) {
    
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->storeManager = $storeManager;
        $this->orderResourceModel = $orderResourceModel;
        $this->session = $context->getSession();
    }

    /**
     * Save action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $session = $this->session->getData();
        if (isset($session['store'])) {
            $storeId = $session['store'];
        }
        if (!isset($session['store'])) {
            $storeId = 1;
        }
        try {
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
            $logsArray['job_code'] = 'Order Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Save Mapping';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['store_id'] = $storeId;
            $logsArray['website_id'] = $websiteId;
            $logId = $this->logsHelper->manualLogs($logsArray);
            $stringJSONData = json_decode(stripslashes($this->getRequest()->getParam('jsonstringdata')));
            $stringArrayData = (array)$stringJSONData;
            $data = $this->orderResourceModel->insertIntoMappingTableCustomValue($stringArrayData, $storeId);
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Saved Order Mapping Successfully';
            $logsArray['description'] = 'Save Entries as '.print_r($stringArrayData,true);
            $logsArray['action'] = 'Save Order Schema';
            $logsArray['message_type'] = 'Success';
            $logsArray['status'] = 'Success';
            $logsArray['log_action'] = 'Save Order Schema';
            $logsArray['messages'] = 'Save Order Schema Successful';
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $this->logsHelper->logs($logsArray);
            $this->logsHelper->manualLogsUpdate($logsArray);
            $this->messageManager->addSuccess('Order attributes mapped successfully');
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        } catch (\Exception $e) {
            if($logId){
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Save Mapping not Successful';
                $logsArray['description'] = $e->getMessage();
                $logsArray['action'] = 'Save Order Mapping';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'Save Order Mapping';
                $logsArray['messages'] = 'Save Order Mapping not Successful';
                $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $this->logsHelper->logs($logsArray);
                $this->logsHelper->manualLogsUpdate($logsArray);
            }
        }
    }
}
