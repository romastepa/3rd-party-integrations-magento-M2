<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

use Magento\{
    Eav\Model\Config as EavConfig,
    Framework\Model\AbstractModel,
    Framework\Model\Context,
    Framework\Model\ResourceModel\AbstractResource,
    Framework\Registry,
    Framework\Message\ManagerInterface as MessageManagerInterface,
    Framework\Data\Collection\AbstractDb,
    Framework\Stdlib\DateTime\DateTime,
    Framework\App\Filesystem\DirectoryList,
    Framework\File\Csv,
    Catalog\Helper\Image,
    Catalog\Model\Product\Attribute\Source\Status,
    Catalog\Model\Product\Visibility,
    Catalog\Model\CategoryFactory,
    Catalog\Model\Product as ProductModel,
    Store\Model\StoreManagerInterface
};

use Emarsys\Emarsys\{
    Helper\Logs as EmarsysHelperLogs,
    Helper\Data as EmarsysDataHelper,
    Model\ResourceModel\Customer as EmarsysResourceModelCustomer,
    Model\ResourceModel\Product as ProductResourceModel,
    Model\ResourceModel\Emarsysproductexport as ProductExportResourceModel,
    Model\Emarsysproductexport as ProductExportModel
};

/**
 * Class Product
 * @package Emarsys\Emarsys\Model
 */
class Product extends AbstractModel
{
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
     * @var ProductResourceModel
     */
    protected $productResourceModel;

    /**
     * @var ProductExportModel
     */
    protected $productExportModel;

    /**
     * @var ProductExportResourceModel
     */
    protected $productExportResourceModel;

    /**
     * @var ProductModel
     */
    protected $productModel;

    /**
     * @var Logs
     */
    protected $logsHelper;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var EmarsysDataHelper
     */
    protected $emarsysHelper;

    /**
     * @var Csv
     */
    protected $csvWriter;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var ApiExport
     */
    protected $apiExport;

    /**
     * @var Image
     */
    protected $imageHelper;

    protected $_errorCount = false;
    protected $_mode = false;
    protected $_credentials = [];
    protected $_websites = [];
    /**
     * @var State
     */
    protected $state;

