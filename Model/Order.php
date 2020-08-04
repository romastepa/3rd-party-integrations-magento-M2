<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Emarsys\Emarsys\Helper\Logs as EmarsysHelperLogs;
use Emarsys\Emarsys\Model\ResourceModel\CreditmemoExport\CollectionFactory as EmarsysCreditmemoExportFactory;
use Emarsys\Emarsys\Model\ResourceModel\Customer as EmarsysResourceModelCustomer;
use Emarsys\Emarsys\Model\ResourceModel\Order as OrderResourceModel;
use Emarsys\Emarsys\Model\ResourceModel\OrderExport\CollectionFactory as EmarsysOrderExportFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\SnapshotFactory;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\Timezone as TimeZone;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\CollectionFactory as CreditmemoItemCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Order
 */
class Order extends AbstractModel
{
    const BATCH_SIZE = 500;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var Customer
     */
    protected $customerResourceModel;

    /**
     * @var Logs
     */
    protected $logsHelper;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var OrderResourceModel
     */
    protected $orderResourceModel;

    /**
     * @var OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var EmarsysOrderExportFactory
     */
    protected $emarsysOrderExportFactory;

    /**
     * @var OrderQueueFactory
     */
    protected $orderQueueFactory;

    /**
     * @var TimeZone
     */
    protected $timezone;

    /**
     * @var ApiExport
     */
    protected $apiExport;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var array
     */
    protected $salesCsvHeader = [];

    /**
     * @var null | resource
     */
    protected $handle = null;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var OrderItemCollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * @var SnapshotFactory
     */
    protected $snapshotFactory;

    /**
     * @var CreditmemoItemCollectionFactory
     */
    private $creditmemoItemCollectionFactory;

    /**
     * @var EmarsysCreditmemoExportFactory
     */
    private $emarsysCreditmemoExportFactory;

