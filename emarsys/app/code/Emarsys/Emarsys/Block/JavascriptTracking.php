<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Block;

use Magento\Customer\Model\Context;

/**
 * Class JavascriptTracking
 * @package Emarsys\Emarsys\Block
 */
class JavascriptTracking extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer
     */
    protected $customerResourceModel;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    
    protected $_productloader; 

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
     * @param \Magento\Checkout\Model\CartFactory $cartFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        \Magento\Checkout\Model\CartFactory $cartFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Block\Onepage\Success $orderSuccess,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        array $data = []
    ) {
    
        $this->_storeManager = $context->getStoreManager();
        $this->_checkoutSession = $checkoutSession;
        $this->cartFactory = $cartFactory;
        $this->orderFactory = $orderFactory;
        $this->orderSuccess = $orderSuccess;
        $this->_request = $request;
        $this->customerResourceModel = $customerResourceModel;
        $this->categoryFactory = $categoryFactory;
        $this->_productloader = $_productloader;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     * get Page Handle
     */
    public function getPageHandle()
    {
        return $handle = $this->_request->getFullActionName();
    }

    /**
     * @return string
     * Get logged in customer email
     */
    public function getLoggedInCustomerEmail()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        if ($customerSession->isLoggedIn()) {
            $loggedInCustomerEmail = $customerSession->getCustomer()->getEmail();
        } else {
            $loggedInCustomerEmail = '';
        }
        return $loggedInCustomerEmail;
    }

    public function getCurrentCategory()
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $category = $objectManager->get('Magento\Framework\Registry')->registry('current_category');
            if (isset($category) && $category != '') {
                $categoryName = '';
                $categoryPath = $category->getPath();
                $categoryPathIds = explode('/', $categoryPath);
                $childCats = array();
                if (count($categoryPathIds) > 2) {
                    $pathIndex = 0;
                    foreach($categoryPathIds as $categoryPathId) {
                        if($pathIndex <= 1) {
                            $pathIndex++;
                            continue;
                        }
                        $childCat = $this->categoryFactory->create()->load($categoryPathId);
                        $childCats[] = $childCat->getName();
                    }
                    $categoryName = implode(" > ", $childCats);
                }
                return $categoryName;
            } else {
                return '';
            }
        } catch (\Exception $e) {
            return '';
        }
    }

    public function getCurrentProductSku()
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $product = $objectManager->get('Magento\Framework\Registry')->registry('current_product');
            if (isset($product) && $product != '') {
                $productSku = $product->getSku();
                return $productSku;
            } else {
                return '';
            }
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @return string
     * get Page Handle From Db
     */
    public function getPageHandleStatus()
    {
        $websiteId = $this->getWebsiteId();
        $scope = 'websites';
        $path = 'web_extend/recommended_product_pages/recommended_product_pages';
        $handle = $this->getPageHandle();

        $pageHandles = [
            'cms_index_index' => 'web_extend/recommended_product_pages/recommended_home_page',
            'catalog_category_view' => 'web_extend/recommended_product_pages/recommended_category_page',
            'catalog_product_view' => 'web_extend/recommended_product_pages/recommended_product_page',
            'checkout_cart_index' => 'web_extend/recommended_product_pages/recommended_cart_page',
            'checkout_onepage_success' => 'web_extend/recommended_product_pages/recommended_order_thankyou_page',
            'catalogsearch_result_index' => 'web_extend/recommended_product_pages/recommended_nosearch_result_page'
        ];

        $pageResult = [];
        if (array_key_exists($handle, $pageHandles)) {
            $jsStatus = $this->getJsEnableStatusForAllPages();
            if ($jsStatus == 1) {
                $websiteId = $this->getWebsiteId();
                $scope = 'websites';
                $path = $pageHandles[$handle];
                $pageValue = $this->customerResourceModel->getDataFromCoreConfig($path, $scope, $websiteId);
                if ($pageValue == '' && $websiteId == 1) {
                    $pageValue = $this->customerResourceModel->getDataFromCoreConfig($path);
                }
                $pageData = explode('||', $pageValue);
                $pageResult['logic'] = $pageData[0];
                $pageResult['templateId'] = $pageData[1];
                $pageResult['status'] = 'Valid';
            } else {
                $pageResult['status'] = 'Invalid';
            }
        } else {
            $pageResult['status'] = 'Invalid';
        }
        return $pageResult;
    }

    public function getSearchResult()
    {
        $q = $this->_request->getParam('q');
        if ($q != '') {
            return $q;
        } else {
            return '';
        }
    }

    /**
     * Get Merchant Id from DB
     */
    public function getMerchantId()
    {
        $websiteId = $this->getWebsiteId();
        $scope = 'websites';
        $path = 'web_extend/javascript_tracking/merchant_id';

        $merchantId = $this->customerResourceModel->getDataFromCoreConfig($path, $scope, $websiteId);
        if ($merchantId == '' && $websiteId == 1) {
            $merchantId = $this->customerResourceModel->getDataFromCoreConfig($path);
        }
        return $merchantId;
    }

    /**
     * Get Status of Web Extended Javascript integration from DB
     */
    public function getJsEnableStatusForAllPages()
    {
        $websiteId = $this->getWebsiteId();
        $scope = 'websites';
        $path = 'web_extend/javascript_tracking/enable_javascript_integration';
        $jsStatus = $this->customerResourceModel->getDataFromCoreConfig($path, $scope, $websiteId);
        if ($jsStatus == '' && $websiteId == 1) {
            $jsStatus = $this->customerResourceModel->getDataFromCoreConfig($path);
        }
        return $jsStatus;
    }

    /**
     * @return mixed
     * Get all cart items
     */
    public function getAllCartItems()
    {
        $allItems = $this->cartFactory->create()->getQuote()->getAllItems();
        return $allItems;
    }
    
    public function getLoadProduct($id)
    {
        return $this->_productloader->create()->load($id);
    }

    public function getOrderData()
    {
        $orderId = $this->_checkoutSession->getLastOrderId();
        $order = $this->orderFactory->create()->load($orderId);
        $incrementId = $order->getIncrementId();
        $orderData = [];
        if (!empty($incrementId)) {
            $orderData['increment_id'] = $incrementId;
        }
        foreach ($order->getAllItems() as $item) {
            $qty = $item->getQtyOrdered();
            $price = $item->getPrice();
            $sku = $item->getSku();
            $orderData[] = "{item: '" . $sku . "', price: $price, quantity: $qty}";
        }
        return $orderData;
    }

    public function getProductSkuForCart()
    {
        $allItems = $this->cartFactory->create()->getQuote()->getAllItems();
        $productSku = '';
        foreach ($allItems as $item) {
            $productSku = $item->getProduct()->getSku();
            break;
        }
        if ($productSku != '') {
            return $productSku;
        } else {
            return '';
        }
    }

    /**
     * @return int
     * Get Website Id
     */
    public function getWebsiteId()
    {
        return $this->_storeManager->getStore()->getWebsiteId();
    }
}