    /**
     * Product constructor.
     * @param MessageManagerInterface $messageManager
     * @param ProductModel $productModel
     * @param DateTime $date
     * @param EmarsysHelperLogs $logsHelper
     * @param EmarsysResourceModelCustomer $customerResourceModel
     * @param ProductResourceModel $productResourceModel
     * @param Emarsysproductexport $productExportModel
     * @param ProductExportResourceModel $productExportResourceModel
     * @param CategoryFactory $categoryFactory
     * @param StoreManagerInterface $storeManager
     * @param EavConfig $eavConfig
     * @param EmarsysDataHelper $emarsysHelper
     * @param Csv $csvWriter
     * @param DirectoryList $directoryList
     * @param ApiExport $apiExport
     * @param Image $imageHelper
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        MessageManagerInterface $messageManager,
        ProductModel $productModel,
        DateTime $date,
        EmarsysHelperLogs $logsHelper,
        EmarsysResourceModelCustomer $customerResourceModel,
        ProductResourceModel $productResourceModel,
        ProductExportModel $productExportModel,
        ProductExportResourceModel $productExportResourceModel,
        CategoryFactory $categoryFactory,
        StoreManagerInterface $storeManager,
        EavConfig $eavConfig,
        EmarsysDataHelper $emarsysHelper,
        Csv $csvWriter,
        DirectoryList $directoryList,
        ApiExport $apiExport,
        Image $imageHelper,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->messageManager = $messageManager;
        $this->productModel = $productModel;
        $this->date = $date;
        $this->logsHelper = $logsHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->productResourceModel = $productResourceModel;
        $this->productExportModel = $productExportModel;
        $this->productExportResourceModel = $productExportResourceModel;
        $this->categoryFactory = $categoryFactory;
        $this->storeManager = $storeManager;
        $this->eavConfig =  $eavConfig;
        $this->emarsysHelper =  $emarsysHelper;
        $this->csvWriter = $csvWriter;
        $this->directoryList = $directoryList;
        $this->apiExport = $apiExport;
        $this->imageHelper = $imageHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Emarsys\Emarsys\Model\ResourceModel\Product');
    }

    /**
     * @param string $mode
     * @param null $includeBundle
     * @param null $excludedCategories
     * @return bool
     * @throws \Exception
     */
    public function consolidatedCatalogExport($mode = EmarsysDataHelper::ENTITY_EXPORT_MODE_AUTOMATIC, $includeBundle = null, $excludedCategories = null)
    {
        set_time_limit(0);

        $result = false;

        $logsArray['job_code'] = 'product';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = __('Bulk product export started');
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = $mode;
        $logsArray['auto_log'] = 'Complete';
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['store_id'] = 0;
        $logId = $this->logsHelper->manualLogs($logsArray, 1);
        $logsArray['id'] = $logId;
        $logsArray['log_action'] = 'sync';
        $logsArray['action'] = 'synced to emarsys';

        try {
            $this->_errorCount = false;
            $this->_mode = $mode;

            $allStores = $this->storeManager->getStores();

            /** @var \Magento\Store\Model\Store $store */
            foreach ($allStores as $store) {
                $this->setCredentials($store, $logsArray);
            }

            foreach ($this->getCredentials() as $websiteId => $website) {
                $emarsysFieldNames = array();
                $magentoAttributeNames = array();

                foreach ($website as $storeId => $store) {
                    foreach ($store['mapped_attributes_names'] as $mapAttribute) {
                        $emarsysFieldId = $mapAttribute['emarsys_attr_code'];
                        $emarsysFieldNames[$storeId][] = $this->productResourceModel->getEmarsysFieldName($storeId, $emarsysFieldId);
                        $magentoAttributeNames[$storeId][] = $mapAttribute['magento_attr_code'];
                    }
                }

                $this->productExportResourceModel->truncateTable();

                $defaultStoreID = false;

                foreach ($website as $storeId => $store) {
                    $currencyStoreCode = $store['store']->getDefaultCurrencyCode();
                    if (!$defaultStoreID) {
                        $defaultStoreID = $store['store']->getWebsite()->getDefaultStore()->getId();
                    }

                    $currentPageNumber = 1;
                    $collection = $this->productExportModel->getCatalogExportProductCollection(
                        $storeId,
                        $currentPageNumber,
                        $magentoAttributeNames[$storeId],
                        $includeBundle,
                        $excludedCategories
                    );

                    $lastPageNumber = $collection->getLastPageNumber();
                    $header = $emarsysFieldNames[$storeId];

                    while ($currentPageNumber <= $lastPageNumber) {
                        if ($currentPageNumber != 1) {
                            $collection = $this->productExportModel->getCatalogExportProductCollection(
                                $storeId,
                                $currentPageNumber,
                                $magentoAttributeNames[$storeId],
                                $includeBundle,
                                $excludedCategories
                            );
                        }
                        $logsArray['emarsys_info'] = __('Processing data for store %1', $storeId);
                        $logsArray['description'] = __('%1 of %2', $currentPageNumber, $lastPageNumber);
                        $logsArray['message_type'] = 'Success';
                        $this->logsHelper->logs($logsArray);

                        $products = array();
                        foreach ($collection as $product) {
                            $catIds = $product->getCategoryIds();
                            $categoryNames = $this->getCategoryNames($catIds, $storeId);
                            $product->setStoreId($storeId);
                            $products[$product->getId()] = [
                                'entity_id' => $product->getId(),
                                'params' => serialize(array(
                                    'default_store' => ($storeId == $defaultStoreID) ? $storeId : 0,
                                    'store' => $store['store']->getCode(),
                                    'store_id' => $store['store']->getId(),
                                    'data' => $this->_getProductData($magentoAttributeNames[$storeId], $product, $categoryNames, $store['store']),
                                    'header' => $header,
                                    'currency_code' => $currencyStoreCode,
                                ))
                            ];
                        }

                        if (!empty($products)) {
                            $this->productExportResourceModel->saveBulkProducts($products);
                        }
                        $currentPageNumber++;
                    }
                    $logsArray['emarsys_info'] = __('Data for store %1 prepared', $storeId);
                    $logsArray['description'] = __('Data for store %1 prepared', $storeId);
                    $logsArray['message_type'] = 'Success';
                    $this->logsHelper->logs($logsArray);
                }

                if (!empty($store)) {
                    $logsArray['emarsys_info'] = __('Starting data uploading');
                    $logsArray['description'] = __('Starting data uploading');
                    $logsArray['message_type'] = 'Success';
                    $this->logsHelper->logs($logsArray);

                    $csvFilePath = $this->productExportModel->saveToCsv($websiteId, $logsArray);
                    $bulkDir = $store['store']->getConfig(EmarsysDataHelper::XPATH_EMARSYS_FTP_BULK_EXPORT_DIR);
                    $outputFile = $bulkDir . 'products_' . $websiteId . '.csv';
                    $uploaded = $this->moveFile($store['store'], $outputFile, $csvFilePath, $logsArray, $mode);
                    if ($uploaded) {
                        $logsArray['emarsys_info'] = __('Data for was uploaded');
                        $logsArray['description'] = __('Data for was uploaded');
                        $logsArray['message_type'] = 'Success';
                    } else {
                        $logsArray['emarsys_info'] = __('Error during data uploading');
                        $logsArray['description'] = __('Error during data uploading');
                        $logsArray['message_type'] = 'Error';
                    }
                    $this->logsHelper->logs($logsArray);
                }
            }

            if ($this->_errorCount) {
                $logsArray['status'] = 'error';
                $logsArray['messages'] = __('Product export have an error. Please check.');
            } else {
                $logsArray['status'] = 'success';
                $logsArray['messages'] = __('Product export completed');
            }
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $this->logsHelper->manualLogsUpdate($logsArray);
            $result = true;
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $logsArray['messages'] = __('consolidatedCatalogExport Exception');
            $logsArray['status'] = 'error';
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $this->logsHelper->manualLogsUpdate($logsArray);

            $logsArray['emarsys_info'] = __('consolidatedCatalogExport Exception');
            $logsArray['description'] = __("Exception " . json_encode(error_get_last()));
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->logs($logsArray);

            if ($mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                $this->messageManager->addErrorMessage(
                    __("Exception " . $msg)
                );
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @param string $outputFile
     * @param string $csvFilePath
     * @param array $logsArray
     * @param string $mode
     * @return bool
     * @throws \Zend_Http_Client_Exception
     */
    public function moveFile($store, $outputFile, $csvFilePath, $logsArray, $mode)
    {
        $result = true;
        $apiExportEnabled = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_API_ENABLED);
        $url = $this->emarsysHelper->getEmarsysMediaUrlPath('product', $csvFilePath);
        if ($apiExportEnabled) {
            $merchantId = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_MERCHANT_ID);
            //get token from admin configuration
            $token = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_TOKEN);

            //Assign API Credentials
            $this->apiExport->assignApiCredentials($merchantId, $token);

            //Get catalog API Url
            $apiUrl = $this->apiExport->getApiUrl(\Magento\Catalog\Model\Product::ENTITY);

            //Export CSV to API
            $apiExportResult = $this->apiExport->apiExport($apiUrl, $csvFilePath);
            if ($apiExportResult['result'] == 1) {
                //successfully uploaded file on Emarsys
                $logsArray['emarsys_info'] = __('File uploaded to Emarsys');
                $logsArray['description'] = __('File uploaded to Emarsys. File Name: %1. API Export result: %2', $url, $apiExportResult['resultBody']);
                $logsArray['message_type'] = 'Success';
                $this->logsHelper->logs($logsArray);
                $this->_errorCount = false;
                if ($mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addSuccessMessage(
                        __("File uploaded to Emarsys successfully !!!")
                    );
                }
            } else {
                //Failed to export file on Emarsys
                $this->_errorCount = true;
                $msg = isset($apiExportResult['resultBody']) ? $apiExportResult['resultBody'] : '';
                $logsArray['emarsys_info'] = __('Failed to upload file on Emarsys');
                $logsArray['description'] = __('Failed to upload %1 on Emarsys. %2' , $url, $msg);
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->logs($logsArray);
                if ($mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addErrorMessage(
                        __("Failed to upload file on Emarsys !!! " . $msg)
                    );
                }
                $result = false;
            }
        } else {
            if ($this->emarsysHelper->moveFileToFtp($store, $csvFilePath, $outputFile)) {
                //successfully uploaded the file on ftp
                $this->_errorCount = false;
                $logsArray['emarsys_info'] = __('File uploaded to FTP server successfully');
                $logsArray['description'] = $url . ' > ' . $outputFile;
                $logsArray['message_type'] = 'Success';
                $this->logsHelper->logs($logsArray);
                if ($mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addSuccessMessage(
                        __("File uploaded to FTP server successfully !!!")
                    );
                }
            } else {
                //failed to upload file on FTP server
                $this->_errorCount = true;
                $errorMessage = error_get_last();
                $msg = isset($errorMessage['message']) ? $errorMessage['message'] : '';
                $logsArray['emarsys_info'] = __('Failed to upload file on FTP server');
                $logsArray['description'] = __('Failed to upload %1 on FTP server %2' , $url, $msg);
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->logs($logsArray);
                if ($mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                    $this->messageManager->addErrorMessage(
                        __("Failed to upload file on FTP server !!! " . $msg)
                    );
                }
                $result = false;
            }
        }

        $this->emarsysHelper->removeFilesInFolder($this->emarsysDataHelper->getEmarsysMediaDirectoryPath('product'));

        return $result;
    }

