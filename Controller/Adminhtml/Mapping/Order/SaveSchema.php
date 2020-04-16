<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Order;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\View\Result\PageFactory;
use Emarsys\Emarsys\Helper\Logs;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Model\ResourceModel\Order;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Zend_Json;

class SaveSchema extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var Order
     */
    protected $orderResourceModel;

    /**
     * SaveSchema constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Logs $logsHelper
     * @param DateTime $date
     * @param Order $orderResourceModel
     * @param StoreManagerInterface $storeManager
     * @param EmarsysHelper $emarsysHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Logs $logsHelper,
        DateTime $date,
        Order $orderResourceModel,
        StoreManagerInterface $storeManager,
        EmarsysHelper $emarsysHelper
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
     * @return Redirect
     * @throws Exception
     */
    public function execute()
    {
        try {
            $session = $this->session->getData();
            $resultRedirect = $this->resultRedirectFactory->create();
            $storeId = false;
            if (isset($session['store'])) {
                $storeId = $session['store'];
            }
            $storeId = $this->emarsysHelper->getFirstStoreIdOfWebsiteByStoreId($storeId);
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
            $errorStatus = true;

            //initial logging started
            $logsArray['job_code'] = 'Order Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Running Order Update Schema';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['store_id'] = $storeId;
            $logsArray['website_id'] = $websiteId;
            $logsArray['action'] = 'Update Order Schema';
            $logsArray['log_action'] = 'Update Order Schema';
            $logId = $this->logsHelper->manualLogs($logsArray);
            $logsArray['id'] = $logId;

            $data = $this->orderResourceModel->getSalesOrderColumnNames();

            $header = $this->emarsysHelper->getSalesOrderCsvDefaultHeader();
            foreach ($header as $column) {
                $manData[$column] = $column;
            }

            $this->orderResourceModel->deleteOrderAttributeMapping('customer', $storeId);
            $this->orderResourceModel->insertIntoMappingTableStaticData($manData, $storeId);
            $this->orderResourceModel->insertIntoMappingTable($data, $storeId);

            $errorStatus = false;
            $logsArray['emarsys_info'] = 'Update Schema Successful';
            $logsArray['description'] = 'Inserted Entries ' . Zend_Json::encode($data);
            $logsArray['message_type'] = 'Success';
            $this->logsHelper->manualLogs($logsArray);
            $this->messageManager->addSuccessMessage('Order Schema Updated Successfully.');
        } catch (Exception $e) {
            if ($logId) {
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Update Schema not Successful';
                $logsArray['description'] = $e->getMessage();
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->manualLogs($logsArray);
            }
            $this->messageManager->addErrorMessage(
                __(
                    'There was a problem while updating order schema. Please refer emarsys logs for more information. %1',
                    $e->getMessage()
                )
            );
        }

        if ($errorStatus) {
            $logsArray['messages'] = 'Error While Update Schema';
            $logsArray['status'] = 'error';
        } else {
            $logsArray['messages'] = 'Update Schema Successful';
            $logsArray['status'] = 'success';
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogs($logsArray);

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
