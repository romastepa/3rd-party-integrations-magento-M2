<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Emarsys\Emarsys\Helper\Logs as EmarsysHelperLogs;
use Emarsys\Emarsys\Model\Emarsysproductexport as ProductExportModel;
use Emarsys\Emarsys\Model\ResourceModel\Customer as EmarsysResourceModelCustomer;
use Emarsys\Emarsys\Model\ResourceModel\Emarsysproductexport as ProductExportResourceModel;
use Emarsys\Emarsys\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Bundle\Model\Product\Type as TypeBundle;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as TypeConfigurable;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\File\Csv;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Serialize as Serializer;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\GroupedProduct\Model\Product\Type\Grouped as TypeGrouped;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

class ProductAsync extends AbstractModel
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
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var Csv
     */
    protected $csvWriter;

    /**
     * @var Serializer
     */
    protected $serializer;

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

    /**
     * @var Emulation
     */
    protected $appEmulation;

    protected $_errorCount = false;
    protected $_websites = [];
    protected $_attributeCache = [];
    protected $_categoryNames = [];
    protected $_mapHeader = ['item'];
    protected $_processedStores = [];
    protected $_parentProducts = [];
    protected $_productTypeInstance = null;

    /**
     * @var State
     */
    protected $state;
    /**
     * @var TypeConfigurable
     */
    protected $typeConfigurable;
    /**
     * @var TypeBundle
     */
    protected $typeBundle;
    /**
     * @var TypeGrouped
     */
    protected $typeGrouped;

    /**
     * Product constructor.
     *
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
     * @param EmarsysHelper $emarsysHelper
     * @param Csv $csvWriter
     * @param Serializer $serializer
     * @param DirectoryList $directoryList
     * @param ApiExport $apiExport
     * @param Image $imageHelper
     * @param Emulation $appEmulation
     * @param TypeConfigurable $typeConfigurable
     * @param TypeBundle $typeBundle
     * @param TypeGrouped $typeGrouped
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
        EmarsysHelper $emarsysHelper,
        Csv $csvWriter,
        Serializer $serializer,
        DirectoryList $directoryList,
        ApiExport $apiExport,
        Image $imageHelper,
        Emulation $appEmulation,
        TypeConfigurable $typeConfigurable,
        TypeBundle $typeBundle,
        TypeGrouped $typeGrouped,
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
        $this->eavConfig = $eavConfig;
        $this->emarsysHelper = $emarsysHelper;
        $this->csvWriter = $csvWriter;
        $this->serializer = $serializer;
        $this->directoryList = $directoryList;
        $this->apiExport = $apiExport;
        $this->imageHelper = $imageHelper;
        $this->appEmulation = $appEmulation;
        $this->typeConfigurable = $typeConfigurable;
        $this->typeBundle = $typeBundle;
        $this->typeGrouped = $typeGrouped;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Emarsys\Emarsys\Model\ResourceModel\Product::class);
    }

    /**
     * @param int $pid
     * @param array $website
     * @param \Emarsys\Emarsys\Model\ProductExportQueue $page
     * @param null | bool $includeBundle
     * @param array $logsArray
     * @return array
     * @throws \Exception
     */
    public function consolidatedCatalogExport(
        $pid,
        $website,
        $page,
        $includeBundle = null,
        $logsArray
    ) {
        set_time_limit(0);

        $result = false;

        try {
            $this->_errorCount = false;

            $emarsysFieldNames = $magentoAttributeNames = [];

            foreach ($website as $storeId => $store) {
                foreach ($store['mapped_attributes_names'] as $mapAttribute) {
                    $emarsysFieldId = $mapAttribute['emarsys_attr_code'];
                    $emarsysFieldNames[$storeId][] = $this->productResourceModel->getEmarsysFieldName(
                        $this->emarsysHelper->getFirstStoreIdOfWebsiteByStoreId($storeId),
                        $emarsysFieldId
                    );
                    $magentoAttributeNames[$storeId][] = $mapAttribute['magento_attr_code'];
                }
            }

            $defaultStoreID = false;
            $this->_mapHeader = ['item', 'group_id'];
            $this->_processedStores = [];
            foreach ($website as $storeId => $store) {
                $this->appEmulation->startEnvironmentEmulation(
                    $storeId,
                    Area::AREA_FRONTEND,
                    true
                );
                $currencyStoreCode = $store['store']->getDefaultCurrencyCode();
                if (!$defaultStoreID) {
                    $defaultStoreID = $store['store']->getWebsite()->getDefaultStore()->getId();
                }

                $excludedCategories = $store['store']->getConfig(EmarsysHelper::XPATH_PREDICT_EXCLUDED_CATEGORIES);

                if ($excludedCategories) {
                    $excludedCategories = explode(
                        ',',
                        str_replace(' ', '', $excludedCategories)
                    );
                }

                if (empty($excludedCategories)) {
                    $excludedCategories = [];
                }

                $currentPageNumber = 1;
                $collection = $this->productExportModel->getCatalogExportProductCollection(
                    $storeId,
                    $currentPageNumber,
                    $magentoAttributeNames[$storeId],
                    $includeBundle,
                    $excludedCategories,
                    $page
                );

                $lastPageNumber = $collection->getLastPageNumber();
                $headerOld = $emarsysFieldNames[$storeId];
                $header = [];
                foreach ($headerOld as $el) {
                    $header[] = $el;
                    if ($el == 'item') {
                        $header[] = 'group_id';
                    }
                }
                $this->_categoryNames = [];
                $this->_parentProducts = [];

                $this->prepareHeader(
                    $store['store']->getCode(),
                    $header,
                    $currencyStoreCode,
                    ($storeId == $defaultStoreID) ? $storeId : 0
                );

                while ($currentPageNumber <= $lastPageNumber) {
                    if ($currentPageNumber != 1) {
                        $collection = $this->productExportModel->getCatalogExportProductCollection(
                            $storeId,
                            $currentPageNumber,
                            $magentoAttributeNames[$storeId],
                            $includeBundle,
                            $excludedCategories,
                            $page
                        );
                    }
                    $logsArray['emarsys_info'] = __('%1 - Processing data for store %2', $pid, $storeId);
                    $logsArray['description'] = __('%1 of %2', $currentPageNumber, $lastPageNumber);
                    $logsArray['message_type'] = 'Success';
                    $this->logsHelper->manualLogs($logsArray);

                    $products = [];
                    foreach ($collection as $product) {
                        $collection->getSelect()->query()->closeCursor();
                        echo '.';
                        $catIds = $product->getCategoryIds();
                        $categoryNames = $this->getCategoryNames($catIds, $storeId, $excludedCategories);
                        $product->setStoreId($storeId);
                        $products[$product->getId()] = [
                            'entity_id' => $product->getId(),
                            'params' => $this->serializer->serialize([
                                'default_store' => ($storeId == $defaultStoreID) ? $storeId : 0,
                                'store' => $store['store']->getCode(),
                                'store_id' => $store['store']->getId(),
                                'data' => $this->_getProductData(
                                    $magentoAttributeNames[$storeId],
                                    $product,
                                    $categoryNames,
                                    $store['store'],
                                    $collection,
                                    $logsArray
                                ),
                                'header' => $header,
                                'currency_code' => $currencyStoreCode,
                            ]),
                        ];
                    }

                    if (!empty($products)) {
                        $this->productExportResourceModel->saveBulkProducts($products);
                    }
                    $currentPageNumber++;
                }
                $logsArray['emarsys_info'] = __('%1 - Data for store %2 prepared', $pid, $storeId);
                $logsArray['description'] = __('Data for store %1 prepared', $storeId);
                $logsArray['message_type'] = 'Success';
                $this->logsHelper->manualLogs($logsArray);
                $this->appEmulation->stopEnvironmentEmulation();
            }

            if ($this->_errorCount) {
                $logsArray['status'] = 'error';
                $logsArray['messages'] = __('Product export have an error. Please check.');
            } else {
                $logsArray['status'] = 'success';
                $logsArray['messages'] = __('Product export completed');
            }
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $this->logsHelper->manualLogs($logsArray);
            $result = true;
        } catch (\Exception $e) {
            $logsArray['messages'] = __('consolidatedCatalogExport Exception');
            $logsArray['status'] = 'error';
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $this->logsHelper->manualLogs($logsArray);

            $logsArray['emarsys_info'] = __('%1 consolidatedCatalogExport Exception', $pid);
            $logsArray['description'] = __("Exception %1", $e->getMessage());
            $logsArray['message_type'] = 'Error';
            $this->logsHelper->manualLogs($logsArray);
        }


        return [$this->_mapHeader, $this->_processedStores];
    }

    /**
     * Prepare Global Header and Mapping
     *
     * @param string $storeCode
     * @param array $header
     * @param string $currencyCode
     * @param bool|int $isDefault
     * @return mixed
     */
    public function prepareHeader($storeCode, $header, $currencyCode, $isDefault = false)
    {
        if (!array_key_exists($storeCode, $this->_processedStores)) {
            // $this->_processedStores[$storeCode] = array(oldKey => newKey);
            $this->_processedStores[$storeCode] = [];
            foreach ($header as $key => &$value) {
                if (strtolower($value) == 'item') {
                    unset($header[$key]);
                    $this->_processedStores[$storeCode][$key] = 0;
                    continue;
                }
                if (strtolower($value) == 'group_id') {
                    unset($header[$key]);
                    $this->_processedStores[$storeCode][$key] = 1;
                    continue;
                }

                if (!$isDefault) {
                    if (strtolower($value) == 'price' || strtolower($value) == 'msrp') {
                        $newValue = $value . '_' . $currencyCode;
                        $existingKey = array_search($newValue, $this->_mapHeader);
                        if ($existingKey) {
                            unset($header[$key]);
                            $this->_processedStores[$storeCode][$key] = $existingKey;
                            continue;
                        } else {
                            $value = $newValue;
                        }
                    } else {
                        $value = $value . '_' . $storeCode;
                    }
                }
            }
            $headers = array_flip($header);

            foreach ($headers as $head => $key) {
                $this->_mapHeader[] = $head;
                $renewedHead = array_keys($this->_mapHeader);
                $lastElementKey = array_pop($renewedHead);
                $this->_processedStores[$storeCode][$key] = $lastElementKey;
            }
        }

        return $this->_processedStores[$storeCode];
    }

    /**
     * Get Category Names
     *
     * @param  $catIds
     * @param  $storeId
     * @param  $excludedCategories
     * @return array
     */
    public function getCategoryNames($catIds, $storeId, $excludedCategories = [])
    {
        $key = $storeId . '-' . $this->serializer->serialize($catIds);
        if (!isset($this->_categoryNames[$key])) {
            $this->_categoryNames[$key] = [];
            foreach ($catIds as $catId) {
                if (in_array($catId, $excludedCategories)) {
                    continue;
                }
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
                    $this->_categoryNames[$key][] = implode(" > ", $childCats);
                }
            }
        }

        return $this->_categoryNames[$key];
    }

    /**
     * @param  $magentoAttributeNames
     * @param \Magento\Catalog\Model\Product $productObject
     * @param  $categoryNames
     * @param \Magento\Store\Model\Store $store
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param array $logsArray
     * @return array
     * @throws \Exception
     */
    protected function _getProductData(
        $magentoAttributeNames,
        $productObject,
        $categoryNames,
        $store,
        $collection,
        $logsArray
    ) {
        $attributeData = $parentProducts = [];
        foreach ($magentoAttributeNames as $attributeCode) {
            try {
                $attributeOption = $productObject->getData($attributeCode);
                if (!is_array($attributeOption)) {
                    $attribute = $this->getEavAttribute($attributeCode);
                    if ($attribute->getFrontendInput() == 'boolean'
                        || $attribute->getFrontendInput() == 'select'
                        || $attribute->getFrontendInput() == 'multiselect'
                    ) {
                        $attributeOption = $productObject->getAttributeText($attributeCode);
                    }
                }
                switch ($attributeCode) {
                    case 'sku':
                        if ($productObject->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE) {
                            $attributeData[] = $attributeOption;
                            $parentProducts = $this->typeConfigurable->getParentIdsByChild($productObject->getId());
                            $this->_productTypeInstance = $this->typeConfigurable;
                            if (empty($parentProducts)) {
                                $parentProducts = $this->typeBundle->getParentIdsByChild($productObject->getId());
                                $this->_productTypeInstance = $this->typeBundle;
                                if (empty($parentProducts)) {
                                    $parentProducts = $this->typeGrouped->getParentIdsByChild($productObject->getId());
                                    $this->_productTypeInstance = $this->typeGrouped;
                                }
                            }
                            if (!empty($parentProducts)) {
                                $parentId = current($parentProducts);
                                $parentProduct = $collection->getItemById($parentId);
                                if (!$parentProduct) {
                                    if (!isset($this->_parentProducts[$parentId])) {
                                        $this->productModel->setTypeInstance($this->_productTypeInstance);
                                        $this->_parentProducts[$parentId] = $this->productModel->load($parentId);
                                        $parentProduct = $this->_parentProducts[$parentId];
                                    } else {
                                        $parentProduct = $this->_parentProducts[$parentId];
                                    }
                                }
                                if ($parentProduct) {
                                    $parentProduct->setStoreId($store->getId());
                                    $attributeData[] = $parentProduct->getSku();
                                }
                            } else {
                                $attributeData[] = $attributeOption;
                            }
                        } else {
                            $attributeData[] = 'g/' . $attributeOption;
                            $attributeData[] = $attributeOption;
                        }
                        break;
                    case 'quantity_and_stock_status':
                        $status = ($store->getConfig(EmarsysHelper::XPATH_PREDICT_AVAILABILITY_STATUS) == 1)
                            ? ($productObject->getStatus() == Status::STATUS_ENABLED)
                            : true;
                        $inStock = ($store->getConfig(EmarsysHelper::XPATH_PREDICT_AVAILABILITY_IN_STOCK) == 1)
                            ? $productObject->isAvailable()
                            : true;
                        $visibility =
                            ($store->getConfig(EmarsysHelper::XPATH_PREDICT_AVAILABILITY_VISIBILITY) == 1)
                                ? ($productObject->getVisibility() != Visibility::VISIBILITY_NOT_VISIBLE)
                                : true;

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
                        /**
                         * @var \Magento\Catalog\Helper\Image $helper
                         */
                        $url = $this->imageHelper
                            ->init($productObject, 'product_base_image')
                            ->setImageFile($attributeOption)
                            ->getUrl();
                        $attributeData[] = $url;
                        break;
                    case 'url_key':
                        $url = $productObject->getRequestPath();
                        if (empty($url)) {
                            $url = 'catalog/product/view/id/' . $productObject->getId();
                        }
                        $url = $store->getBaseUrl() . $url;
                        if ($productObject->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE) {
                            $parentProducts = $this->typeConfigurable->getParentIdsByChild($productObject->getId());
                            $this->_productTypeInstance = $this->typeConfigurable;
                            if (empty($parentProducts)) {
                                $parentProducts = $this->typeBundle->getParentIdsByChild($productObject->getId());
                                $this->_productTypeInstance = $this->typeBundle;
                                if (empty($parentProducts)) {
                                    $parentProducts = $this->typeGrouped->getParentIdsByChild($productObject->getId());
                                    $this->_productTypeInstance = $this->typeGrouped;
                                }
                            }
                            if (!empty($parentProducts)) {
                                $parentId = current($parentProducts);
                                $parentProduct = $collection->getItemById($parentId);
                                if (!$parentProduct) {
                                    if (!isset($this->_parentProducts[$parentId])) {
                                        $this->productModel->setTypeInstance($this->_productTypeInstance);
                                        $this->_parentProducts[$parentId] = $this->productModel->load($parentId);
                                        $parentProduct = $this->_parentProducts[$parentId];
                                    } else {
                                        $parentProduct = $this->_parentProducts[$parentId];
                                    }
                                }
                                if ($parentProduct) {
                                    $parentProduct->setStoreId($store->getId());
                                    $url = $parentProduct->getRequestPath();
                                    if (empty($url)) {
                                        $url = 'catalog/product/view/id/' . $parentProduct->getId();
                                    }
                                    $url = $store->getBaseUrl() . $url;
                                }
                            }
                        }
                        $attributeData[] = $url;
                        break;
                    case 'price':
                        $price = $productObject->getMinimalPrice();
                        if ($price <= 0.0001) {
                            $price = $attributeOption;
                        }
                        $attributeData[] = number_format($price, 2, '.', '');
                        break;
                    default:
                        if ($attributeOption instanceof \Magento\Framework\Phrase) {
                            $attributeOption = $attributeOption->getText();
                        }
                        $attributeData[] = $attributeOption;
                        break;

                }
            } catch (\Exception $e) {
                $attributeData[] = '';
                $logsArray['emarsys_info'] = __('consolidatedCatalogExport _getProductData Exception');
                $logsArray['description'] = __('%1: %2', $attributeCode, $e->getMessage());
                $logsArray['message_type'] = 'Error';
                $this->logsHelper->logs($logsArray);
            }
        }

        return $attributeData;
    }

    /**
     * @param  $attributeCode
     * @return AbstractAttribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getEavAttribute($attributeCode)
    {
        if (!isset($this->_attributeCache[$attributeCode])) {
            $this->_attributeCache[$attributeCode] = $this->eavConfig->getAttribute(
                'catalog_product',
                $attributeCode
            );
        }
        return $this->_attributeCache[$attributeCode];
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function truncateExportTable()
    {
        return $this->productExportResourceModel->truncateTable();
    }

    /**
     * Gets Size of Product Collection And Max Product Id
     *
     * @return [int, int, int]
     */
    public function getSizeAndMaxAndMinId()
    {
        return $this->productExportModel->getSizeAndMaxAndMinId();
    }
}
