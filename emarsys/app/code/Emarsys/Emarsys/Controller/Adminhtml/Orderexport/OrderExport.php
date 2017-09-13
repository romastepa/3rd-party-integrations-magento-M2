<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Orderexport;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\Timezone as TimeZone;

/**
 * Class Index
 */
class OrderExport extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $salesOrderCollectionFactory;
    /**
     * @var
     */
    protected $orderResourceModel;
    /**
     * @var
     */
    protected $customerResourceModel;

    /**
     * @var
     */
    protected $messageManager;
    /**
     * @var
     */
    protected $priceHelper;
    /**
     * @var
     */
    protected $_timezoneInterface;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Customer\Model\CustomerFactory $customer
     * @param DateTime $date
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $salesOrderCollectionFactory,
        DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Emarsys\Log\Helper\Logs $logsHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Order $orderResourceModel,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        TimeZone $timezone,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\Order\CreditmemoRepository $creditmemoRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
         \Emarsys\Emarsys\Helper\Data $emarsysDataHelper
    ) {
        $this->salesOrderCollectionFactory = $salesOrderCollectionFactory;
        $this->storeManager = $storeManager;
        $this->messageManager = $context->getMessageManager();
        $this->priceHelper = $priceHelper;
        $this->orderResourceModel = $orderResourceModel;
        $this->customerResourceModel = $customerResourceModel;
        $this->logsHelper = $logsHelper;
        $this->timezone = $timezone;
        $this->date = $date;
        $this->request = $request;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->_timezoneInterface =$timezoneInterface;
        $this->_emarsysDataHelper = $emarsysDataHelper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $creditmemoCollection = $this->creditmemoRepository->create()->getCollection();

        $data = $this->request->getParams();
        $scope = 'websites';
        $scopeId = $data['storeId'];
        $resultRedirect = $this->resultRedirectFactory->create();

        if (isset($data['fromDate']) && $data['fromDate'] != '')
        {
            $toTimezone = $this->timezone->getDefaultTimezone();
            $fromDate = $this->timezone->date($data['fromDate'])
                ->setTimezone(new \DateTimeZone($toTimezone))
                ->format('Y-m-d H:i:s');
            $magentoTime = $this->date->date('Y-m-d H:i:s');
            $currentTime = new \DateTime($magentoTime);
            $currentTime->format('Y-m-d H:i:s');
            $datetime2 = new \DateTime($fromDate);
            $interval = $currentTime->diff($datetime2);
            if ($interval->y > 2 || ($interval->y == 2 && $interval->m >= 1) || ($interval->y == 2 && $interval->d >= 1))
            {
                $this->messageManager->addError("The timeframe cannot be more than 2 years");
                $url = $this->getUrl("emarsys_emarsys/orderexport/index/store/$scopeId");
                return $resultRedirect->setPath($url);
            }
        }
        $websiteId = $this->storeManager->getStore($scopeId)->getWebsiteId();
        $storeCode = $this->storeManager->getStore($scopeId)->getCode();

        $logsArray['job_code'] = 'order';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'bulk order export started';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $scopeId;
        $logsArray['website_id'] = $websiteId;
        $logId = $this->logsHelper->manualLogs($logsArray);

        $hostname = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/hostname',$scope,$websiteId);
        $port = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/port',$scope,$websiteId);
        $username = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/username',$scope,$websiteId);
        $password = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/ftp_password',$scope,$websiteId);
        $bulkDir = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/ftp_bulk_export_dir',$scope,$websiteId);
        $ftpSsl = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/useftp_overssl',$scope,$websiteId);
        $passiveMode = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/usepassive_mode',$scope,$websiteId);

        if ($hostname != '' && $port != '' && $username != '' && $password != '') {
            $errorStatus = 0;
        } else {
            $errorStatus = 1;
        }
        if ($errorStatus != 1) {
            if ($ftpSsl == 1) {
                $ftpConnection = @ftp_ssl_connect($hostname, $port);
            } else {
                $ftpConnection = @ftp_connect($hostname, $port);
            }
            $ftpLogin = @ftp_login($ftpConnection, $username, $password);

            if ($ftpLogin) {
                $heading = array('order','date', 'customer', 'item', 'unit_price', 'c_sales_amount','quantity');
                $emasysFields = $this->orderResourceModel->getEmarsysOrderFields();
                foreach($emasysFields as $field)
                {
                    if ($field['emarsys_order_field'] != '')
                        $heading[] = $field['emarsys_order_field'];
                }

                $localFilePath = BP."/var";
                $outputFile = "sales_items_". $this->date->date('YmdHis', time())."_".$storeCode.".csv";
                $filePath = $localFilePath."/".$outputFile;
                $handle = fopen($filePath, 'w');
                fputcsv($handle, $heading);

                $orderStatuse = $this->customerResourceModel->getDataFromCoreConfig('smart_insight/smart_insight/orderexportforstatus',$scope,$websiteId);
                $orderStatuses = explode(',',$orderStatuse);
                $orderCollection = array();
                $guestOrderExportStatus = $this->customerResourceModel->getDataFromCoreConfig('smart_insight/smart_insight/exportguest_checkoutorders',$scope,$websiteId);
                $emailAsIdentifierStatus = $this->customerResourceModel->getDataFromCoreConfig('smart_insight/smart_insight/exportusing_emailidentifier',$scope,$websiteId);

                if (isset($data['fromDate']) && isset($data['toDate']) && $data['fromDate'] != '' && $data['toDate'] != '') {

                    $toTimezone = $this->timezone->getDefaultTimezone();
                    $fromDate = $this->timezone->date($data['fromDate'])
                        ->setTimezone(new \DateTimeZone($toTimezone))
                        ->format('Y-m-d H:i:s');

                    $toDate = $this->timezone->date($data['toDate'])
                        ->setTimezone(new \DateTimeZone($toTimezone))
                        ->format('Y-m-d H:i:s');

                    if ($orderStatuses != '') {
                        $orderCollection = $this->salesOrderCollectionFactory->create()
                            ->getCollection()
                            ->addFieldToFilter('created_at', array(
                                'from' => $fromDate,
                                'to' => $toDate,
                                'date' => true,
                            ))
                            ->addFieldToFilter('store_id', array('eq' => $scopeId))
                            ->addFieldToFilter('status', array('in' => $orderStatuses));
                        $creditmemoCollection = $this->creditmemoRepository->create()->getCollection()->addFieldToFilter('created_at', array(
                                'from' => $fromDate,
                                'to' => $toDate,
                                'date' => true,
                            ))->addFieldToFilter('store_id', array('eq' => $scopeId));
                    }
                } else if (isset($data['fromDate']) && isset($data['toDate']) && $data['fromDate'] == '' && $data['toDate'] == '') {
                    if ($orderStatuses != '') {
                        $orderCollection = $this->salesOrderCollectionFactory->create()
                            ->getCollection()
                            ->addAttributeToFilter('status', array('in' => $orderStatuses))->addFieldToFilter('store_id', array('eq' => $scopeId));
                    }
                    $creditmemoCollection = $this->creditmemoRepository->create()->getCollection()->addFieldToFilter('store_id', array('eq' => $scopeId));
                }

                foreach($orderCollection as $order)
                {

                    $orderId = $order->getRealOrderId();
                    $orderEntityId = $order->getId();
                    $createdDate = $order->getCreatedAt();
                    $customerEmail = $order->getCustomerEmail();
                    foreach ($order->getAllVisibleItems() as $item)
                    {
                        $values = array();
                        $values[] = $orderId;
                        $date = new \DateTime($createdDate);
                        $createdDate = $date->format('Y-m-d');
                        $values[] = $createdDate;
                        if ($customerEmail != '') {
                            $values[] = $customerEmail;
                        } else {
                            $values[] = '';
                        }
                        $values[] = $item->getProduct()->getSku();
                        $unitPrice = $item->getPriceInclTax();        //Unit Prices
                        if ($unitPrice != '') {
                            $values[] = number_format((float)$unitPrice, 2, '.', '') ;
                        } else {
                            $values[] = '';
                        }
                        $cSalesAmount = $item->getRowTotalInclTax() - $item->getDiscountAmount();    //cSalesAmount
                        if ($cSalesAmount != '') {
                            $values[] = $cSalesAmount;
                        } else {
                            $values[] = '';
                        }
                        $values[] = (int)$item->getQtyInvoiced();

                        foreach($emasysFields as $field)
                        {
                            if ($field['emarsys_order_field'] != '') {

                                $orderExpValues = $this->orderResourceModel->getOrderColValue($field['emarsys_order_field'], $orderEntityId);
                               

                               if(isset($orderExpValues['created_at']))
                                {
                                   $createdAt = $this->_emarsysDataHelper->getDateTimeInLocalTimezone($orderExpValues['created_at']);
                                   $values[] = $createdAt;
                                }
                                else if(isset($orderExpValues['updated_at']))
                                {
                                   $updatedAt = $this->_emarsysDataHelper->getDateTimeInLocalTimezone($orderExpValues['updated_at']);
                                   $values[] = $updatedAt;
                                }
                                else
                                {
                                    $values[] = $orderExpValues['magento_column_value'];
                                }
                            }
                        }

                        if(($guestOrderExportStatus == 0 || $emailAsIdentifierStatus == 0) && $order->getCustomerIsGuest() == 1){

                        } else {
                            fputcsv($handle, $values);
                        }
                    }
                }

                foreach($creditmemoCollection as $creditmemo)
                {
                    $creditMemoOrder = $this->salesOrderCollectionFactory->create()->load($creditmemo->getOrderId());
                    $orderId = $creditmemo->getOrder()->getIncrementId();
                    $orderEntityId = $creditmemo->getOrder()->getId();
                    $createdDate = $creditmemo->getCreatedAt();
                    $customerEmail = $creditMemoOrder->getCustomerEmail();
                    foreach ($creditMemoOrder->getAllVisibleItems() as $item)
                    {
                        $values = array();
                        $values[] = $orderId;
                        $date = new \DateTime($createdDate);
                        $createdDate = $date->format('Y-m-d');
                        $values[] = $createdDate;
                        if ($customerEmail != '') {
                            $values[] = $customerEmail;
                        } else {
                            $values[] = '';
                        }
                        $values[] = $item->getProduct()->getSku();
                        $unitPrice = $item->getPriceInclTax();        //Unit Prices
                        if ($unitPrice != '') {
                            $values[] = number_format((float)$unitPrice, 2, '.', '');
                        } else {
                            $values[] = '';
                        }
                        $cSalesAmount = $item->getRowTotalInclTax() - $item->getDiscountAmount();    //cSalesAmount
                        if ($cSalesAmount != '') {
                            $values[] = "-".$cSalesAmount;
                        } else {
                            $values[] = '';
                        }
                        $values[] = (int)$item->getQtyInvoiced();

                        foreach($emasysFields as $field)
                        {
                            if ($field['emarsys_order_field'] != '') {
                                $orderExpValues = $this->orderResourceModel->getOrderColValue($field['emarsys_order_field'], $orderEntityId);
                                if(isset($orderExpValues['created_at']))
                                {
                                   $createdAt = $this->_emarsysDataHelper->getDateTimeInLocalTimezone($orderExpValues['created_at']);
                                   $values[] = $createdAt;
                                }
                                else if(isset($orderExpValues['updated_at']))
                                {
                                   $updatedAt = $this->_emarsysDataHelper->getDateTimeInLocalTimezone($orderExpValues['updated_at']);
                                   $values[] = $updatedAt;
                                }
                                else
                                {
                                    $values[] = $orderExpValues['magento_column_value'];
                                }

                            }
                        }

                        if(($guestOrderExportStatus == 0 || $emailAsIdentifierStatus == 0) && $creditMemoOrder->getCustomerIsGuest() == 1){

                        } else {
                            fputcsv($handle, $values);
                        }
                    }

                }
                $file = $outputFile;
                $fileOpen = fopen($filePath,"r");
                $remoteDirPath = $bulkDir;
                if ($remoteDirPath == '/') {
                    $remoteFileName = $outputFile;
                } else {
                    $remoteDirPath = rtrim($remoteDirPath,'/');
                    $remoteFileName = $remoteDirPath."/".$outputFile;
                }

                if ($passiveMode == 1) {
                    @ftp_pasv($ftpConnection, true);
                }

                if (!@ftp_chdir($ftpConnection, $remoteDirPath)) {
                    @ftp_mkdir($ftpConnection,$remoteDirPath);
                }
                @ftp_chdir($ftpConnection, '/');
                $trackErrors = ini_get('track_errors');
                ini_set('track_errors', 1);

                if (@ftp_put($ftpConnection, $remoteFileName, $filePath, FTP_ASCII)) {
                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'File uploaded to FTP server successfully';
                    $logsArray['description'] = $remoteFileName;
                    $logsArray['action'] = 'synced to FTP';
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);
                    $errorCount = 0;
                    $this->messageManager->addSuccess("File uploaded to FTP server successfully !!!");
                } else {
                    // error message is now in $php_errormsg
                    $msg = $php_errormsg;
                    ini_set('track_errors', $trackErrors);
                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Failed to upload file on FTP server';
                    $logsArray['description'] = 'Failed to upload file on FTP server. ' . $msg;
                    $logsArray['action'] = 'synced to FTP';
                    $logsArray['message_type'] = 'Error';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);
                    $errorCount = 1;
                    $this->messageManager->addError("Failed to upload file on FTP server !!! ".$msg);
                }
                unlink($filePath);
                $errorCount = 0;
            } else {
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Failed to connect with FTP server.';
                $logsArray['description'] = 'Failed to connect with FTP server.';
                $logsArray['action'] = 'synced to FTP';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'sync';
                $this->logsHelper->logs($logsArray);
                $errorCount = 1 ;
                $this->messageManager->addError("Failed to connect with FTP server. Please check your settings and try again !!!");
            }
        } else {
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Invalid FTP credentials';
            $logsArray['description'] = 'Invalid FTP credential. Please check your settings and try again';
            $logsArray['action'] = 'synced to FTP';
            $logsArray['message_type'] = 'Error';
            $logsArray['log_action'] = 'sync';
            $this->logsHelper->logs($logsArray);
            $errorCount = 1;
            $this->messageManager->addError("Invalid FTP credential. Please check your settings and try again !!!");
        }

        $logsArray['id'] = $logId;
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        if ($errorCount == 1) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = 'Order export have an error. Please check';
        } else {
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Order export completed';
        }
        $this->logsHelper->manualLogsUpdate($logsArray);
        $resultRedirect = $this->resultRedirectFactory->create();
        $url = $this->getUrl("emarsys_emarsys/orderexport/index/store/$scopeId");
        return $resultRedirect->setPath($url);
    }
}
