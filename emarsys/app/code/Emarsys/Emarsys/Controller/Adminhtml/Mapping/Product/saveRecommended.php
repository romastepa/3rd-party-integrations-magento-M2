<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
namespace Emarsys\Emarsys\Controller\Adminhtml\Mapping\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class SaveRecommended extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Emarsys\Emarsys\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Product
     */
    protected $resourceModelProduct;

    /**
     * @var  \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @param Context $context
     * @param \Magento\Backend\Model\Session $session
     * @param \Emarsys\Emarsys\Model\ProductFactory $productFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Product $resourceModelProduct
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $eavAttribute,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Emarsys\Emarsys\Model\ProductFactory $productFactory,
        \Emarsys\Emarsys\Helper\Data $emarsysHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Log\Model\Logs $emarsysLogs,
        \Emarsys\Log\Helper\Logs $logHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Emarsys\Emarsys\Model\ResourceModel\Product $resourceModelProduct,
        PageFactory $resultPageFactory
    ) {
    
        parent::__construct($context);
        $this->eavAttribute = $eavAttribute;
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->_storeManager = $storeManager;
        $this->date = $date;
        $this->logHelper = $logHelper;
        $this->emarsysHelper = $emarsysHelper;
        $this->emarsysLogs = $emarsysLogs;
        $this->productFactory = $productFactory;
        $this->resourceModelProduct = $resourceModelProduct;
        $this->scopeConfigInterface = $scopeConfigInterface;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('store')) {
            $storeId = $this->getRequest()->getParam('store');
        }else{
            $storeId = $this->emarsysHelper->getFirstStoreId();
        }
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        try {
            $recommendedArray = array();
            $logsArray['job_code'] = 'Product Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Running Update Schema';
            $logsArray['description'] = 'Started Recommended Mapping';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['website_id'] = $websiteId;
            $logsArray['store_id'] = $storeId;
            $logId = $this->logHelper->manualLogs($logsArray);
            $model = $this->productFactory->create();
            /**
             * Truncating the Mapping Table first
             */
            $this->resourceModelProduct->truncateMappingTable($storeId);
            /**
             *Here We need set the recommended attribute values
             */
            $data = $this->resourceModelProduct->getProductAttributeLabelId();

            $recommendedData = [
                'sku' => ['emarsys_attr_code' => $data[0]],
                'name' => ['emarsys_attr_code' => $data[1]],
                'url_key' => ['emarsys_attr_code' => $data[2]],
                'image' => ['emarsys_attr_code' => $data[3]],
                'category_ids' => ['emarsys_attr_code' => $data[4]],
                'price' => ['emarsys_attr_code' => $data[5]]
            ];

            foreach ($recommendedData as $key => $value) {
                if ($key == '') {
                    continue;
                }
                $recommendedArray[] = $value['magento_attr_code'] = $key;
                $model = $this->productFactory->create();
                $model = $model->setData($value);
                $model->setStoreId($storeId);
                $model->save();
            }
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Saved Recommended Mapping';
            $logsArray['action'] = 'Saved Recommended Mapping';
            $logsArray['message_type'] = 'Success';
            $logsArray['description'] = 'Saved Recommended Mapping as '.print_r($recommendedArray,true);
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['log_action'] = 'True';
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Product Recommended Mapping Saved Successfully';
            $this->logHelper->logs($logsArray);
            $this->logHelper->manualLogs($logsArray);
            $this->messageManager->addSuccess("Recommended Product attributes mapped successfully");
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(),$storeId,'Save Recommended(Product)');
            $this->messageManager->addError("Error occurred while mapping Product attribute");
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        }
    }
}