    /**
     * Get Store Credentials
     *
     * @param null|int $websiteId
     * @param null|int $storeId
     * @return array|mixed
     */
    public function getCredentials($websiteId = null, $storeId = null)
    {
        $return = $this->_credentials;
        if (!is_null($storeId) && !is_null($websiteId)) {
            $return = null;
            if (isset($this->_credentials[$storeId])) {
                $return = $this->_credentials[$storeId];
            }
        }
        return $return;
    }

    /**
     * Set Store Credential
     *
     * @param \Magento\Store\Model\Store $store
     * @param array $logsArray
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setCredentials($store, $logsArray)
    {
        $storeId = $store->getId();
        $websiteId = $this->getWebsiteId($store);
        if (!isset($this->_credentials[$websiteId][$storeId])) {
            if ($store->getConfig(EmarsysDataHelper::XPATH_EMARSYS_ENABLED)) {
                //check feed export enabled for the website
                if ($store->getConfig(EmarsysDataHelper::XPATH_PREDICT_ENABLE_NIGHTLY_PRODUCT_FEED)) {
                    //get method of catalog export from admin configuration
                    if ($store->getConfig(EmarsysDataHelper::XPATH_PREDICT_API_ENABLED)) {
                        $merchantId = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_MERCHANT_ID);
                        $token = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_TOKEN);
                        if ($merchantId == '' || $token == '') {
                            $this->_errorCount = true;
                            $logsArray['emarsys_info'] = __('Invalid API credentials');
                            $logsArray['description'] = __('Invalid API credential. Please check your settings and try again');
                            $logsArray['message_type'] = 'Error';
                            $this->logsHelper->logs($logsArray);
                            if ($this->_mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                                $this->messageManager->addErrorMessage(
                                    __('Invalid API credential. Please check your settings and try again !!!')
                                );
                            }
                            return;
                        }
                        $logsArray['emarsys_info'] = __('Set API credentials');
                        $logsArray['description'] = __('Set API credentials for store %1', $storeId);
                        $logsArray['message_type'] = 'Success';
                        $this->logsHelper->logs($logsArray);
                    } else {
                        if (!$this->emarsysHelper->checkFtpConnectionByStore($store)) {
                            $this->_errorCount = true;
                            $logsArray['emarsys_info'] = __('Failed to connect with FTP server.');
                            $logsArray['description'] = __('Failed to connect with FTP server.');
                            $logsArray['message_type'] = 'Error';
                            $this->logsHelper->logs($logsArray);
                            if ($this->_mode == EmarsysDataHelper::ENTITY_EXPORT_MODE_MANUAL) {
                                $this->messageManager->addErrorMessage(
                                    __("Failed to connect with FTP server. Please check your settings and try again !!!")
                                );
                            }
                            return;
                        }
                        $logsArray['emarsys_info'] = __('Set FTP credentials');
                        $logsArray['description'] = __('Set FTP credentials for store %1', $storeId);
                        $logsArray['message_type'] = 'Success';
                        $this->logsHelper->logs($logsArray);
                    }

                    $mappedAttributes = $this->productResourceModel->getMappedProductAttribute($storeId);
                    $mappingField = 0;
                    foreach ($mappedAttributes as $mapAttribute) {
                        $emarsysFieldId = $mapAttribute['emarsys_attr_code'];
                        if ($emarsysFieldId != 0) {
                            $mappingField = 1;
                        }
                    }
                    if ($mappingField) {
                        $this->_credentials[$websiteId][$storeId]['store'] = $store;
                        $this->_credentials[$websiteId][$storeId]['mapped_attributes_names'] = $mappedAttributes;
                    }
                } else {
                    $this->_errorCount = true;
                    $logsArray['emarsys_info'] = __('Catalog Feed Export is Disabled');
                    $logsArray['description'] = __('Catalog Feed Export is Disabled for the store %1.', $store->getName());
                    $logsArray['message_type'] = 'Error';
                    $this->logsHelper->logs($logsArray);
                }
            } else {
                $this->_errorCount = true;
                $logsArray['emarsys_info'] = __('Emarsys is disabled');
                $logsArray['description'] = __('Emarsys is disabled for the website %1', $websiteId);
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->logs($logsArray);
            }
        }
    }

    /**
     * Get Grouped WebsiteId
     *
     * @param \Magento\Store\Model\Store $store
     * @return int
     */
    public function getWebsiteId($store)
    {
        $apiUserName = $store->getConfig(EmarsysDataHelper::XPATH_EMARSYS_API_USER);
        if (!isset($this->_websites[$apiUserName])) {
            $this->_websites[$apiUserName] = $store->getWebsiteId();
        }

        return $this->_websites[$apiUserName];
    }

