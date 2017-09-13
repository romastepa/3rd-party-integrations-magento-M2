<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys;

use Magento\Framework\App\Action\Context;
use Sabre\DAV\Client;



class CronSync
{
    protected $customerObserver;

    protected $logger;

    protected $orderResourceModel;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Sync $syncHelper
     * @param Model\ResourceModel\Sync $syncResourceModel
     */
    
    protected $categoryFactory;
    protected $eavConfig;
    public function __construct(
        \Emarsys\Emarsys\Model\Observer\RealTimeCustomer $customerObserver,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        \Psr\Log\LoggerInterface $logger,
        \Emarsys\Emarsys\Model\Api\Contact $contactModel,
        \Emarsys\Emarsys\Helper\Event $eventHelper,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Customer\Model\CustomerFactory $customer,
        \Emarsys\Emarsys\Model\ResourceModel\Order $orderResourceModel,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Model\QueueFactory $queueModel,
        \Magento\Framework\App\Request\Http $request,
        \Emarsys\Log\Helper\Logs $logsHelper,
        \Magento\Catalog\Model\ProductFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product $productModel,
        \Emarsys\Emarsys\Model\ResourceModel\Product $productResourceModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\Order\CreditmemoRepository $creditmemoRepository,
        \Magento\Sales\Model\OrderFactory $salesOrderCollectionFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Emarsys\Emarsys\Helper\Data $emarsysDataHelper,
        \Magento\Eav\Model\Config $eavConfig
    )
    {
        $this->customerObserver = $customerObserver;
        $this->customerResourceModel = $customerResourceModel;
        $this->contactModel = $contactModel;
        $this->logger = $logger;
        $this->eventHelper = $eventHelper;
        $this->customer = $customer;
        $this->queueModel = $queueModel;
        $this->request = $request;
        $this->orderResourceModel = $orderResourceModel;
        $this->date = $date;
        $this->storeManager = $storeManager;
        $this->logsHelper = $logsHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productModel = $productModel;
        $this->productResourceModel = $productResourceModel;
        $this->scopeConfig = $scopeConfig;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->salesOrderCollectionFactory = $salesOrderCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->_emarsysDataHelper = $emarsysDataHelper;
        $this->eavConfig = $eavConfig;
    }


    /**
     * Customer sync method for cron
     */
    public function cronCustomerSync($schedule)
    {
        $scheduleId = $schedule->getScheduleId();
        $storeData = $this->customerResourceModel->getAllStoresId();  // get all active stores

        foreach ($storeData as $store) {
            // Get last sync date
            $data = array();
            $scopeId = $data['storeId'] = $store['store_id'];
            $websiteId = $data['website'] = $store['website_id'];
            $data['fromDate'] = '';
            $data['toDate'] = '';
            $scope = 'websites';

            $websiteCode = $this->storeManager->getStore($scopeId)->getWebsite()->getCode();
            $storeCode = $this->storeManager->getStore($scopeId)->getCode();

            $subscribedStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/attribute_value', $scope, $scopeId);
            if ($subscribedStatus == '' && $websiteId == 1) {
                $subscribedStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/attribute_value');
            }

            $data['subscribeStatus'] = $subscribedStatus;
            $customerCollection = $this->customerResourceModel->getCustomerIdCollectionForCron($data);

            foreach ($customerCollection as $customer) {
                if (isset($customer['entity_id'])) {
                    $customerId = $customer['entity_id'];
                    $this->contactModel->syncContact($customerId, $websiteId, $scopeId);
                }
            }
        }

    }

