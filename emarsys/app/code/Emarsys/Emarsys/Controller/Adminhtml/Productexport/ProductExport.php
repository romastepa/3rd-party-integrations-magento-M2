<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Productexport;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\Timezone as TimeZone;

class ProductExport extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $productCollectionFactory;
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
    protected $productResourceModel;
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
     *
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;
    /**
     *
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;
    /**
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Catalog\Model\ProductFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\Product $productModel
     * @param DateTime $date
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Emarsys\Log\Helper\Logs $logsHelper
     * @param \Emarsys\Emarsys\Model\ResourceModel\Order $orderResourceModel
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
     * @param \Emarsys\Emarsys\Model\ResourceModel\Product $productResourceModel
     * @param TimeZone $timezone
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Eav\Model\Config $eavConfig
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Model\ProductFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product $productModel,
        DateTime $date,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Emarsys\Log\Helper\Logs $logsHelper,
        \Emarsys\Emarsys\Model\ResourceModel\Order $orderResourceModel,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        \Emarsys\Emarsys\Model\ResourceModel\Product $productResourceModel,
        TimeZone $timezone,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Eav\Model\Config $eavConfig
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->messageManager = $context->getMessageManager();
        $this->priceHelper = $priceHelper;
        $this->_objectManager = $context->getObjectManager();
        $this->orderResourceModel = $orderResourceModel;
        $this->customerResourceModel = $customerResourceModel;
        $this->productResourceModel = $productResourceModel;
        $this->productModel = $productModel;
        $this->logsHelper = $logsHelper;
        $this->timezone = $timezone;
        $this->date = $date;
        $this->request = $request;
        $this->_resourceConfig = $resourceConfig;
        $this->categoryFactory = $categoryFactory;
        $this->storeManager = $storeManager;
        $this->eavConfig =  $eavConfig;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $data = $this->request->getParams();
        $scope = 'websites';
        $scopeId = $data['storeId'];
        $websiteId = $this->storeManager->getStore($scopeId)->getWebsiteId();
        $storeCode = $this->storeManager->getStore($scopeId)->getCode();

        $logsArray['job_code'] = 'product';
        $logsArray['status'] = 'started';
        $logsArray['messages'] = 'bulk product export started';
        $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logsArray['run_mode'] = 'Manual';
        $logsArray['auto_log'] = 'Complete';
        $logsArray['store_id'] = $scopeId;
        $logsArray['website_id'] = $websiteId;
        $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
        $logId = $this->logsHelper->manualLogs($logsArray);

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
                $ftpConnection = @ftp_ssl_connect($hostname, $port);
            } else {
                $ftpConnection = @ftp_connect($hostname, $port);
            }
            $ftpLogin = @ftp_login($ftpConnection, $username, $password);
            if ($ftpLogin) {
                $mappedAttributes = $this->productResourceModel->getMappedProductAttribute($scopeId);
                $emarsysFieldNames = array();
                $magentoAttributeNames = array();
                if (isset($mappedAttributes) && count($mappedAttributes) != '') {
                    $mappingField = 0;
                    foreach ($mappedAttributes as $mapAttribute) {
                        $emarsysFieldId = $mapAttribute['emarsys_attr_code'];
                        if ($emarsysFieldId != 0) {
                            $mappingField = 1;
                        }
                        $emarsysFieldName = $this->productResourceModel->getEmarsysFieldName($scopeId, $emarsysFieldId);
                        $emarsysFieldNames[] = $emarsysFieldName;
                        $magentoAttributeNames[] = $mapAttribute['magento_attr_code'];
                    }
                    if ($mappingField == 1) {
                        $heading = $emarsysFieldNames;
                        $localFilePath = BP . "/var";
                        $outputFile = "products_" . $this->date->date('YmdHis', time()) . "_" . $storeCode . ".csv";
                        $filePath = $localFilePath . "/" . $outputFile;
                        $handle = fopen($filePath, 'w');
                        fputcsv($handle, $heading);
                        $excludeCategories = explode(',', $data['excludeCategories']);
                        $productCollection = $this->productCollectionFactory->create()->getCollection()->addAttributeToFilter('visibility', array("neq" => 1));
                        foreach ($productCollection as $product) {
                            $excludeCatFlag = 0;
                            $productData = $this->_objectManager->create('\Magento\Catalog\Model\Product')->load($product['entity_id']);
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
                            if (($data['includeBundle'] == 0 && $productType == 'bundle') || $excludeCatFlag == 1) {
                                continue;
                            }
                            
                            $attributeData = array();
                            foreach ($magentoAttributeNames as $attributeName) {
                                 
                                $attributeOption = $productData->getData($attributeName);
                                if (!is_array($attributeOption))
                                {
                                    $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeName);
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
                                            
                                            $attributeData[] = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)."catalog/product".$attributeOption;
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
                                    if ($attributeName == 'url_key') {
                                        $attributeData[] = $productData->getProductUrl();
                                    } else {
                                        $attributeData[] = '';
                                    }
                                }
                            }
                            fputcsv($handle, $attributeData);
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
                        $trackErrors = ini_get('track_errors');
                        ini_set('track_errors', 1);

                        if (@ftp_put($ftpConnection, $remoteFileName, $filePath, FTP_ASCII)) {
                            $logsArray['id'] = $logId;
                            $logsArray['emarsys_info'] = 'File uploaded to FTP server successfully';
                            $logsArray['description'] = $remoteFileName;
                            $logsArray['action'] = 'synced to emarsys';
                            $logsArray['message_type'] = 'Success';
                            $logsArray['log_action'] = 'sync';
                            $this->logsHelper->logs($logsArray);
                            $errorCount = 0;
                            $this->messageManager->addSuccess("File uploaded to FTP server successfully !!!");
                        } else {
                            $msg = $php_errormsg;
                            ini_set('track_errors', $trackErrors);
                            $logsArray['id'] = $logId;
                            $logsArray['emarsys_info'] = 'Failed to upload file on FTP server';
                            $logsArray['description'] = 'Failed to upload file on FTP server '. $msg;
                            $logsArray['action'] = 'synced to emarsys';
                            $logsArray['message_type'] = 'Error';
                            $logsArray['log_action'] = 'sync';
                            $this->logsHelper->logs($logsArray);
                            $errorCount = 1;
                            $this->messageManager->addError("Failed to upload file on FTP server !!! " .$msg);
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
                        $this->messageManager->addError("Attributes are not mapped !!!");
                    }
                } else {
                    $logsArray['id'] = $logId;
                    $logsArray['emarsys_info'] = 'Attributes are not mapped';
                    $logsArray['description'] = 'Failed to upload file on server. Attributes are not mapped';
                    $logsArray['action'] = 'synced to emarsys';
                    $logsArray['message_type'] = 'Error';
                    $logsArray['log_action'] = 'sync';
                    $this->logsHelper->logs($logsArray);
                    $errorCount = 1;
                    $this->messageManager->addError("Attributes are not mapped !!!");
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
                $this->messageManager->addError("Failed to connect with FTP server. Please check your settings and try again !!!");
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
            $this->messageManager->addError("Invalid FTP credential. Please check your settings and try again !!!");
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
        $resultRedirect = $this->resultRedirectFactory->create();
        $url = $this->getUrl("emarsys_emarsys/productexport/index/store/$scopeId");
        return $resultRedirect->setPath($url);
    }
}