    /**
     * Order constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param MessageManagerInterface $messageManager
     * @param EmarsysResourceModelCustomer $customerResourceModel
     * @param EmarsysHelperLogs $logsHelper
     * @param DateTime $date
     * @param EmarsysHelper $emarsysHelper
     * @param OrderResourceModel $orderResourceModel
     * @param OrderFactory $salesOrderFactory
     * @param EmarsysOrderExportFactory $emarsysOrderExportFactory
     * @param EmarsysCreditmemoExportFactory $emarsysCreditmemoExportFactory
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param CreditmemoItemCollectionFactory $creditmemoItemCollectionFactory
     * @param SnapshotFactory $snapshotFactory
     * @param OrderQueueFactory $orderQueueFactory
     * @param TimeZone $timezone
     * @param ApiExport $apiExport
     * @param DirectoryList $directoryList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        MessageManagerInterface $messageManager,
        EmarsysResourceModelCustomer $customerResourceModel,
        EmarsysHelperLogs $logsHelper,
        DateTime $date,
        EmarsysHelper $emarsysHelper,
        OrderResourceModel $orderResourceModel,
        OrderFactory $salesOrderFactory,
        EmarsysOrderExportFactory $emarsysOrderExportFactory,
        EmarsysCreditmemoExportFactory $emarsysCreditmemoExportFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        CreditmemoItemCollectionFactory $creditmemoItemCollectionFactory,
        SnapshotFactory $snapshotFactory,
        OrderQueueFactory $orderQueueFactory,
        TimeZone $timezone,
        ApiExport $apiExport,
        DirectoryList $directoryList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->emarsysHelper = $emarsysHelper;
        $this->orderResourceModel = $orderResourceModel;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->emarsysOrderExportFactory = $emarsysOrderExportFactory;
        $this->emarsysCreditmemoExportFactory = $emarsysCreditmemoExportFactory;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->creditmemoItemCollectionFactory = $creditmemoItemCollectionFactory;
        $this->snapshotFactory = $snapshotFactory;
        $this->orderQueueFactory = $orderQueueFactory;
        $this->timezone = $timezone;
        $this->apiExport = $apiExport;
        $this->directoryList = $directoryList;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Emarsys\Emarsys\Model\ResourceModel\Order::class);
    }

    /**
     * @param $storeId
     * @param $mode
     * @param null $exportFromDate
     * @param null $exportTillDate
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function syncOrders($storeId, $mode, $exportFromDate = null, $exportTillDate = null)
    {
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();

        //Loging functionality start
        $logsArray['job_code'] = 'order';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = __('Bulk order export started');
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = $mode;
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $storeId;
        $logsArray['website_id'] = $websiteId;
        $logId = $this->logsHelper->manualLogs($logsArray, 1);
        $logsArray['id'] = $logId;
        $logsArray['log_action'] = 'sync';
        $logsArray['action'] = 'Smart Insight';
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $errorCount = false;

        //check emarsys enabled for the website
        if ($this->emarsysHelper->getEmarsysConnectionSetting($websiteId)) {
            //check smart insight enabled for the website
            if ($this->emarsysHelper->getCheckSmartInsight($websiteId)) {
                //get configuration of catalog export method
                $apiExportEnabled = $store->getConfig(EmarsysHelper::XPATH_EMARSYS_SIEXPORT_API_ENABLED);

                //check method of data exort from admin configuration
                if ($apiExportEnabled) {
                    //export data using api
                    $logsArray['action'] = 'synced to API';
                    $this->exportOrdersDataUsingApi($storeId, $mode, $exportFromDate, $exportTillDate, $logsArray);
                } else {
                    //export data using ftp
                    $logsArray['action'] = 'synced to FTP';
                    $this->exportOrdersDataUsingFtp($storeId, $mode, $exportFromDate, $exportTillDate, $logsArray);
                }
            } else {
                $errorCount = true;
                $logsArray['emarsys_info'] = __('Smart Insight is disabled');
                $logsArray['description'] = __('Smart Insight is disabled for the store %1.', $store->getName());
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->manualLogs($logsArray);
            }
        } else {
            $errorCount = true;
            $logsArray['emarsys_info'] = __('Emarsys is Disabled for this website %1', $websiteId);
            $logsArray['description'] = __('Emarsys is Disabled for this website %1', $websiteId);
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->manualLogs($logsArray);
        }

        if ($errorCount) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = __('Smart Insight export have an error. Please check');
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $this->logsHelper->manualLogs($logsArray);
        }

        return true;
    }

    /**
     * @param $storeId
     * @param $mode
     * @param $exportFromDate
     * @param $exportTillDate
     * @param $logsArray
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function exportOrdersDataUsingApi($storeId, $mode, $exportFromDate, $exportTillDate, $logsArray)
    {
        $store = $this->storeManager->getStore($storeId);
        $errorCount = false;
        $orderSyncStatus = true;
        $cmSyncStatus = true;

        $merchantId = $store->getConfig(EmarsysHelper::XPATH_EMARSYS_SIEXPORT_MERCHANT_ID);
        $token = $store->getConfig(EmarsysHelper::XPATH_EMARSYS_SIEXPORT_TOKEN);

        $orderCollectionClone = null;
        $creditMemoCollectionClone = null;

        if ($merchantId != '' && $token != '') {
            //test connection using merchant id and token
            $this->apiExport->assignApiCredentials($merchantId, $token);
            $response = $this->apiExport->testSIExportApi($storeId);

            if ($response['result'] == 1) {
                //get directory path bases on entity
                $fileDirectory = $this->emarsysHelper->getEmarsysMediaDirectoryPath(
                    \Magento\Sales\Model\Order::ENTITY
                );

                //check existence or create directory for csv generation
                $this->emarsysHelper->checkAndCreateFolder($fileDirectory);

                //prepare order collection
                $orderCollection = $this->getOrderCollection(
                    $mode,
                    $storeId,
                    $exportFromDate,
                    $exportTillDate
                );
                //prepare credit-memo collection
                $creditMemoCollection = $this->getCreditMemoCollection(
                    $mode,
                    $storeId,
                    $exportFromDate,
                    $exportTillDate
                );

                if ($orderCollection && (is_object($orderCollection)) && ($orderCollection->getSize())) {
                    $orderCollectionClone = clone $orderCollection;
                }

                if ($creditMemoCollection
                    && (is_object($creditMemoCollection))
                    && ($creditMemoCollection->getSize())
                ) {
                    $creditMemoCollectionClone = clone $creditMemoCollection;
                }

                //check maximum record export is set
                $maxRecordExport = $store->getConfig(EmarsysHelper::XPATH_EMARSYS_SIEXPORT_MAX_RECORDS);

                if ($maxRecordExport) {
                    //export data in chunks based on max record set in admin configuration
                    if (!empty($orderCollection) && is_object($orderCollection) && $orderCollection->getSize()) {
                        try {
                            $orderSyncStatus = $this->generateBatchFilesAndSyncToEmarsys(
                                \Magento\Sales\Model\Order::ENTITY,
                                $orderCollection,
                                $mode,
                                $storeId,
                                $maxRecordExport,
                                $logsArray
                            );
                        } catch (\Exception $e) {
                            $logsArray['emarsys_info'] = __('Export Orders Data Using Api');
                            $logsArray['description'] = __($e->getMessage());
                            $logsArray['message_type'] = 'Error';
                            $this->logsHelper->manualLogs($logsArray);
                        }
                    } else {
                        $logsArray['emarsys_info'] = __('Export Orders Data Using Api');
                        $logsArray['description'] = __('No orders for store: %1', $storeId);
                        $logsArray['message_type'] = 'Notice';
                        $this->logsHelper->manualLogs($logsArray);
                    }

                    if (!empty($creditMemoCollection) && is_object($creditMemoCollection)
                        && $creditMemoCollection->getSize()
                    ) {
                        try {
                            $cmSyncStatus = $this->generateBatchFilesAndSyncToEmarsys(
                                \Magento\Sales\Model\Order::ACTION_FLAG_CREDITMEMO,
                                $creditMemoCollection,
                                $mode,
                                $storeId,
                                $maxRecordExport,
                                $logsArray
                            );
                        } catch (\Exception $e) {
                            $logsArray['emarsys_info'] = __('Export CreditMemos Data Using Api');
                            $logsArray['description'] = __($e->getMessage());
                            $logsArray['message_type'] = 'Error';
                            $this->logsHelper->manualLogs($logsArray);
                        }
                    } else {
                        $logsArray['emarsys_info'] = __('Export CreditMemos Data Using Api');
                        $logsArray['description'] = __('No CreditMemos for store: %1', $storeId);
                        $logsArray['message_type'] = 'Notice';
                        $this->logsHelper->manualLogs($logsArray);
                    }

                    if ($orderSyncStatus && $cmSyncStatus) {
                        $errorCount = false;
                    }
                } else {
                    try {
                        //export full data to emarsys
                        $outputFile = $this->getSalesCsvFileName($store->getCode());
                        $filePath = $fileDirectory . "/" . $outputFile;

                        $generateOrderCsvStatus = false;
                        if ((!empty($orderCollection) && is_object($orderCollection) && $orderCollection->getSize())
                            || (
                                !empty($creditMemoCollection) && is_object($creditMemoCollection)
                                && $creditMemoCollection->getSize()
                            )
                        ) {
                            $generateOrderCsvStatus = $this->generateOrderCsv(
                                $storeId,
                                $filePath,
                                $orderCollection,
                                $creditMemoCollection
                            );
                        }

                        //sync data to emarsys using API
                        if ($generateOrderCsvStatus) {
                            $syncResponse = $this->sendRequestToEmarsys($filePath, $outputFile, $logsArray);
                            $url = $this->emarsysHelper->getEmarsysMediaUrlPath(
                                \Magento\Sales\Model\Order::ENTITY,
                                $filePath
                            );
                            if ($syncResponse['status']) {
                                $errorCount = false;
                                $logsArray['emarsys_info'] = __('File uploaded to Emarsys successfully.');
                                $logsArray['description'] = $url . ' > ' . $outputFile;
                                $logsArray['message_type'] = 'Success';
                                $this->logsHelper->manualLogs($logsArray);
                            } else {
                                $logsArray['emarsys_info'] = __('Failed to upload file to Emarsys.');
                                $logsArray['description'] = __('Failed to upload %1 on Emarsys %2', $url, $outputFile);
                                $logsArray['message_type'] = 'Error';
                                $this->logsHelper->manualLogs($logsArray);
                            }
                        } else {
                            $logsArray['emarsys_info'] = __('Export Orders Data Using Api');
                            $logsArray['description'] = __('No orders or creditmemos for store: %1', $storeId);
                            $logsArray['message_type'] = 'Notice';
                            $this->logsHelper->manualLogs($logsArray);
                        }
                    } catch (\Exception $e) {
                        $logsArray['emarsys_info'] = __('Failed to upload file to Emarsys.');
                        $logsArray['description'] = __($e->getMessage());
                        $logsArray['message_type'] = 'Error';
                        $this->logsHelper->manualLogs($logsArray);
                    }
                    //unset file handle
                    $this->unsetFileHandle();
                }
            } else {
                //smart insight api test connection is failed
                $logsArray['status'] = 'error';
                $logsArray['messages'] = 'Smart Insight API test connection is failed. Please check credentials. '
                    . \Zend_Json::encode($response);
                $this->logsHelper->manualLogs($logsArray);
                if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addErrorMessage(__(
                        'Smart Insight API Test connection is failed. Please check credentials.'
                    ));
                }
            }
        } else {
            //invalid api credentials
            $logsArray['emarsys_info'] = __('Invalid API credentials. Either Merchant Id or Token is not present.');
            $logsArray['description'] = __(
                'Invalid API credentials. Either Merchant Id or Token is not present. Please check your settings and try again'
            );
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->manualLogs($logsArray);
            if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                $this->messageManager->addErrorMessage(__(
                    "Invalid API credentials. Either Merchant Id or Token is not present."
                    . " Please check your settings and try again !!!"
                ));
            }
        }

        try {
            //remove file after sync
            $this->emarsysHelper->removeFilesInFolder(
                $this->emarsysHelper->getEmarsysMediaDirectoryPath(\Magento\Sales\Model\Order::ENTITY)
            );
        } catch (\Exception $e) {
            $logsArray['emarsys_info'] = __('Failed to remove exported files.');
            $logsArray['description'] = __($e->getMessage());
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->manualLogs($logsArray);
        }

        if ($errorCount) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = __('Order export has an error. Please check.');
        } else {
            $this->cleanOrderQueueTable($orderCollectionClone, $creditMemoCollectionClone);
            $logsArray['status'] = 'success';
            $logsArray['messages'] = __('Order export completed');
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogs($logsArray);

        return true;
    }

    /**
     * @param $storeId
     * @param $mode
     * @param $exportFromDate
     * @param $exportTillDate
     * @param $logsArray
     * @throws \Exception
     */
    public function exportOrdersDataUsingFtp($storeId, $mode, $exportFromDate, $exportTillDate, $logsArray)
    {
        $store = $this->storeManager->getStore($storeId);
        $errorCount = true;
        $moveFile = false;

        $bulkDir = $store->getConfig(EmarsysHelper::XPATH_EMARSYS_FTP_BULK_EXPORT_DIR);

        $orderCollectionClone = null;
        $creditMemoCollectionClone = null;

        if ($this->emarsysHelper->checkFtpConnectionByStore($store)) {
            try {
                //ftp connection established successfully
                $outputFile = $this->getSalesCsvFileName($store->getCode());
                $fileDirectory = $this->emarsysHelper->getEmarsysMediaDirectoryPath(
                    \Magento\Sales\Model\Order::ENTITY
                );

                //Check and create directory for csv generation
                $this->emarsysHelper->checkAndCreateFolder($fileDirectory);
                $filePath = $fileDirectory . "/" . $outputFile;

                //prepare order collection
                /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
                $orderCollection = $this->getOrderCollection(
                    $mode,
                    $storeId,
                    $exportFromDate,
                    $exportTillDate
                );

                //Generate Sales CSV
                if ($orderCollection && (is_object($orderCollection)) && ($orderCollection->getSize())) {
                    $orderCollection->setPageSize(self::BATCH_SIZE);
                    $moveFile = true;
                    $pages = $orderCollection->getLastPageNumber();
                    for ($i = 1; $i <= $pages; $i++) {
                        //echo "$i/$pages => " . date('Y-m-d H:i:s') . "\n";
                        $orderCollection->clear();
                        $orderCollection->setPageSize(self::BATCH_SIZE)->setCurPage($i);
                        $orderCollectionClone = clone $orderCollection;
                        $this->generateOrderCsv(
                            $storeId,
                            $filePath,
                            $orderCollection,
                            false,
                            true
                        );

                        $logsArray['emarsys_info'] = __('Order\'s iteration %1 of %2', $i, $pages);
                        $logsArray['description'] = __('Order\'s iteration %1 of %2', $i, $pages);
                        $logsArray['message_type'] = 'Success';
                        $this->logsHelper->manualLogs($logsArray);
                    }
                }
            } catch (\Exception $e) {
                $logsArray['emarsys_info'] = __('Export Orders Data Using FTP');
                $logsArray['description'] = __($e->getMessage());
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->manualLogs($logsArray);
                $moveFile = false;
            }

            try {
                //prepare credit-memo collection
                /** @var  $creditMemoCollection */
                $creditMemoCollection = $this->getCreditMemoCollection(
                    $mode,
                    $storeId,
                    $exportFromDate,
                    $exportTillDate
                );

                if ($creditMemoCollection
                    && (is_object($creditMemoCollection))
                    && ($creditMemoCollection->getSize())
                ) {
                    $moveFile = true;
                    $creditMemoCollection->setPageSize(self::BATCH_SIZE);
                    $pages = $creditMemoCollection->getLastPageNumber();
                    for ($i = 1; $i <= $pages; $i++) {
                        //echo "$i/$pages => " . date('Y-m-d H:i:s') . "\n";
                        $creditMemoCollection->clear();
                        $creditMemoCollection->setPageSize(self::BATCH_SIZE)->setCurPage($i);
                        $creditMemoCollectionClone = clone $creditMemoCollection;
                        $this->generateOrderCsv(
                            $storeId,
                            $filePath,
                            false,
                            $creditMemoCollection,
                            true
                        );

                        $logsArray['emarsys_info'] = __('CreditMemo\'s iteration %1 of %2', $i, $pages);
                        $logsArray['description'] = __('CreditMemo\'s iteration %1 of %2', $i, $pages);
                        $logsArray['message_type'] = 'Success';
                        $this->logsHelper->manualLogs($logsArray);
                    }
                }
            } catch (\Exception $e) {
                $logsArray['emarsys_info'] = __('Export CreditMemos Data Using FTP');
                $logsArray['description'] = __($e->getMessage());
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->manualLogs($logsArray);
                $moveFile = $moveFile ?: false;
            }

            //CSV upload to FTP process starts
            try {
                $url = $this->emarsysHelper->getEmarsysMediaUrlPath(\Magento\Sales\Model\Order::ENTITY, $filePath);

                if ($moveFile) {
                    $remoteDirPath = $bulkDir;
                    if ($remoteDirPath == '/') {
                        $remoteFileName = $outputFile;
                    } else {
                        $remoteDirPath = rtrim($remoteDirPath, '/');
                        $remoteFileName = $remoteDirPath . "/" . $outputFile;
                    }

                    //Upload CSV to FTP
                    if ($this->emarsysHelper->moveFileToFtp($store, $filePath, $remoteFileName)) {
                        //file uploaded to FTP server successfully
                        $errorCount = false;
                        $logsArray['emarsys_info'] = __('File uploaded to FTP server successfully');
                        $logsArray['description'] = $url . ' > ' . $remoteFileName;
                        $logsArray['message_type'] = 'Success';
                        $this->logsHelper->manualLogs($logsArray);
                        if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                            $this->messageManager->addSuccessMessage(
                                __("File uploaded to FTP server successfully !!!")
                            );
                        }
                    } else {
                        //Failed to upload file on FTP server
                        $errorMessage = error_get_last();
                        $msg = isset($errorMessage['message']) ? $errorMessage['message'] : '';
                        $logsArray['emarsys_info'] = __('Failed to upload file on FTP server');
                        $logsArray['description'] = __('Failed to upload %1 on FTP server. %2', $url, $msg);
                        $logsArray['message_type'] = 'Error';
                        $this->logsHelper->manualLogs($logsArray);
                        if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                            $this->messageManager->addErrorMessage(
                                __("Failed to upload file on FTP server !!! %1", $msg)
                            );
                        }
                    }
                    //unset file handle
                    $this->unsetFileHandle();

                    //remove file after sync
                    $this->emarsysHelper->removeFilesInFolder(
                        $this->emarsysHelper->getEmarsysMediaDirectoryPath(\Magento\Sales\Model\Order::ENTITY)
                    );
                } else {
                    //no sales data found for the store
                    $logsArray['emarsys_info'] = __('No Sales Data found for the store . ' . $store->getCode());
                    $logsArray['description'] = __('No Sales Data found for the store . ' . $store->getCode());
                    $logsArray['message_type'] = 'Error';
                    $this->logsHelper->manualLogs($logsArray);
                }
            } catch (\Exception $e) {
                $logsArray['emarsys_info'] = __('Failed to Upload CSV to FTP.');
                $logsArray['description'] = __($e->getMessage());
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->manualLogs($logsArray);
            }
        } else {
            //failed to connect with FTP server with given credentials
            $logsArray['emarsys_info'] = __('Failed to connect with FTP server.');
            $logsArray['description'] = __('Failed to connect with FTP server.');
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->manualLogs($logsArray);
            if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                $this->messageManager->addErrorMessage(
                    __('"Failed to connect with FTP server. Please check your settings and try again !!!"')
                );
            }
        }

        if ($errorCount) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = __('Order export have an error. Please check');
        } else {
            //clean the queue table after SI export
            $this->cleanOrderQueueTable($orderCollectionClone, $creditMemoCollectionClone);
            $logsArray['status'] = 'success';
            $logsArray['messages'] = __('Order export completed');
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
        $this->logsHelper->manualLogs($logsArray);

        return true;
    }

    /**
     * @param $entity
     * @param $entityCollection
     * @param $mode
     * @param $storeId
     * @param $limit
     * @param $logsArray
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function generateBatchFilesAndSyncToEmarsys($entity, $entityCollection, $mode, $storeId, $limit, $logsArray)
    {
        $store = $this->storeManager->getStore($storeId);
        $messageCollector = [];
        $result = false;
        $fileDirectory = $this->emarsysHelper->getEmarsysMediaDirectoryPath(
            \Magento\Sales\Model\Order::ENTITY
        );

        //sales order operation
        $entityCollection->setPageSize($limit);
        $pages = $entityCollection->getLastPageNumber();

        for ($i = 1; $i <= $pages; $i++) {
            $entityCollection->setCurPage($i);

            //get sales csv file name
            $outputFile = $this->getSalesCsvFileName($store->getCode(), true);
            $filePath = $fileDirectory . "/" . $outputFile;

            if ($entity == \Magento\Sales\Model\Order::ENTITY) {
                $generatedOrderCsv = $this->generateOrderCsv($storeId, $filePath, $entityCollection, false);
            } else {
                $generatedOrderCsv = $this->generateOrderCsv($storeId, $filePath, false, $entityCollection);
            }

            if (!$generatedOrderCsv) {
                array_push($messageCollector, 1);
                $logsArray['emarsys_info'] = __('No records for export');
                $logsArray['description'] = 'No records for export';
                $logsArray['message_type'] = 'Notice';
                $this->logsHelper->manualLogs($logsArray);
            } else {
                $syncResponse = $this->sendRequestToEmarsys($filePath, $outputFile, $logsArray, $entity);

                $url = $this->emarsysHelper->getEmarsysMediaUrlPath(\Magento\Sales\Model\Order::ENTITY, $filePath);

                if ($syncResponse['status']) {
                    array_push($messageCollector, 1);
                    $logsArray['emarsys_info'] = __('File uploaded to Emarsys.');
                    $logsArray['description'] = $url . ' > ' . $outputFile;
                    $logsArray['message_type'] = 'Success';
                    $this->logsHelper->manualLogs($logsArray);
                } else {
                    array_push($messageCollector, 0);
                    $logsArray['emarsys_info'] = __('Failed to upload file to Emarsys.');
                    $logsArray['description'] = __('Failed to upload %1 on Emarsys %2', $url, $outputFile);
                    $logsArray['message_type'] = 'Error';
                    $this->logsHelper->manualLogs($logsArray);
                }
            }
            //unset file handle
            $this->unsetFileHandle();

            //remove file after sync
            $this->emarsysHelper->removeFilesInFolder(
                $this->emarsysHelper->getEmarsysMediaDirectoryPath(\Magento\Sales\Model\Order::ENTITY)
            );

            //clear the current page collection
            $entityCollection->clear();
        }

        //display messages
        if (!empty($messageCollector)) {
            if (!in_array(0, $messageCollector)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * @param $filePath
     * @param $csvFileName
     * @param $logsArray
     * @param null $entityName
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Http_Client_Exception
     */
    public function sendRequestToEmarsys($filePath, $csvFileName, $logsArray, $entityName = null)
    {
        $syncResult = [];

        //get sales api Url
        $apiUrl = $this->apiExport->getApiUrl(\Magento\Sales\Model\Order::ENTITY);

        //export csv to emarsys using api
        $apiExportResult = $this->apiExport->apiExport($apiUrl, $filePath);

        if ($apiExportResult['result'] == 1) {
            //successfully uploaded file to emarsys
            if (!is_null($entityName)) {
                $logsArray['emarsys_info'] = __('%1 File uploaded to Emarsys', ucfirst($entityName));
            } else {
                $logsArray['emarsys_info'] = __('File uploaded to Emarsys');
            }
            $logsArray['description'] = __(
                'File: "%1" uploaded to Emarsys. Emarasys response: "%2"',
                $csvFileName,
                $apiExportResult['resultBody']
            );
            $logsArray['action'] = 'synced to emarsys';
            $logsArray['message_type'] = 'Success';
            $this->logsHelper->manualLogs($logsArray);
            $syncResult['status'] = true;
        } else {
            //failed to upload file on emarsys
            $logsArray['emarsys_info'] = __('Failed to upload file on Emarsys');
            $logsArray['description'] = __(
                'Failed to upload file: "%1" on Emarsys. Emarasys response: "%2"',
                $csvFileName,
                $apiExportResult['resultBody']
            );
            $logsArray['action'] = 'synced to emarsys';
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->manualLogs($logsArray);
            $syncResult['status'] = false;
        }
        $syncResult['message'] = $apiExportResult['resultBody'];

        return $syncResult;
    }

    public function unsetFileHandle()
    {
        $this->handle = null;
        return;
    }

    /**
     * @param $storeId
     * @param $filePath
     * @param $orderCollection
     * @param $creditMemoCollection
     * @param bool $sameFile
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateOrderCsv($storeId, $filePath, $orderCollection, $creditMemoCollection, $sameFile = false)
    {
        $result = false;
        $store = $this->storeManager->getStore($storeId);
        $emarsysFields = $this->orderResourceModel->getEmarsysOrderFields(
            $this->emarsysHelper->getFirstStoreIdOfWebsiteByStoreId($storeId)
        );

        $guestOrderExportStatus = $store->getConfig(EmarsysHelper::XPATH_SMARTINSIGHT_EXPORTGUEST_CHECKOUTORDERS);
        $taxIncluded = $this->emarsysHelper->isIncludeTax($storeId);
        $useBaseCurrency = $this->emarsysHelper->isUseBaseCurrency($storeId);

        if (($sameFile && !$this->handle) || !$sameFile) {
            $this->handle = fopen($filePath, 'w');

            //Get Header for sales csv
            $header = $this->getSalesCsvHeader($this->emarsysHelper->getFirstStoreIdOfWebsiteByStoreId($storeId));

            //put headers in sales csv
            fputcsv($this->handle, $header);
        }

        //write data for orders into csv
        if ($orderCollection && $orderCollection->getSize()) {
            $dummySnapshot = $this->snapshotFactory->create();
            /** @var \Magento\Sales\Model\Order $order */
            foreach ($orderCollection as $order) {
                $orderId = $order->getRealOrderId();
                $createdDate = date('Y-m-d', strtotime($order->getCreatedAt()));
                $customerEmail = trim($order->getCustomerEmail());
                if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $fullyInvoiced = false;
                if ($order->getState() == \Magento\Sales\Model\Order::STATE_COMPLETE) {
                    $fullyInvoiced = true;
                }

                $parentId = null;
                $items = $this->orderItemCollectionFactory->create(['entitySnapshot' => $dummySnapshot])
                    ->addFieldToFilter('order_id', ['eq' => $order->getId()]);

                /** @var \Magento\Sales\Model\Order\Item $item */
                foreach ($items as $item) {
                    if ($item->getProductType() == Configurable::TYPE_CODE) {
                        $parentId = $item->getId();
                    }
                    if ($parentId && $item->getParentItemId() == $parentId) {
                        $parentId = null;
                        continue;
                    }
                    $values = [];
                    //set order id
                    $values[] = $orderId;
                    //set timestamp
                    $values[] = $createdDate;
                    //set customer
                    $values[] = $this->emailValidation($customerEmail);
                    //set product sku/id
                    $values[] = trim($item->getSku());

                    $rowTotal = 0;
                    $qty = 0;
                    if ($fullyInvoiced) {
                        $qty = (int)$item->getQtyInvoiced();
                        if ($taxIncluded) {
                            $rowTotal = $useBaseCurrency
                                ? $item->getBaseRowTotalInclTax()
                                : $item->getRowTotalInclTax();
                        } else {
                            $rowTotal = $useBaseCurrency
                                ? $item->getBaseRowTotal()
                                : $item->getRowTotal();
                        }
                        if (($item->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE)) {
                            $parentId = null;
                            $productOptions = $item->getProductOptions();
                            if (isset($productOptions['product_calculations'])
                                && $productOptions['product_calculations'] == 0
                            ) {
                                $rowTotal = 0;
                            }
                        }
                    }

                    if ($rowTotal) {
                        $values[] = number_format($rowTotal, 2, '.', '');
                    } else {
                        $values[] = 0;
                    }

                    //set quantity
                    $values[] = $qty;

                    foreach ($emarsysFields as $field) {
                        $emarsysOrderFieldValueOrder = trim($field['emarsys_order_field']);
                        $magentoColumnName = trim($field['magento_column_name']);
                        if (!empty($emarsysOrderFieldValueOrder)
                            && !in_array($emarsysOrderFieldValueOrder, ["'", '"'])
                            && !empty($magentoColumnName)
                        ) {
                            $values[] = $this->getValueForType(
                                $emarsysOrderFieldValueOrder,
                                $order->getData($magentoColumnName)
                            );
                        }
                    }
                    if (!($order->getCustomerIsGuest() == 1 && $guestOrderExportStatus == 0)) {
                        fputcsv($this->handle, $values);
                        $result = true;
                    }
                }
            }
        }

        //write data for credit-memo into csv
        if ($creditMemoCollection && $creditMemoCollection->getSize()) {
            $dummySnapshot = $this->snapshotFactory->create();
            /** @var \Magento\Sales\Model\Order\Creditmemo $creditMemo */
            foreach ($creditMemoCollection as $creditMemo) {
                $creditMemoOrder = $this->salesOrderFactory->create()->load($creditMemo->getOrderId());
                $orderId = $creditMemoOrder->getRealOrderId();
                $createdDate = date('Y-m-d', strtotime($creditMemo->getCreatedAt()));
                $customerEmail = trim($creditMemo->getOrder()->getCustomerEmail());
                if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $parentSku = null;
                $items = $this->creditmemoItemCollectionFactory->create(['entitySnapshot' => $dummySnapshot])
                    ->addFieldToFilter('parent_id', ['eq' => $creditMemo->getId()]);

                /** @var \Magento\Sales\Model\Order\Creditmemo\Item $item */
                foreach ($items as $item) {
                    if ($item->getOrderItem()->getProductType() == Configurable::TYPE_CODE) {
                        $parentSku = $item->getSku();
                    }
                    if ($parentSku && $item->getOrderItem()->getSku() == $parentSku
                        && $item->getOrderItem()->getProductType() != Configurable::TYPE_CODE
                    ) {
                        $parentSku = null;
                        continue;
                    }

                    $values = [];
                    //set order id
                    $values[] = $orderId;
                    //set timestamp
                    $values[] = $createdDate;
                    //set customer
                    $values[] = $this->emailValidation($customerEmail);
                    //set product sku/id
                    $values[] = trim($item->getSku());

                    $rowTotal = 0;
                    $qty = (int)$item->getQty();
                    if ($qty > 0) {
                        $qty = '-' . abs($qty);
                        if ($taxIncluded) {
                            $rowTotal = $useBaseCurrency
                                ? $item->getBaseRowTotalInclTax()
                                : $item->getRowTotalInclTax();
                        } else {
                            $rowTotal = $useBaseCurrency
                                ? $item->getBaseRowTotal()
                                : $item->getRowTotal();
                        }
                        if (($item->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE)) {
                            $parentId = null;
                            $productOptions = $item->getProductOptions();
                            if (isset($productOptions['product_calculations'])
                                && $productOptions['product_calculations'] == 0
                            ) {
                                $rowTotal = 0;
                            }
                        }
                    }

                    if ($rowTotal) {
                        $values[] = '-' . number_format(abs($rowTotal), 2, '.', '');
                    } else {
                        $values[] = 0;
                    }

                    //set quantity
                    $values[] = $qty;

                    foreach ($emarsysFields as $field) {
                        $emarsysOrderFieldValueOrder = trim($field['emarsys_order_field']);
                        $magentoColumnName = trim($field['magento_column_name']);
                        if (!empty($emarsysOrderFieldValueOrder)
                            && !in_array($emarsysOrderFieldValueOrder, ["'", '"'])
                            && !empty($magentoColumnName)
                        ) {
                            $values[] = $this->getValueForType(
                                $emarsysOrderFieldValueOrder,
                                $creditMemo->getData($magentoColumnName)
                            );
                        }
                    }
                    if (!($creditMemoOrder->getCustomerIsGuest() == 1 && $guestOrderExportStatus == 0)) {
                        fputcsv($this->handle, $values);
                        $result = true;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param $suffix
     * @return string
     */
    public function getSalesCsvFileName($suffix, $unique = false)
    {
        $uniqueId = '';
        if ($unique) {
            $uniqueId = str_replace(' ', '', microtime(true));
            $uniqueId = str_replace('.', '', $uniqueId);
            $uniqueId = '_' . $uniqueId;
        }

        return "sales_items_" . $this->date->date('YmdHis', time()) . $uniqueId . "_" . $suffix . ".csv";
    }

    /**
     * Get Sales CSV Header
     *
     * @param int $storeId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSalesCsvHeader($storeId = 0)
    {
        if (!isset($this->salesCsvHeader[$storeId])) {
            //default header
            $header = $this->emarsysHelper->getSalesOrderCsvDefaultHeader();

            //header collected from mapped order attributes
            $emarsysFields = $this->orderResourceModel->getEmarsysOrderFields($storeId);
            foreach ($emarsysFields as $field) {
                $emarsysOrderFieldValue = trim($field['emarsys_order_field']);
                if ($emarsysOrderFieldValue != '' && $emarsysOrderFieldValue != "'") {
                    if ($emarsysOrderFieldValue != 'customer') {
                        $header[] = $emarsysOrderFieldValue;
                    }
                }
            }
            $this->salesCsvHeader[$storeId] = $header;
        }
        return $this->salesCsvHeader[$storeId];
    }

    /**
     * @param $mode
     * @param $storeId
     * @param $exportFromDate
     * @param $exportTillDate
     * @return $this|array
     */
    public function getOrderCollection($mode, $storeId, $exportFromDate, $exportTillDate)
    {
        $orderCollection = [];

        if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_AUTOMATIC) {
            $orderQueueCollection = $this->orderQueueFactory->create()->getCollection()
                ->addFieldToFilter('store_id', ['eq' => $storeId])
                ->addFieldToFilter('entity_type_id', 1);

            if ($orderQueueCollection && $orderQueueCollection->getSize()) {
                $orderCollection = $this->emarsysOrderExportFactory->create()
                    ->addFieldToFilter('store_id', ['eq' => $storeId])
                    ->addFieldToFilter(
                        'entity_id',
                        ['in' => $orderQueueCollection->getColumnValues('entity_id')]
                    )
                    ->addFieldToFilter('state', ['nin' => \Magento\Sales\Model\Order::STATE_CLOSED]);
            }
        } else {
            $orderCollection = $this->emarsysOrderExportFactory->create()
                ->addFieldToFilter('store_id', ['eq' => $storeId])
                ->addOrder('created_at', 'ASC')
                ->addFieldToFilter('state', ['nin' => \Magento\Sales\Model\Order::STATE_CLOSED]);

            if (isset($exportFromDate) && isset($exportTillDate) && $exportFromDate != '' && $exportTillDate != '') {
                $toTimezone = $this->timezone->getDefaultTimezone();
                $fromDate = $this->timezone->date($exportFromDate)
                    ->setTimezone(new \DateTimeZone($toTimezone))
                    ->format('Y-m-d H:i:s');

                $toDate = $this->timezone->date($exportTillDate)
                    ->setTimezone(new \DateTimeZone($toTimezone))
                    ->format('Y-m-d H:i:s');

                $orderCollection->addFieldToFilter(
                    'created_at',
                    [
                        'from' => $fromDate,
                        'to' => $toDate,
                        'date' => true,
                    ]
                );
            }
        }

        return $orderCollection;
    }

    /**
     * @param $mode
     * @param $storeId
     * @param $exportFromDate
     * @param $exportTillDate
     * @return $this|array
     */
    public function getCreditMemoCollection($mode, $storeId, $exportFromDate, $exportTillDate)
    {
        $creditMemoCollection = [];

        if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_AUTOMATIC) {
            $creditMemoQueueCollection = $this->orderQueueFactory->create()->getCollection()
                ->addFieldToFilter('store_id', ['eq' => $storeId])
                ->addFieldToFilter('entity_type_id', 2);

            if ($creditMemoQueueCollection && $creditMemoQueueCollection->getSize()) {
                $creditMemoCollection = $this->emarsysCreditmemoExportFactory->create()
                    ->addFieldToFilter('store_id', ['eq' => $storeId])
                    ->addFieldToFilter(
                        'order_id',
                        ['in' => $creditMemoQueueCollection->getColumnValues('entity_id')]
                    );
            }
        } else {
            $creditMemoCollection = $this->emarsysCreditmemoExportFactory->create()
                ->addFieldToFilter('store_id', ['eq' => $storeId]);

            if (isset($exportFromDate) && isset($exportTillDate) && $exportFromDate != '' && $exportTillDate != '') {
                $toTimezone = $this->timezone->getDefaultTimezone();
                $fromDate = $this->timezone->date($exportFromDate)
                    ->setTimezone(new \DateTimeZone($toTimezone))
                    ->format('Y-m-d H:i:s');

                $toDate = $this->timezone->date($exportTillDate)
                    ->setTimezone(new \DateTimeZone($toTimezone))
                    ->format('Y-m-d H:i:s');

                $creditMemoCollection->addFieldToFilter(
                    'created_at',
                    [
                        'from' => $fromDate,
                        'to' => $toDate,
                        'date' => true,
                    ]
                );
            }
        }

        return $creditMemoCollection;
    }

    /**
     * clean Order Queue Table
     *
     * @param bool $orderCollection
     * @param bool $creditMemoCollection
     * @return bool
     * @throws \Exception
     */
    public function cleanOrderQueueTable($orderCollection = false, $creditMemoCollection = false)
    {
        //remove order records from queue table
        if ($orderCollection) {
            $allOrderIds = $orderCollection->getAllIds();
            $orderIdsArrays = array_chunk($allOrderIds, 100);

            foreach ($orderIdsArrays as $orderIds) {
                $orderQueueCollection = $this->orderQueueFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('entity_id', ['in' => $orderIds])
                    ->load();
                $orderQueueCollection->walk('delete');
            }
        }

        //remove credit-memo records from queue table
        if ($creditMemoCollection) {
            $allCreditmemoOrderIds = $creditMemoCollection->getColumnValues('order_id');
            $creditmemoIdsArrays = array_chunk($allCreditmemoOrderIds, 100);
            foreach ($creditmemoIdsArrays as $creditmemoIds) {
                $creditMemoQueueCollection = $this->orderQueueFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('entity_id', ['in' => $creditmemoIds])
                    ->load();
                $creditMemoQueueCollection->walk('delete');
            }
        }

        return true;
    }

    /**
     * @param $emarsysAttribute
     * @param $value
     * @return mixed
     */
    protected function getValueForType($emarsysAttribute, $value)
    {
        if (substr($emarsysAttribute, 0, 2) === "s_") {
            $value = trim(preg_replace('/\s+/', ' ', $value));
        }

        return $value;
    }

    protected function emailValidation($customerEmail)
    {
        $search = [
            '`', '!', '#', '$', '%', '^', '&', '*', '(', ')', '-', '\'', '"',
            '<', '>', '\\', '/', '?', ',', ':', ';', '|', '[', ']', '{', '}',
        ];

        return str_replace($search, '', $customerEmail);
    }
}