    /**
     * Get Category Names
     *
     * @param $catIds
     * @param $storeId
     * @return array
     */
    public function getCategoryNames($catIds, $storeId)
    {
        $categoryNames = [];
        foreach ($catIds as $catId) {
            $cateData = $this->categoryFactory->create()
                ->setStoreId($storeId)
                ->load($catId);
            $categoryPath = $cateData->getPath();
            $categoryPathIds = explode('/', $categoryPath);
            $childCats = [];
            if (count($categoryPathIds) > 2) {
                $pathIndex = 0;
                foreach ($categoryPathIds as $categoryPathId) {
                    if ($pathIndex <= 1) {
                        $pathIndex++;
                        continue;
                    }
                    $childCateData = $this->categoryFactory->create()
                        ->setStoreId($storeId)
                        ->load($categoryPathId);
                    $childCats[] = $childCateData->getName();
                }
                $categoryNames[] = implode(" > ", $childCats);
            }
        }

        return $categoryNames;
    }

    /**
     * @param $magentoAttributeNames
     * @param \Magento\Catalog\Model\Product $productObject
     * @param $categoryNames
     * @param \Magento\Store\Model\Store $store
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getProductData($magentoAttributeNames, $productObject, $categoryNames, $store)
    {
        $attributeData = [];
        foreach ($magentoAttributeNames as $attributeName) {
            $attributeOption = $productObject->getData($attributeName);
            if (!is_array($attributeOption)) {
                $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeName);
                if ($attribute->getFrontendInput() == 'boolean'
                    || $attribute->getFrontendInput() == 'select'
                    || $attribute->getFrontendInput() == 'multiselect'
                ) {
                    $attributeOption = $productObject->getAttributeText($attributeName);
                }
            }
            if (isset($attributeOption) && $attributeOption != '') {
                switch ($attributeName) {
                    case 'quantity_and_stock_status':
                        $status = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_AVAILABILITY_STATUS)
                            ? ($productObject->getStatus() == Status::STATUS_ENABLED)
                            : true
                        ;
                        $inStock = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_AVAILABILITY_IN_STOCK)
                            ? ($productObject->getData('inventory_in_stock') == 1)
                            : true
                        ;
                        $visibility = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_AVAILABILITY_VISIBILITY)
                            ? ($productObject->getVisibility() != Visibility::VISIBILITY_NOT_VISIBLE)
                            : true
                        ;

                        if ($status && $inStock && $visibility) {
                            $attributeData[] = 'TRUE';
                        } else {
                            $attributeData[] = 'FALSE';
                        }
                        break;
                    case 'category_ids':
                        $attributeData[] = implode('|', $categoryNames);
                        break;
                    case is_array($attributeOption):
                        $attributeData[] = implode(',', $attributeOption);
                        break;
                    case 'image':
                        /** @var \Magento\Catalog\Helper\Image $helper */
                        $url = $this->imageHelper
                            ->init($productObject, 'product_base_image')
                            ->setImageFile($attributeOption)
                            ->getUrl();
                        $attributeData[] = $url;
                        break;
                    case 'url_key':
                        $attributeData[] = $store->getBaseUrl() . $productObject->getRequestPath();
                        break;
                    default:
                        $attributeData[] = $attributeOption;
                        break;

                }
            } else {
                switch ($attributeName) {
                    case 'image':
                        $url = $this->imageHelper
                            ->init($productObject, 'product_base_image')
                            ->setImageFile($attributeOption)
                            ->getUrl();
                        $attributeData[] = $url;
                        break;
                    case 'url_key':
                        $attributeData[] = $store->getBaseUrl() . $productObject->getRequestPath();
                        break;
                    default:
                        $attributeData[] = $attributeOption;
                        break;
                }
            }
        }

        return $attributeData;
    }
}
