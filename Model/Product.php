<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Catalog\Model\ProductFactory as ProductModelFactory;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Emarsys\Emarsys\Helper\Logs as EmarsysHelperLogs;
use Emarsys\Emarsys\Model\ResourceModel\Customer as EmarsysResourceModelCustomer;
use Emarsys\Emarsys\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Eav\Model\Config as EavConfig;
use Emarsys\Emarsys\Helper\Data as EmarsysDataHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Csv;
use Magento\Store\Model\ScopeInterface;

use Emarsys\Emarsys\Model\Emarsysproductexport as ProductExportModel;
use Emarsys\Emarsys\Model\ResourceModel\Emarsysproductexport as ProductExportResourceModel;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Product
 * @package Emarsys\Emarsys\Model
 */
class Product extends AbstractModel
{
    /**
     * @var ProductModelFactory
     */
    protected $productCollectionFactory;

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
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

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

    protected $_errorCount = false;
    protected $_mode = false;
    protected $_credentials = [];
    protected $_websites = [];

    /**
     * Product constructor.
     * @param Context $context
     * @param Registry $registry
     * @param MessageManagerInterface $messageManager
     * @param ProductModelFactory $productCollectionFactory
     * @param ProductModel $productModel
     * @param DateTime $date
     * @param EmarsysHelperLogs $logsHelper
     * @param EmarsysResourceModelCustomer $customerResourceModel
     * @param ProductResourceModel $productResourceModel
     * @param ProductExportModel $productExportModel
     * @param ProductExportResourceModel $productExportResourceModel
     * @param CategoryFactory $categoryFactory
     * @param StoreManagerInterface $storeManager
     * @param EavConfig $eavConfig
     * @param EmarsysDataHelper $emarsysHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Csv $csvWriter
     * @param DirectoryList $directoryList
     * @param ApiExport $apiExport
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        MessageManagerInterface $messageManager,
        ProductModelFactory $productCollectionFactory,
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
        ScopeConfigInterface $scopeConfig,
        Csv $csvWriter,
        DirectoryList $directoryList,
        ApiExport $apiExport,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->productResourceModel = $productResourceModel;
        $this->productExportModel = $productExportModel;
        $this->productExportResourceModel = $productExportResourceModel;
        $this->productModel = $productModel;
        $this->logsHelper = $logsHelper;
        $this->date = $date;
        $this->categoryFactory = $categoryFactory;
        $this->eavConfig =  $eavConfig;
        $this->emarsysHelper =  $emarsysHelper;
        $this->scopeConfig = $scopeConfig;
        $this->csvWriter = $csvWriter;
        $this->directoryList = $directoryList;
        $this->apiExport = $apiExport;
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

                    list($csvFilePath, $outputFile) = $this->productExportModel->saveToCsv($websiteId, $logsArray);
                    $bulkDir = $this->customerResourceModel->getDataFromCoreConfig(
                        EmarsysDataHelper::XPATH_EMARSYS_FTP_BULK_EXPORT_DIR, 
                        ScopeInterface::SCOPE_WEBSITES, 
                        $websiteId
                    );

                    $outputFile = $bulkDir . $outputFile;
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
            $logsArray['description'] = __("Exception " . $msg);
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
     */
    public function moveFile($store, $outputFile, $csvFilePath, $logsArray, $mode)
    {
        $result = true;
        $apiExportEnabled = $store->getConfig(EmarsysDataHelper::XPATH_PREDICT_API_ENABLED);

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
                $logsArray['description'] = __('File uploaded to Emarsys. File Name: %1. API Export result: %2', $csvFilePath, $apiExportResult['resultBody']);
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
                $logsArray['description'] = __('Failed to upload file on Emarsys. %1' , $msg);
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
                $logsArray['description'] = $outputFile;
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
                $logsArray['description'] = __('Failed to upload file on FTP server %1' , $msg);
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
        if (file_exists($csvFilePath)) {
            unlink($csvFilePath);
        }

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
     * @param \Magento\Catalog\Model\Product $productData
     * @param $categoryNames
     * @param \Magento\Store\Model\Store $store
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getProductData($magentoAttributeNames, $productData, $categoryNames, $store)
    {
        $attributeData = [];
        foreach ($magentoAttributeNames as $attributeName) {
            $attributeOption = $productData->getData($attributeName);
            if (!is_array($attributeOption)) {
                $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeName);
                if ($attribute->getFrontendInput() == 'boolean' || $attribute->getFrontendInput() == 'select'  || $attribute->getFrontendInput() == 'multiselect' ) {
                    $attributeOption = $productData->getAttributeText($attributeName);
                }
            }
            if (isset($attributeOption) && $attributeOption != '') {
                if ($attributeName == 'quantity_and_stock_status') {
                    if ($productData->getData('inventory_in_stock') == 1
                        && $productData->getData('visibility') != 1
                    ) {
                        $attributeData[] = 'TRUE';
                    } else {
                        $attributeData[] = 'FALSE';
                    }
                } elseif ($attributeName == 'category_ids') {
                    $attributeData[] = implode('|', $categoryNames);
                } elseif (is_array($attributeOption)) {
                    $attributeData[] = implode(',', $attributeOption);
                } elseif ($attributeName == 'image') {
                    $mediaUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                    $imgUrl = $mediaUrl . 'catalog/product' . $attributeOption;
                    $attributeData[] = str_replace('pub/', '', $imgUrl);
                } elseif ($attributeName == 'url_key') {
                    $attributeData[] = $store->getBaseUrl() . $productData->getRequestPath();
                } else {
                    $attributeData[] = $attributeOption;
                }
            } else {
                if ($attributeName == 'url_key') {
                    $attributeData[] = $store->getBaseUrl() . $productData->getRequestPath();
                } elseif ($attributeName == 'image') {
                    $mediaUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                    $imgUrl = $mediaUrl . 'catalog/product' . $attributeOption;
                    $attributeData[] = str_replace('pub/', '', $imgUrl);
                } else {
                    $attributeData[] = '';
                }
            }
        }

        return $attributeData;
    }
}
