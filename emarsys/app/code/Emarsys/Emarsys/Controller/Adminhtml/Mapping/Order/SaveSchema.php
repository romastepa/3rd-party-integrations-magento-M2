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

class SaveSchema extends \Magento\Backend\App\Action
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
        \Emarsys\Log\Helper\Logs $logsHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Emarsys\Emarsys\Model\ResourceModel\Order $orderResourceModel,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper
    ) {
    
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->logsHelper = $logsHelper;
        $this->orderResourceModel = $orderResourceModel;
        $this->session = $context->getSession();
        $this->date = $date;
        $this->storeManager = $storeManager;
        $this->emarsysHelper = $emarsysHelper;
    }

    /**
     * Save action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        try {
            $session = $this->session->getData();
            if (isset($session['store'])) {
                $storeId = $session['store'];
            }else{
                $storeId = $this->emarsysHelper->getFirstStoreId();
            }
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
            $logsArray['job_code'] = 'Order Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Running Update Schema';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['store_id'] = $storeId;
            $logsArray['website_id'] = $websiteId;
            $logId = $this->logsHelper->manualLogs($logsArray);
            $data = $this->orderResourceModel->getSalesOrderColumnNames();
            $manData['order'] = 'order';
            $manData['date'] = 'date';
            $manData['customer'] = 'customer';
            $manData['item'] = 'item';
            $manData['quantity'] = 'quantity';
            $manData['unit_price'] = 'unit_price';
            $manData['c_sales_amount'] = 'c_sales_amount';
            $this->orderResourceModel->insertIntoMappingTableStaticData($manData, $storeId);
            $this->orderResourceModel->insertIntoMappingTable($data, $storeId);
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Update Schema Successful';
            $logsArray['description'] = 'Inserted Entries '.print_r($data,true);
            $logsArray['action'] = 'Update Order Schema';
            $logsArray['message_type'] = 'Success';
            $logsArray['log_action'] = 'Update Order Schema';
            $logsArray['messages'] = 'Update Schema Successful';
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $this->logsHelper->logs($logsArray);
            $this->logsHelper->manualLogsUpdate($logsArray);
            $this->messageManager->addSuccess('Order Schema Updated Successfully.');
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        }catch (\Exception $e){
            if($logId){
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Update Schema not Successful';
            $logsArray['description'] = $e->getMessage();
            $logsArray['action'] = 'Update Order Schema';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'Update Order Schema';
            $logsArray['messages'] = 'Update Schema not Successful';
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $this->logsHelper->logs($logsArray);
            $this->logsHelper->manualLogsUpdate($logsArray);
            }
        }
    }
}