    public function cronOrderSyncQueue($schedule)
    {
        $allStores = $this->orderResourceModel->getStores();
        $creditmemoOrderIds = array();
        foreach ($allStores as $store) {
            if ($store['store_id'] == 0)
                continue;
            $creditmemoCollection = $this->creditmemoRepository->create()->getCollection();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $priceHelper = $objectManager->create('\Magento\Framework\Pricing\Helper\Data');
            $queueCollection = $objectManager->create('Emarsys\Emarsys\Model\OrderQueue');
            $queueDataAll = $queueCollection->getCollection()->addFieldToFilter('entity_type_id', 1)->getData();
            $queueCollection = $objectManager->create('Emarsys\Emarsys\Model\OrderQueue');
            $queueDataCreditmemoAll = $queueCollection->getCollection()->addFieldToFilter('entity_type_id', 2)->getData();
            $orderIds = array();
            foreach ($queueDataAll as $queueData) {
                $orderIds[] = $queueData['entity_id'];
            }
            foreach ($queueDataCreditmemoAll as $queueData) {
                $creditmemoOrderIds[] = $queueData['entity_id'];
            }
            $salesOrderModel = $objectManager->create('\Magento\Sales\Model\Order');
            $salesOrderCollection = $salesOrderModel->getCollection()->addFieldToFilter('store_id', $store['store_id'])->addFieldToFilter('entity_id', array('in' => $orderIds));

            //login functionality start
            $scope = 'websites';
            $scopeId = $store['store_id'];
            $websiteId = $this->storeManager->getStore($scopeId)->getWebsiteId();;
            $logsArray['job_code'] = 'order';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Bulk order export started';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['store_id'] = $scopeId;
            $logsArray['website_id'] = $websiteId;
            $logId = $this->logsHelper->manualLogs($logsArray,1);

            $hostname = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/hostname', $scope, $websiteId);
            $port = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/port', $scope, $websiteId);
            $username = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/username', $scope, $websiteId);
            $password = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/ftp_password', $scope, $websiteId);
            $bulkDir = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/ftp_bulk_export_dir', $scope, $websiteId);
            $ftpSsl = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/useftp_overssl', $scope, $websiteId);
            $passiveMode = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/usepassive_mode', $scope, $websiteId);

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
                    $heading = array('order', 'date', 'customer', 'item', 'unit_price', 'c_sales_amount', 'quantity');
                    $emasysFields = $this->orderResourceModel->getEmarsysOrderFields();
                    foreach ($emasysFields as $field) {
                        if ($field['emarsys_order_field'] != '')
                            $heading[] = $field['emarsys_order_field'];
                    }

                    $localFilePath = BP . "/var";
                    $outputFile = "sales_items_" . $this->date->date('YmdHis', time()) . "_" . $store['code'] .".csv";
                    $filePath = $localFilePath . "/" . $outputFile;
                    $handle = fopen($filePath, 'w');
                    fputcsv($handle, $heading);

                    $orderStatuse = $this->customerResourceModel->getDataFromCoreConfig('smart_insight/smart_insight/orderexportforstatus', $scope, $websiteId);
                    $orderStatuses = explode(',', $orderStatuse);
                    $orderCollection = array();
                    $guestOrderExportStatus = $this->customerResourceModel->getDataFromCoreConfig('smart_insight/smart_insight/exportguest_checkoutorders', $scope, $websiteId);
                    $emailAsIdentifierStatus = $this->customerResourceModel->getDataFromCoreConfig('smart_insight/smart_insight/exportusing_emailidentifier', $scope, $websiteId);

                    foreach ($salesOrderCollection as $order) {
                        $orderId = $order->getRealOrderId();
                        $orderEntityId = $order->getId();
                        $createdDate = $order->getCreatedAt();
                        $customerEmail = $order->getCustomerEmail();

                        foreach ($order->getAllVisibleItems() as $item) {
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
                                $values[] = number_format((float)$unitPrice, 2, '.', '') ;;
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
                            foreach ($emasysFields as $field) {
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
                            if (($guestOrderExportStatus == 0 || $emailAsIdentifierStatus == 0) && $order->getCustomerIsGuest() == 1) {
                            } else {
                                fputcsv($handle, $values);
                            }

                        }
                    }

                    foreach ($queueDataCreditmemoAll as $order) {
                        $creditMemoOrder = $this->salesOrderCollectionFactory->create()->load($order['entity_id']);
                        $orderId = $creditMemoOrder->getIncrementId();
                        $orderEntityId = $creditMemoOrder->getId();
                        $createdDate = $creditMemoOrder->getCreatedAt();
                        $customerEmail = $creditMemoOrder->getCustomerEmail();
                        foreach ($creditMemoOrder->getAllVisibleItems() as $item) {
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
                                $values[] = "-".number_format((float)$unitPrice, 2, '.', '') ;;
                            } else {
                                $values[] = '';
                            }
                            $cSalesAmount = $item->getRowTotalInclTax() - $item->getDiscountAmount();    //cSalesAmount
                            if ($cSalesAmount != '') {
                                $values[] = "-" . $cSalesAmount;
                            } else {
                                $values[] = '';
                            }
                            $values[] = (int)$item->getQtyInvoiced();

                            foreach ($emasysFields as $field) {
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

                            if ($creditMemoOrder->getCustomerIsGuest() == 1) {
                                if ($guestOrderExportStatus == 0 || $emailAsIdentifierStatus == 0) {
                                } else {
                                    fputcsv($handle, $values);
                                }
                            } else {
                                fputcsv($handle, $values);
                            }
                        }

                    }

                    $file = $outputFile;
                    $fileOpen = fopen($filePath, "r");
                    $remoteDirPath = $bulkDir;
                    if ($remoteDirPath == '/') {
                        $remoteFileName = $outputFile;
                    } else {
                        $remoteDirPath = rtrim($remoteDirPath, '/');
                        $remoteFileName = $remoteDirPath . "/" . $outputFile;
                    }

                    if ($passiveMode == 1) {
                        @ftp_pasv($ftpConnection, true);
                    }

                    if (!@ftp_chdir($ftpConnection, $remoteDirPath)) {
                        @ftp_mkdir($ftpConnection, $remoteDirPath);
                    }
                    @ftp_chdir($ftpConnection, '/');
                    if (@ftp_put($ftpConnection, $remoteFileName, $filePath, FTP_ASCII)) {
                        $logsArray['id'] = $logId;
                        $logsArray['emarsys_info'] = 'File uploaded to FTP server successfully';
                        $logsArray['description'] = $remoteFileName;
                        $logsArray['action'] = 'synced to FTP';
                        $logsArray['message_type'] = 'Success';
                        $logsArray['log_action'] = 'sync';
                        $this->logsHelper->logs($logsArray);
                        $errorCount = 0;
                    } else {
                        $logsArray['id'] = $logId;
                        $logsArray['emarsys_info'] = 'Failed to upload file on FTP server';
                        $logsArray['description'] = 'Failed to upload file on FTP server';
                        $logsArray['action'] = 'synced to FTP';
                        $logsArray['message_type'] = 'Error';
                        $logsArray['log_action'] = 'sync';
                        $this->logsHelper->logs($logsArray);
                        $errorCount = 1;
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
                    $errorCount = 1;
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
            }
            $logsArray['id'] = $logId;
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            if ($errorCount == 1) {
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'Order export have an error. Please check';
            } else {

                $orderExportStatus = $objectManager->create('Emarsys\Emarsys\Model\OrderExportStatus');
                $orderExportStatusCollection = $orderExportStatus->getCollection()->addFieldToFilter('order_id', array('in' => $orderIds));
                $allDataExport = $orderExportStatusCollection->getData();
                foreach ($allDataExport as $orderExportStat) {
                    $eachOrderStat = $objectManager->create('Emarsys\Emarsys\Model\OrderExportStatus')->load($orderExportStat['id']);
                    $eachOrderStat->setExported(1);
                    $eachOrderStat->save();
                }
                foreach ($queueDataAll as $queueData) //remove the exported records from the queue table
                {
                    $queueDataEach = $objectManager->create('Emarsys\Emarsys\Model\OrderQueue')->load($queueData['id']);
                    $queueDataEach->delete();
                }

                $creditmemoExportStatus = $objectManager->create('Emarsys\Emarsys\Model\CreditmemoExportStatus');
                $creditmemoExportStatusCollection = $creditmemoExportStatus->getCollection()->addFieldToFilter('order_id', array('in' => $creditmemoOrderIds));
                $allDataExport = $creditmemoExportStatusCollection->getData();
                foreach ($allDataExport as $orderExportStat) {
                    $eachOrderStat = $objectManager->create('Emarsys\Emarsys\Model\CreditmemoExportStatus')->load($orderExportStat['id']);
                    $eachOrderStat->setExported(1);
                    $eachOrderStat->save();
                }
                foreach ($queueDataCreditmemoAll as $queueData) //remove the exported records from the queue table
                {
                    $queueDataEach = $objectManager->create('Emarsys\Emarsys\Model\OrderQueue')->load($queueData['id']);
                    $queueDataEach->delete();
                }
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Order export completed';
            }
            $this->logsHelper->manualLogsUpdate($logsArray);
        }
    }

    public function cronProductSync($schedule)
    {
        $allStores = $this->orderResourceModel->getStores();
        foreach ($allStores as $store) {
            if ($store['store_id'] == 0)
                continue;
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $scope = 'websites';
            $scopeId = $store['store_id'];
            $websiteId = $this->storeManager->getStore($scopeId)->getWebsiteId();

            $logsArray['job_code'] = 'product';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'bulk product export started';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['store_id'] = $scopeId;
            $logsArray['website_id'] = $websiteId;
            $logId = $this->logsHelper->manualLogs($logsArray,1);
            $hostname = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/hostname', $scope, $websiteId);
            if ($hostname == '' && $websiteId == 1) {
                $hostname = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/hostname');
            }

            $port = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/port', $scope, $websiteId);
            if ($port == '' && $websiteId == 1) {
                $port = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/port');
            }

            $username = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/username', $scope, $websiteId);
            if ($username == '' && $websiteId == 1) {
                $username = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/username');
            }

            $password = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/ftp_password', $scope, $websiteId);
            if ($password == '' && $websiteId == 1) {
                $password = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/ftp_password');
            }

            $bulkDir = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/ftp_bulk_export_dir', $scope, $websiteId);
            if ($bulkDir == '' && $websiteId == 1) {
                $bulkDir = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/ftp_bulk_export_dir');
            }

            $ftpSsl = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/useftp_overssl', $scope, $websiteId);
            if ($ftpSsl == '' && $websiteId == 1) {
                $ftpSsl = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/useftp_overssl');
            }

            $passiveMode = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/usepassive_mode', $scope, $websiteId);
            if ($passiveMode == '' && $websiteId == 1) {
                $passiveMode = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/ftp_settings/usepassive_mode');
            }

            if ($hostname != '' && $port != '' && $username != '' && $password != '') {
                $errorStatus = 0;
            } else {
                $errorStatus = 1;
            }

            if ($errorStatus != 1) {
                if ($ftpSsl == 1) {
                    $ftpConnection = @ftp_ssl_connect($hostname);
                } else {
                    $ftpConnection = @ftp_connect($hostname);
                }
                $ftpLogin = @ftp_login($ftpConnection, $username, $password);
                if ($ftpLogin) {
                    $mappedAttributes = $this->productResourceModel->getMappedProductAttribute($scopeId);
                    $emarsysFieldNames = array();
                    $magentoAttributeNames = array();
                    if (isset($mappedAttributes) && count($mappedAttributes) != '') {
                        foreach ($mappedAttributes as $mapAttribute) {
                            $emarsysFieldId = $mapAttribute['emarsys_attr_code'];
                            $emarsysFieldName = $this->productResourceModel->getEmarsysFieldName($scopeId, $emarsysFieldId);
                            $emarsysFieldNames[] = $emarsysFieldName;
                            $magentoAttributeNames[] = $mapAttribute['magento_attr_code'];
                        }
                        $heading = $emarsysFieldNames;
                        $localFilePath = BP . "/var";
                        $outputFile = "products_" . $this->date->date('YmdHis', time()) . "_" . $store['code'].".csv";
                        $filePath = $localFilePath . "/" . $outputFile;
                        $handle = fopen($filePath, 'w');
                        fputcsv($handle, $heading);
                        $excludeCategories = explode(',', $this->scopeConfig->getValue('emarsys_predict/feed_export/include_bundle_product', $scope, $websiteId));//emarsys_predict/feed_export/excluded_categories
                        $productCollection = array();
                        $productCollection = $this->productCollectionFactory->create()->getCollection();
                        foreach ($productCollection->getData() as $product) {

                            $excludeCatFlag = 0;
                            $productData = $objectManager->create('\Magento\Catalog\Model\Product')->load($product['entity_id']);
                            $productType = $productData->getTypeId();
                            $catIds = array();
                            $catIds = $productData->getCategoryIds();
                            $categoryNames = array();
                            foreach ($catIds as $catId) {
                                if (in_array($catId, $excludeCategories)) {
                                    $excludeCatFlag = 1;
                                    break;
                                }
                                $cateData   = $this->categoryFactory->create()->load($catId);
                                $categoryPath = $cateData->getPath();
                                $categoryPathIds = explode('/', $categoryPath);
                                $childCats = array();
                                if (count($categoryPathIds) > 2) {
                                    $pathIndex = 0;
                                    foreach($categoryPathIds as $categoryPathId) {
                                        if($pathIndex <= 1) {
                                            $pathIndex++;
                                            continue;
                                        }
                                        $childCateData = $this->categoryFactory->create()->load($categoryPathId);
                                        $childCats[] = $childCateData->getName();
                                    }
                                    $categoryNames[] = implode(" > ", $childCats);
                                }
                            }

                            $attributeData = array();
                            $attributeData = array();
                            foreach ($magentoAttributeNames as $attributeName) {

                                $attributeOption = $productData->getData($attributeName);
                                if (!is_array($attributeOption))
                                {
                                    $attribute = $this->eavConfig->getAttribute('catalog_product', "$attributeName");
                                    if ($attribute->getFrontendInput() == 'boolean' || $attribute->getFrontendInput() == 'select'  || $attribute->getFrontendInput() == 'multiselect' )
                                    {
                                        $attributeOption = $productData->getAttributeText($attributeName);
                                    }
                                }
                                if (isset($attributeOption) && $attributeOption != '') {
                                    if(is_array($attributeOption))
                                    {
                                        if ($attributeName == 'category_ids')
                                        {
                                            $attributeData[] = implode('|',$categoryNames);

                                        }
                                        else if ($attributeName == 'quantity_and_stock_status')
                                        {

                                            if ($productData->getData('quantity_and_stock_status')['is_in_stock'] == 1)
                                                $attributeData[] =  'TRUE';
                                            else
                                                $attributeData[] = 'FALSE';
                                        }

                                        else
                                        {
                                            $attributeData[] = implode(',',$attributeOption);
                                        }

                                    }
                                    else
                                    {
                                        if ($attributeName == 'image')
                                        {

                                            $attributeData[] = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)."catalog/product/".$attributeOption;
                                        }
                                        else if ($attributeName == 'url_key')
                                        {
                                            $attributeData[] = $productData->getProductUrl();
                                        }
                                        else
                                        {
                                            $attributeData[] = $attributeOption;
                                        }

                                    }

                                } else {
                                    $attributeData[] = '';
                                }
                            }
                            $data['includeBundle'] = $this->scopeConfig->getValue('emarsys_predict/feed_export/include_bundle_product', $scope, $websiteId);
                            if ((isset($data['includeBundle']) && $data['includeBundle'] == 0 && $productType == 'bundle') || $excludeCatFlag == 1) {

                            } else {
                                fputcsv($handle, $attributeData);
                            }
                        }
                        $file = $outputFile;
                        $fileOpen = fopen($filePath, "r");
                        $remoteDirPath = $bulkDir;
                        if ($remoteDirPath == '/') {
                            $remoteFileName = $outputFile;
                        } else {
                            $remoteDirPath = rtrim($remoteDirPath, '/');
                            $remoteFileName = $remoteDirPath . "/" . $outputFile;
                        }
                        if ($passiveMode == 1) {
                            @ftp_pasv($ftpConnection, true);
                        }
                        if (!@ftp_chdir($ftpConnection, $remoteDirPath)) {
                            @ftp_mkdir($ftpConnection, $remoteDirPath);
                        }
                        @ftp_chdir($ftpConnection, '/');
                        if (@ftp_put($ftpConnection, $remoteFileName, $filePath, FTP_ASCII)) {
                            $logsArray['id'] = $logId;
                            $logsArray['emarsys_info'] = 'File uploaded to FTP server successfully';
                            $logsArray['description'] = $remoteFileName;
                            $logsArray['action'] = 'synced to emarsys';
                            $logsArray['message_type'] = 'Success';
                            $logsArray['log_action'] = 'sync';
                            $this->logsHelper->logs($logsArray);
                            $errorCount = 0;
                        } else {
                            $logsArray['id'] = $logId;
                            $logsArray['emarsys_info'] = 'Failed to upload file on FTP server';
                            $logsArray['description'] = 'Failed to upload file on FTP server';
                            $logsArray['action'] = 'synced to emarsys';
                            $logsArray['message_type'] = 'Error';
                            $logsArray['log_action'] = 'sync';
                            $this->logsHelper->logs($logsArray);
                            $errorCount = 1;
                        }
                        unlink($filePath);

                        $errorCount = 0;
                    } else {
                        $logsArray['id'] = $logId;
                        $logsArray['emarsys_info'] = 'Attributes are not mapped';
                        $logsArray['description'] = 'Failed to upload file on server. Attributes are not mapped';
                        $logsArray['action'] = 'synced to emarsys';
                        $logsArray['message_type'] = 'Error';
                        $logsArray['log_action'] = 'sync';
                        $this->logsHelper->logs($logsArray);
                        $errorCount = 1;
                    }
                } else {
                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Failed to connect with FTP server.';
                    $logsArray['description'] = 'Failed to connect with FTP server.';
                    $logsArray['action'] = 'synced to FTP';
                    $logsArray['message_type'] = 'Error';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);
                    $errorCount = 1;
                }
            } else {
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Invalid FTP credentials';
                $logsArray['description'] = 'Invalid FTP credential. Please check your settings and try again';
                $logsArray['action'] = 'synced to emarsys';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'sync';
                $this->logsHelper->logs($logsArray);
                $errorCount = 1;
            }
            $logsArray['id'] = $logId;
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            if ($errorCount == 1) {
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'Product export have an error. Please check';
            } else {
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Product export completed';
            }
            $this->logsHelper->manualLogsUpdate($logsArray);
        }
    }

    public function cronCustomerSyncQueue($schedule)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $stores = $storeManager->getStores($withDefault = false);
        $scopeConfig = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');

        foreach ($stores as $store)
        {
            $storeId = $store->getData()['store_id'];
            $storeCode = $store->getData()['code'];
            $scope = 'websites';
            $websiteId = $store->getData()['website_id'];
            $scopeId = $websiteId;
            $websiteCode = $storeManager->getStore($storeId)->getWebsite()->getCode();

            $logsArray['job_code'] = 'subscriber';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'bulk subscriber export started';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['website_id'] = $websiteId;
            $logsArray['store_id'] = $storeId;
            $logId = $this->logsHelper->manualLogs($logsArray, 1);
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Subscriber Export Started';
            $logsArray['description'] = 'Subscriber Export Started for Store ID : ' . $storeId;
            $logsArray['action'] = 'synced to emarsys';
            $logsArray['message_type'] = 'Success';
            $logsArray['log_action'] = 'sync';
            $logsArray['website_id'] = $websiteId;
            $this->logsHelper->logs($logsArray);

            $webDavUrl = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_url', $scope, $scopeId);
            if ($webDavUrl == '' && $websiteId == 1) {
                $webDavUrl = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_url');
            }

            $webDavUser = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_user', $scope, $scopeId);
            if ($webDavUser == '' && $websiteId == 1) {
                $webDavUser = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_user');
            }

            $webDavPass = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_password', $scope, $scopeId);
            if ($webDavPass == '' && $websiteId == 1) {
                $webDavPass = $this->customerResourceModel->getDataFromCoreConfig('emarsys_settings/webdav_setting/webdav_password');
            }
            if ($webDavUrl != '' && $webDavUser != '' && $webDavPass != '') {
                $errorStatus = 0;
            } else {
                $errorStatus = 1;
            }
            if ($errorStatus != 1) {
                $settings = array(
                    'baseUri' => $webDavUrl,
                    'userName' => $webDavUser,
                    'password' => $webDavPass,
                    'proxy' => '',
                );
                $client = new Client($settings);
                $customervalues = array();
                $optInStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/initial_db_load', $scope, $scopeId);
                if ($optInStatus == '' && $websiteId == 1) {
                    $optInStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/initial_db_load');
                }

                $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
                $newsLetSubTableName = $resource->getTableName('newsletter_subscriber');

                $queueCollection = $this->queueModel->create()->getCollection();
                $queueCollection->addFieldToSelect('entity_id');
                $queueCollection->addFieldToFilter('entity_type_id', 2);
                $queueCollection->getSelect()->joinLeft(
                    ['newsletter_subscriber' => $newsLetSubTableName],
                    'main_table.entity_id = newsletter_subscriber.subscriber_id', array('subscriber_email', 'subscriber_confirm_code'));


                if (isset($scopeId)) {
                    if ($optInStatus == 'attribute') {
                        $subscribedStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/attribute_value', $scope, $scopeId);
                        if ($subscribedStatus == '' && $websiteId == 1) {
                            $subscribedStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/attribute_value');
                        }
                        $data['subscribeStatus'] = $subscribedStatus;
                        $customervalues = $queueCollection;
                    } else {
                        $customervalues = $queueCollection;
                    }
                }

                $emarsysFieldNames = array();
                $emarsysFieldId = 3;
                $emarsysFieldNames[] = $this->customerResourceModel->getEmarsysFieldName($storeId, $emarsysFieldId);


                if ($optInStatus != '') {
                    $emarsysFieldNames[] = 'Opt-In';
                }

                $heading = $emarsysFieldNames;
                $localFilePath = BP . "/var";
                $outputFile = "subscribers_" . $this->date->date('YmdHis', time()) . "_" . $storeCode.".csv";
                $filePath = $localFilePath . "/" . $outputFile;
                $handle = fopen($filePath, 'w');
                fputcsv($handle, $heading);
                foreach ($customervalues as $value) {
                    $values = array();
                    $values[] = $value['subscriber_email'];
                    if ($optInStatus == 'true') {
                        $values[] = '1';
                    } else if ($optInStatus == 'empty') {
                        $values[] = 0;
                    } else if ($optInStatus == 'attribute') {
                        $values[] = '1';
                    }
                    fputcsv($handle, $values);
                }
                $file = $outputFile;
                $fileOpen = fopen($filePath, "r");
                $response = $client->request('PUT', $file, $fileOpen);
                unlink($filePath);
                $errorCount = 0;
                if ($response['statusCode'] == '201') {
                    $fileLoc = $response['headers']['location']['0'];
                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'File uploaded to server successfully';
                    $logsArray['description'] = $fileLoc;
                    $logsArray['action'] = 'synced to emarsys';
                    $logsArray['website_id'] = $websiteId;
                    $logsArray['message_type'] = 'Success';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);
                    $logsArray['website_id'] = $websiteId;
                    $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                    $errorCount = 0;
                    foreach ($customervalues as $value) {
                        $test = $this->queueModel->create()->load($value['entity_id'],'entity_id')->delete();
                    }
                } else {

                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Failed to upload file on server';
                    $logsArray['description'] = strip_tags($response['body']);
                    $logsArray['action'] = 'synced to emarsys';
                    $logsArray['message_type'] = 'Error';
                    $logsArray['log_action'] = 'sync';
                    $logsArray['website_id'] = $websiteId;
                    $this->logsHelper->logs($logsArray);
                    $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                    $errorCount = 1;
                }
            } else {
                $logsArray['id'] = $logId;
                $logsArray['emarsys_info'] = 'Invalid credentials';
                $logsArray['description'] = 'Invalid credential. Please check your settings and try again';
                $logsArray['action'] = 'synced to emarsys';
                $logsArray['message_type'] = 'Error';
                $logsArray['website_id'] = $websiteId;
                $logsArray['log_action'] = 'sync';
                $this->logsHelper->logs($logsArray);
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $errorCount = 1;
            }
            $logsArray['id'] = $logId;
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            if ($errorCount == 1) {
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'Subscriber export have an error. Please check';
            } else {
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Subscriber export completed';
            }

            $this->logsHelper->manualLogsUpdate($logsArray);

            // customer export start

            $queueCollection = $this->queueModel->create()->getCollection();
            $queueCollection->addFieldToSelect('entity_id');
            $queueCollection->addFieldToFilter('entity_type_id', 1);
            $cusArray = array();
            $cusColl = $queueCollection->getData();
            foreach ($cusColl as $key => $value) {
                $cusArray[] = $value['entity_id'];
            }
            $custModel = $this->customer->create();
            $cusColl = $custModel->getCollection();
            $cusColl->addAttributeToFilter('entity_id', $cusArray);

            $logsArray['job_code'] = 'customer';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'bulk customer export started';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['store_id'] = $storeId;
            $logId1 = $this->logsHelper->manualLogs($logsArray, 1);
            $logsArray['id'] = $logId1;
            $logsArray['emarsys_info'] = 'Customer Export Started';
            $logsArray['description'] = 'Customer Export Started for Store ID : ' . $storeId;
            $logsArray['action'] = 'synced to emarsys';
            $logsArray['message_type'] = 'Success';
            $logsArray['log_action'] = 'sync';
            $logsArray['website_id'] = $websiteId;
            $this->logsHelper->logs($logsArray);

            if ($errorStatus != 1) {

                $settings = array(
                    'baseUri' => $webDavUrl,
                    'userName' => $webDavUser,
                    'password' => $webDavPass,
                    'proxy' => '',
                );

                $client = new Client($settings);

                $customervalues = array();
                $customerData = $this->customer->create();

                $optInStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/initial_db_load', $scope, $scopeId);
                if ($optInStatus == '' && $websiteId == 1) {
                    $optInStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/initial_db_load');
                }

                if (isset($scopeId)) {
                    $websiteId = $this->storeManager->getStore($scopeId)->getWebsiteId();
                    $data['website'] = $websiteId;
                    if (isset($data['fromDate']) && $data['fromDate'] != '' && isset($data['toDate']) && $data['toDate'] != '') {
                        $data['fromDate'] = date('Y-m-d H:i:s', strtotime($data['fromDate']));
                        $data['toDate'] = date('Y-m-d H:i:s', strtotime($data['toDate']));
                    }
                    if ($optInStatus == 'attribute') {
                        $subscribedStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/attribute_value', $scope, $scopeId);
                        if ($subscribedStatus == '' && $websiteId == 1) {
                            $subscribedStatus = $this->customerResourceModel->getDataFromCoreConfig('contacts_synchronization/initial_db_load/attribute_value');
                        }
                        $data['subscribeStatus'] = $subscribedStatus;
                        $customervalues = $cusColl;
                    } else {
                        $customervalues = $cusColl;
                    }
                }
                $mappedAttributes = $this->customerResourceModel->getMappedCustomerAttribute($scopeId);
                $emarsysFieldNames = array();

                if (isset($mappedAttributes) && count($mappedAttributes) != '') {

                    foreach ($mappedAttributes as $mapAttribute) {
                        $emarsysFieldId = $mapAttribute['emarsys_contact_field'];
                        $magentoFieldIds[] = $mapAttribute['magento_attribute_id'];
                        $emarsysFieldName = $this->customerResourceModel->getEmarsysFieldName($scopeId, $emarsysFieldId);
                        $emarsysFieldNames[] = $emarsysFieldName;
                    }

                    if ($optInStatus != '') {
                        $emarsysFieldNames[] = 'Opt-In';
                    }

                    $heading = $emarsysFieldNames;
                    $localFilePath = BP . "/var";
                    $outputFile = "customers_" . $this->date->date('YmdHis', time()) . "_" . $websiteCode . ".csv";
                    $filePath = $localFilePath . "/" . $outputFile;
                    $handle = fopen($filePath, 'w');
                    fputcsv($handle, $heading);
                    $magentoFieldCodes = array();
                    foreach ($magentoFieldIds as $field) {
                        $attData = $this->customerResourceModel->getAttributeCodeById($field);
                        $magentoFieldCodes[$attData['attribute_code']] = $attData['attribute_code'];
                    }
                    $allAttsUsed = array();
                    foreach ($customervalues as $value) {
                        $values = array();
                        $value = array_replace(array_flip($magentoFieldCodes), $value->getData());
                        foreach ($value as $key => $cusData) {
                            $attData = $this->customerResourceModel->getAttributeIdByCode($key);

                            if (in_array($attData['attribute_id'], $magentoFieldIds)) {
                                $allAttsUsed[] = $key;
                                if (isset($cusData)) {
                                    $values[] = $cusData;
                                } else {
                                    $values[] = '';
                                }
                            }
                        }
                        if ($optInStatus == 'true') {
                            $values[] = '1';
                        } else if ($optInStatus == 'empty') {
                            $values[] = 0;
                        } else if ($optInStatus == 'attribute') {
                            $values[] = '1';
                        }
                        if (isset($value['default_billing']) && $value['default_billing'] != NULL) {
                            $magentoAddFields = array();
                            $customerBillingAddress = $this->customerResourceModel->getCustPriBillAddress($value['entity_id']);
                            if ($customerBillingAddress)
                                foreach ($customerBillingAddress->getData() as $key => $dataValue) {
                                    $attData = $this->customerResourceModel->getAttributeIdByCode($key);
                                    if (!is_array($dataValue) && in_array($attData['attribute_id'], $magentoFieldIds)) {
                                        $magentoAddFields[$key] = $dataValue;
                                    }

                                }
                            $magentoFlipFields = array_replace(array_flip($magentoFieldCodes), $magentoAddFields);
                            foreach ($magentoFlipFields as $key => $cusData) {
                                $attData = $this->customerResourceModel->getAttributeIdByCode($key);
                                if (in_array($attData['attribute_id'], $magentoFieldIds)) {
                                    if (isset($magentoAddFields[$key])) {
                                        $attKey = array_search($key, $allAttsUsed);
                                        $values[$attKey] = $magentoAddFields[$key];
                                    }
                                }

                            }
                        }

                        if (isset($value['default_shipping']) && $value['default_shipping'] != NULL) {
                            $magentoAddFields = array();
                            $customerBillingAddress = $this->customerResourceModel->getCustPriShipAddress($value['entity_id']);
                            if ($customerBillingAddress)
                                foreach ($customerBillingAddress->getData() as $key => $dataValue) {
                                    $attData = $this->customerResourceModel->getAttributeIdByCode($key);
                                    if (!is_array($dataValue) && in_array($attData['attribute_id'], $magentoFieldIds)) {
                                        $magentoAddFields[$key] = $dataValue;
                                    }

                                }
                            $magentoFlipFields = array_replace(array_flip($magentoFieldCodes), $magentoAddFields);
                            foreach ($magentoFlipFields as $key => $cusData) {
                                $attData = $this->customerResourceModel->getAttributeIdByCode($key);
                                if (in_array($attData['attribute_id'], $magentoFieldIds)) {
                                    if (isset($magentoAddFields[$key])) {
                                        $attKey = array_search($key, $allAttsUsed);
                                        $values[$attKey] = $magentoAddFields[$key];
                                    }
                                }

                            }
                        }
                        fputcsv($handle, $values);
                    }
                    $file = $outputFile;
                    $fileOpen = fopen($filePath, "r");
                    $response = $client->request('PUT', $file, $fileOpen);
                    unlink($filePath);
                    $errorCount = 0;
                    if ($response['statusCode'] == '201') {
                        $fileLoc = $response['headers']['location']['0'];
                        $logsArray['id'] = $logId1;
                        $logsArray['emarsys_info'] = 'File uploaded to server successfully';
                        $logsArray['description'] = $fileLoc;
                        $logsArray['action'] = 'synced to emarsys';
                        $logsArray['message_type'] = 'Success';
                        $logsArray['log_action'] = 'sync';
                        $logsArray['website_id'] = $websiteId;
                        $this->logsHelper->logs($logsArray);
                        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                        $errorCount = 0;
                        foreach ($customervalues as $value) {
                            $test = $this->queueModel->create()->load($value['entity_id'],'entity_id')->delete();
                        }
                    } else {
                        $logsArray['id'] = $logId1;
                        $logsArray['emarsys_info'] = 'Failed to upload file on server';
                        $logsArray['description'] = strip_tags($response['body']);
                        $logsArray['action'] = 'synced to emarsys';
                        $logsArray['message_type'] = 'Error';
                        $logsArray['log_action'] = 'sync';
                        $logsArray['website_id'] = $websiteId;
                        $this->logsHelper->logs($logsArray);
                        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                        $errorCount = 1;
                    }
                } else {
                    $logsArray['id'] = $logId1;
                    $logsArray['emarsys_info'] = 'Attributes are not mapped';
                    $logsArray['description'] = 'Failed to upload file on server. Attributes are not mapped';
                    $logsArray['action'] = 'synced to emarsys';
                    $logsArray['message_type'] = 'Error';
                    $logsArray['log_action'] = 'sync';
                    $logsArray['website_id'] = $websiteId;
                    $this->logsHelper->logs($logsArray);
                    $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                    $errorCount = 1;
                }
            } else {
                $logsArray['id'] = $logId1;
                $logsArray['emarsys_info'] = 'Invalid credentials';
                $logsArray['description'] = 'Invalid credential. Please check your settings and try again';
                $logsArray['action'] = 'synced to emarsys';
                $logsArray['message_type'] = 'Error';
                $logsArray['log_action'] = 'sync';
                $logsArray['website_id'] = $websiteId;
                $this->logsHelper->logs($logsArray);
                $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
                $errorCount = 1;
            }

            $logsArray['id'] = $logId1;
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            if ($errorCount == 1) {
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'Customer export have an error. Please check';
            } else {
                $logsArray['status'] = 'success';
                $logsArray['messages'] = 'Customer export completed';
            }
            $this->logsHelper->manualLogsUpdate($logsArray);
        }
    }

    public function cronEmarsysSchemaCheck($schedule)
    {
        $emarsysApiIds = array();
        $eventSchema = $this->eventHelper->getEventSchema();
        foreach ($eventSchema['data'] as $_eventSchema) {
            $emarsysApiIds[] = $_eventSchema['id'];
        }
        $emarsysLocalIds = $this->eventHelper->getLocalEmarsysEvents();
        $result = array_diff($emarsysApiIds, $emarsysLocalIds);
        if (count($result)) {
            $this->eventHelper->saveEmarsysEventSchemaNotification();
        }
    }
}
