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

class Save extends \Magento\Backend\App\Action
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
     *
     * @param Context $context
     * @param \Emarsys\Emarsys\Model\ProductFactory $productFactory
     * @param \Emarsys\Emarsys\Model\ResourceModel\Product $resourceModelProduct
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Model\ProductFactory $productFactory,
        \Emarsys\Emarsys\Helper\Data $emsrsysHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Log\Helper\Logs $logHelper,
        \Emarsys\Log\Model\Logs $emarsysLogs,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Emarsys\Emarsys\Model\ResourceModel\Product $resourceModelProduct,
        PageFactory $resultPageFactory
    ) {
    
        parent::__construct($context);
        $this->session = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->productFactory = $productFactory;
        $this->emarsysLogs = $emarsysLogs;
        $this->resourceModelProduct = $resourceModelProduct;
        $this->emsrsysHelper = $emsrsysHelper;
        $this->logHelper = $logHelper;
        $this->date = $date;
        $this->_storeManager = $storeManager;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $savedValues= array();
            $model = $this->productFactory->create();
            $session = $this->session->getData();
            $gridSessionStoreId = '';
            $gridSessionData = [];
            if (isset($session['gridData'])) {
                $gridSessionData = $session['gridData'];
            }
            if (isset($session['storeId'])) {
                $gridSessionStoreId = $session['storeId'];
            }
            if ($gridSessionStoreId == 0) {
                $gridSessionStoreId = $this->emsrsysHelper->getFirstStoreId();
            }
            $websiteId = $this->_storeManager->getStore($gridSessionStoreId)->getWebsiteId();
            $logsArray['job_code'] = 'Product Mapping';
            $logsArray['status'] = 'started';
            $logsArray['messages'] = 'Running Update Schema';
            $logsArray['description'] = 'Saving Product Mapping';
            $logsArray['created_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['run_mode'] = 'Automatic';
            $logsArray['auto_log'] = 'Complete';
            $logsArray['website_id'] = $websiteId;
            $logsArray['store_id'] = $gridSessionStoreId;
            $logId = $this->logHelper->manualLogs($logsArray);
            foreach ($gridSessionData as $key => $value) {
                if ($key == '') {
                    continue;
                }

                $value['magento_attr_code'] = $key;
                $modelColl = $model->getCollection()
                    ->addFieldToFilter('magento_attr_code', $key)
                    ->addFieldToFilter('store_id', $gridSessionStoreId);
                $modelCollData = $modelColl->getData();

                foreach ($modelCollData as $cols) {
                    $colsValue = $cols['emarsys_contact_field'];
                }

                if (isset($colsValue)) {
                    $model = $model->load($colsValue);
                }

                if (!empty($modelCollData)) {
                    foreach ($modelColl as $model) {
                        if (isset($value['emarsys_attr_code'])) {
                            $model->setEmarsysAttrCode($value['emarsys_attr_code']);
                        }
                        $model->setStoreId($gridSessionStoreId);
                        $savedValues[] = $value['emarsys_attr_code'];
                        $model->save();
                    }
                } else {
                    $model = $this->productFactory->create();
                    $model = $model->setData($value);
                    $model->setStoreId($gridSessionStoreId);
                    $savedValues[] = $value['emarsys_attr_code'];
                    $model->save();
                }
            }
            $logsArray['id'] = $logId;
            $logsArray['emarsys_info'] = 'Save Product Mapping';
            $logsArray['action'] = 'Save Product Mapping Successful';
            $logsArray['message_type'] = 'Success';
            $logsArray['description'] = 'Product Mapping Saved as '.print_r($savedValues,true);
            $logsArray['executed_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());
            $logsArray['log_action'] = 'True';
            $logsArray['status'] = 'success';
            $logsArray['messages'] = 'Save Product Mapping Saved Successfully';
            $this->logHelper->logs($logsArray);
            $this->logHelper->manualLogs($logsArray);
            $resultRedirect = $this->resultRedirectFactory->create();
            
            /**
             * Truncating the Mapping Table first
             */
            $this->resourceModelProduct->deleteUnmappedRows($gridSessionStoreId);
            
            $this->messageManager->addSuccess("Product attributes Mapped successfully");
            return $resultRedirect->setRefererOrBaseUrl();
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog($e->getMessage(),$gridSessionStoreId,'Save(Product)');
            $this->messageManager->addError("Error occurred while mapping Product attribute");
            return $resultRedirect->setRefererOrBaseUrl();
        }
    }
}
